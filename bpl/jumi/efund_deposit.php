<?php

namespace BPL\Jumi\Efund_Deposit;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
require_once 'bpl/mods/ajax.php';
// require_once 'bpl/mods/transfer_history.php';
require_once 'bpl/mods/query.php';
require_once 'bpl/mods/mailer.php';
require_once 'bpl/mods/helpers.php';

use Exception;

use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Exception\ExceptionHandler;

use function BPL\Mods\Ajax\check_input2;

use function Templates\SB_Admin\Tmpl\Master\main as master;

// use function BPL\Mods\Transfer_History\view_row_transfers;

use function BPL\Mods\Database\Query\insert;
use function BPL\Mods\Database\Query\update;

use function BPL\Mods\Mailer\main as send_mail;

use function BPL\Mods\Url_SEF\sef;
use function BPL\Mods\Url_SEF\qs;

use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\application;
use function BPL\Mods\Helpers\input_get;
use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\session_set;
use function BPL\Mods\Helpers\settings;
use function BPL\Mods\Helpers\user;
// use function BPL\Mods\Helpers\menu;
use function BPL\Mods\Helpers\page_validate;
use function BPL\Mods\Helpers\time;
use function BPL\Mods\Helpers\user_username;

$content = main();

master($content);

/**
 *
 *
 * @since version
 */
function main()
{
	$user_id = session_get('user_id');
	// $final = input_get('final');

	session_set('edit', false);

	page_validate();

	// $str = menu();

	$str = '';

	$amount = input_get('amount');
	$username_to = input_get('username');
	$edit = session_get('edit', false);

	if ($amount === '') {
		$str .= check_input2();
		$str .= view_transfer_efund($user_id);
	} else {
		process_efund_deposit($user_id, $amount, $username_to, $edit);
	}

	return $str;
}

function view_transfer_efund($user_id)
{
	$sa = settings('ancillaries');
	$efund_name = $sa->efund_name;

	$view_form = view_form($user_id);
	$view_transfer_history = view_transfer_history($user_id);
	$notifications = notifications();

	return <<<HTML
    <div class="container-fluid px-4">
        <h1 class="mt-4">Transfer Efund</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item active">Deposit your $efund_name to another user</li>
        </ol>
		<div class="row justify-content-center">
			<div class="col-lg-5">
				$notifications
				<div class="card mb-4">
					$view_form
				</div>
        	</div>		
		</div>
        $view_transfer_history
    </div>
HTML;
}

function notifications(): string
{
	$app = application();

	// Display Joomla messages as dismissible alerts
	$messages = $app->getMessageQueue(true);
	$notification_str = fade_effect(); // Initialize the notification string

	if (!empty($messages)) {
		foreach ($messages as $message) {
			// Map Joomla message types to Bootstrap alert classes
			$alert_class = '';
			switch ($message['type']) {
				case 'error':
					$alert_class = 'danger'; // Bootstrap uses 'danger' instead of 'error'
					break;
				case 'warning':
					$alert_class = 'warning';
					break;
				case 'notice':
					$alert_class = 'info'; // Joomla 'notice' maps to Bootstrap 'info'
					break;
				case 'message':
				default:
					$alert_class = 'success'; // Joomla 'message' maps to Bootstrap 'success'
					break;
			}

			$notification_str .= <<<HTML
            <div class="alert alert-{$alert_class} alert-dismissible fade show mt-5" role="alert">
                {$message['message']}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
HTML;
		}
	}

	return $notification_str;
}

function fade_effect(int $duration = 10000)
{
	return <<<HTML
    <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Select all alert elements
      const alerts = document.querySelectorAll('.alert');

      // Loop through each alert and set a timeout to dismiss it
      alerts.forEach(function (alert) {
        setTimeout(function () {
          // Use Bootstrap's alert method to close the alert
          bootstrap.Alert.getOrCreateInstance(alert).close();
        }, $duration);
      });
    });
  </script>
HTML;
}

/**
 *
 *
 * @since version
 */
function validate_input($user_id, $amount, $username_to, $edit)
{
	$sa = settings('ancillaries');

	$currency = $sa->currency;
	$processing_fee = $sa->processing_fee;

	$deposit_from = user($user_id);

	$app = application();

	if (
		$amount === '' ||
		!is_numeric($amount) ||
		$deposit_from->username === ''
	) {
		// $err = 'Invalid Transaction!';

		// $app->redirect(Uri::root(true) . '/' . sef(15), $err, 'error');

		$app->enqueueMessage('Invalid Transaction!', 'error');
		$app->redirect(Uri::current());
	}

	if (
		$deposit_from->payout_transfer < ($amount + $processing_fee) &&
		$username_to !== $deposit_from->username
	) {
		$err = 'Please maintain balance of at least ' .
			number_format($amount + $processing_fee, 2) . ' ' . $currency;

		// $app->redirect(Uri::root(true) . '/' . sef(15), $err, 'error');

		$app->enqueueMessage($err, 'error');
		$app->redirect(Uri::current());
	} elseif (
		($deposit_from->payout_transfer < $amount) &&
		$username_to === $deposit_from->username
	) {
		$err = 'Please maintain balance of at least ' . number_format($amount, 2) . ' ' . $currency;

		// $app->redirect(Uri::root(true) . '/' . sef(15), $err, 'error');

		$app->enqueueMessage($err, 'error');
		$app->redirect(Uri::current());
	}

	$deposit_to = user_username($username_to);

	if ($deposit_to->id === '') {
		$err = 'Invalid user!';

		// $app->redirect(Uri::root(true) . '/' . sef(15), $err, 'error');

		$app->enqueueMessage($err, 'error');
		$app->redirect(Uri::current());
	}

	if ($edit === true) {
		$date = input_get('date', '', 'RAW');

		if ($date === '') {
			$err = 'Please specify the Current Date!';

			// application()->redirect(Uri::root(true) . '/' . sef(15), $err, 'error');

			$app->enqueueMessage($err, 'error');
			$app->redirect(Uri::current());
		}
	}
}

/**
 *
 * @return mixed|null
 *
 * @since version
 */
function income_admin()
{
	return db()->setQuery(
		'SELECT * ' .
		'FROM network_income ' .
		'ORDER BY income_id DESC'
	)->loadObject();
}

/**
 * @param $user_id
 * @param $amount
 * @param $username_to
 *
 * @param $date
 *
 * @since version
 */
function logs($user_id, $amount, $username_to, $date)
{
	$settings_ancillaries = settings('ancillaries');

	$efund_name = $settings_ancillaries->efund_name;

	$currency = $settings_ancillaries->currency;
	$processing_fee = $settings_ancillaries->processing_fee;

	$deposit_from = user($user_id);
	$deposit_to = user_username($username_to);

	$db = db();

	insert(
		'network_transactions',
		[
			'user_id',
			'transaction',
			'details',
			'value',
			'balance',
			'transaction_date'
		],
		[
			$db->quote($user_id),
			$db->quote('Deposit ' . $efund_name),
			$db->quote(number_format($amount, 2) . ' ' . $currency . ' deposited to <a href="' .
				sef(44) . qs() . 'uid=' . $deposit_to->id . '">' . $username_to . '</a>.'),
			$amount,
			($deposit_from->username !== $username_to ?
				($deposit_from->balance - ((double) $amount + (double) $processing_fee)) :
				($deposit_from->balance - $amount)),
			$db->quote($date)
		]
	);

	insert(
		'network_activity',
		[
			'user_id',
			'sponsor_id',
			'upline_id',
			'activity',
			'activity_date'
		],
		[
			$db->quote($user_id),
			$db->quote($deposit_from->sponsor_id),
			$db->quote(1),
			$db->quote('<b>Deposit ' . $efund_name . ': </b> <a href="' . sef(44) . qs() . 'uid=' .
				$user_id . '">' . $deposit_from->username . '</a> deposited ' . number_format($amount, 2) .
				' ' . $currency . ' to <a href="' . sef(44) . qs() . 'uid=' . $deposit_to->id . '">' .
				$deposit_to->username . '</a>.'),
			$db->quote($date)
		]
	);

	insert(
		'network_transfer',
		[
			'transfer_from',
			'transfer_to',
			'type',
			'date',
			'amount'
		],
		[
			$db->quote($deposit_from->id),
			$db->quote($deposit_to->id),
			$db->quote('deposit'),
			$db->quote($date),
			$db->quote($amount)
		]
	);

	if ($deposit_from->username !== $username_to) {
		$transaction_id = $db->insertid();

		$income_total = income_admin();
		$income_total = $income_total->income_total ?? 0;
		$income = $income_total + $processing_fee;

		// insert company income
		insert(
			'network_income',
			[
				'transaction_id',
				'amount',
				'income_total',
				'income_date'
			],
			[
				$db->quote($transaction_id),
				$db->quote($processing_fee),
				$db->quote($income),
				$db->quote(time())
			]
		);
	}

	insert(
		'network_transactions',
		[
			'user_id',
			'transaction',
			'details',
			'value',
			'balance',
			'transaction_date'
		],
		[
			$db->quote($deposit_to->id),
			$db->quote('Deposit ' . $efund_name),
			$db->quote(number_format($amount, 2) . ' ' . $currency . ' deposited from <a href="' .
				sef(44) . qs() . 'uid=' . $user_id . '">' . $deposit_from->username . '</a> to <a href="' .
				sef(44) . qs() . 'uid=' . $deposit_to->id . '">' . $deposit_to->username . '</a>'),
			$amount,
			$db->quote($deposit_from->balance),
			$db->quote($date)
		]
	);
}

/**
 * @param $user_id
 * @param $amount
 *
 *
 * @since version
 */
function update_user_deposit_from($user_id, $amount)
{
	update(
		'network_users',
		[
			'payout_transfer = payout_transfer - ' .
			((double) $amount + (double) settings('ancillaries')->processing_fee)
		],
		['id = ' . db()->quote($user_id)]
	);
}

/**
 * @param $username
 * @param $amount
 *
 *
 * @since version
 */
function update_user_deposit_to($username, $amount)
{
	update(
		'network_users',
		['payout_transfer = payout_transfer + ' . $amount],
		['id = ' . db()->quote(user_username($username)->id)]
	);
}

/**
 * @param $edit
 *
 * @return string
 *
 * @since version
 */
function date_get($edit): string
{
	$date = time();

	if ($edit) {
		$date = input_get('date', '', 'RAW');
	}

	return $date;
}

/**
 * @param $user_id
 * @param $amount
 * @param $username_to
 *
 * @param $edit
 *
 * @since version
 */
function process_efund_deposit($user_id, $amount, $username_to, $edit)
{
	$sa = settings('ancillaries');

	$efund_name = $sa->efund_name;

	$app = application();

	$db = db();

	validate_input($user_id, $amount, $username_to, $edit);

	$deposit_from = user($user_id);
	$deposit_to = user_username($username_to);

	// mail admin and user
	$message = '<strong>Sender</strong>
			Username: ' . $deposit_from->username . '<br>
			Full Name: ' . $deposit_from->fullname . '<br>
			Email: ' . $deposit_from->email . '<br>
			Contact Number: ' . $deposit_from->contact . '<br><br>

			<strong>Recipient</strong>
			Username: ' . $deposit_to->username . '<br>
			Full Name: ' . $deposit_to->fullname . '<br>
			Email: ' . $deposit_to->email . '<br>
			Contact Number: ' . $deposit_to->contact . '<br><br>

			<strong>Amount Deposited</strong><br>
			' . $amount . '<br>';

	try {
		$db->transactionStart();

		update_user_deposit_from($user_id, $amount);
		update_user_deposit_to($username_to, $amount);

		logs($user_id, $amount, $username_to, date_get($edit));

		send_mail($message, $efund_name .
			' Deposited Successfully!', [$deposit_to->email, $deposit_from->email]);

		$db->transactionCommit();
	} catch (Exception $e) {
		$db->transactionRollback();

		ExceptionHandler::render($e);
	}

	//	send_mail($user_id, $amount, $username_to);

	// application()->redirect(Uri::root(true) . '/' . sef(15), number_format($amount, 2) .
	// 	' ' . settings('ancillaries')->currency . ' deposited to ' . $username_to . '\'s ' . $efund_name, 'notice');

	$msg = number_format($amount, 2) . ' ' . $sa->currency . ' deposited to ' . $username_to . '\'s ' . $efund_name;

	$app->enqueueMessage($msg, 'success');
	$app->redirect(Uri::current());
}

/**
 * @param $user_id
 *
 * @return array|mixed
 *
 * @since version
 */
function user_deposits($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_transfer ' .
		'WHERE (transfer_from = ' . $db->quote($user_id) .
		' OR transfer_to = ' . $db->quote($user_id) .
		') AND type = ' . $db->quote('deposit') .
		' ORDER BY date DESC'
	)->loadObjectList();
}

/**
 *
 * @param $user_id
 *
 * @return string
 *
 * @since version
 */
function view_form($user_id): string
{
	$sa = settings('ancillaries');

	$efund_name = $sa->efund_name;
	$currency = $sa->currency;
	$processing_fee = $sa->processing_fee;
	$processing_fee_format = number_format($processing_fee, 2);

	$deposit_from = user($user_id);
	$deposit_from_efund = $deposit_from->payout_transfer;
	$deposit_from_efund_format = number_format($deposit_from_efund, 2);

	$form_token = HTMLHelper::_('form.token');

	$note = '';

	if ($processing_fee) {
		$note = <<<HTML
        <p>Note: $efund_name conversion is subject to a $processing_fee_format $currency processing fee.</p>
        HTML;
	}

	return <<<HTML
    <div class="card-header">
        <i class="fas fa-money-bill-transfer me-1"></i> Deposit $efund_name
    </div>
    <div class="card-body">
        <form method="post" onsubmit="submit.disabled = true;">
            $form_token
            $note
            <div class="form-group">
                <label for="username">Recipient Username: *</label>
                <div class="input-group" style="max-width: 300px;">
                    <input type="text" name="username" id="username" class="form-control" placeholder="Username" required>
                    <span class="input-group-btn">
                        <button type="button" onClick="checkInput('username')" class="btn btn-default" style="height: 38px;">Check</button>
                    </span>
                </div>
                <div id="usernameDiv" class="help-block validation-message"></div>
            </div><br>
            <div class="form-group">
                <label for="amount">Amount: *</label>
                <input type="text" name="amount" id="amount" class="form-control" placeholder="Amount" required style="max-width: 300px;">
            </div><br>
            <div class="form-group actions">
                <button type="submit" class="btn btn-primary">Deposit</button>
                <span style="float: right">$efund_name Balance: <span id="efund_balance">$deposit_from_efund_format</span> $currency</span>
            </div>
        </form>
    </div>
    HTML;
}

function view_transfer_history($user_id)
{
	$sa = settings('ancillaries');

	$efund_name = $sa->efund_name;

	$table_transfer_history = table_transfer_history($user_id);

	return <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-history me-1"></i>
				$efund_name Deposit History
			</div>
			<div class="card-body">
				<table id="datatablesSimple">
					$table_transfer_history
				</table>
			</div>
		</div>
	HTML;
}

function table_transfer_history($user_id)
{
	$row_transfer_history = row_transfer_history($user_id);

	$str = <<<HTML
		<thead>
			<tr>
				<th>Date</th>
				<th>Transfer From</th>
				<th>Transfer To</th>
				<th>Amount</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Date</th>
				<th>Transfer From</th>
				<th>Transfer To</th>
				<th>Amount</th>
			</tr>
		</tfoot>
		<tbody>
			$row_transfer_history						
		</tbody>
	HTML;

	return $str;
}

function row_transfer_history($user_id)
{
	$deposits = user_deposits($user_id);

	$str = '';

	if (empty($deposits)) {
		$str .= <<<HTML
			<tr>
				<td>n/a</td>
				<td>n/a</td>
				<td>n/a</td>
				<td>0</td>							
			</tr>					
		HTML;
	} else {
		foreach ($deposits as $deposit) {
			$deposit_from = user($deposit->transfer_from);
			$deposit_to = user($deposit->transfer_to);

			$deposit_amount_format = number_format($deposit->amount, 2);

			$desposit_date = date('M j, Y - g:i A', $deposit->date);

			$user_deposit_from_link = sef(44) . qs() . 'uid=' . $deposit_from->id;
			$user_deposit_to_link = sef(44) . qs() . 'uid=' . $deposit_to->id;

			$user_deposit_from = <<<HTML
				<a href="$user_deposit_from_link">$deposit_from->username</a>
			HTML;
			$user_deposit_to = <<<HTML
				<a href="$user_deposit_to_link">$deposit_to->username</a>
			HTML;

			$str .= <<<HTML
				<tr>
					<td>$desposit_date</td>
					<td>$user_deposit_from</td>
					<td>$user_deposit_to</td>
					<td>$deposit_amount_format</td>									
				</tr>
			HTML;
		}
	}

	return $str;
}