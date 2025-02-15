<?php

namespace BPL\Jumi\Efund_Transfer;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
require_once 'bpl/mods/ajax.php';
require_once 'bpl/mods/transfer_history.php';
require_once 'bpl/mods/query.php';
require_once 'bpl/mods/mailer.php';
require_once 'bpl/mods/helpers.php';

use Exception;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Exception\ExceptionHandler;

use function BPL\Mods\Ajax\check_input2;

use function BPL\Mods\Transfer_History\view_row_transfers;

use function Templates\SB_Admin\Tmpl\Master\main as master;

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
use function BPL\Mods\Helpers\page_validate;
// use function BPL\Mods\Helpers\menu;
use function BPL\Mods\Helpers\user;
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

	page_validate();

	session_set('edit', false);

	$amount = input_get('amount');
	$username = input_get('username');
	$edit = session_get('edit', false);

	$date = input_get_date($edit);

	// $str = menu();

	if ($username !== '') {
		process_form($user_id, $amount, $username, $date);
	}

	$view_transfer_efund = view_transfer_efund($user_id);

	$sa = settings('ancillaries');

	$currency = $sa->currency;
	$processing_fee = $sa->processing_fee;

	$processing_fee_format = number_format($processing_fee, 2);
	$efund_name = $sa->efund_name;

	$note = $processing_fee ? "Transfers are subject to $processing_fee_format $currency processing fee to be deducted from the remaining balance." : "Transfer your $efund_name to another user";

	$str = check_input2();

	$str .= <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Transfer $efund_name</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">$note</li>
		</ol>				
		$view_transfer_efund
	</div>
	HTML;

	return $str;
}

function view_transfer_efund($user_id): string
{
	$form_transfer_efund = form_transfer_efund($user_id);

	$notifications = notifications();
	$view_transfer_history = view_transfer_history($user_id);

	return <<<HTML
    <div class="container-fluid px-4">        
		<div class="row justify-content-center">
			<div class="col-lg-5">
				$notifications
				<div class="card mb-4">
					$form_transfer_efund
				</div>
        	</div>		
		</div>
        $view_transfer_history
    </div>	
HTML;
}

function form_transfer_efund($user_id)
{
	$user = user($user_id);
	$efund = $user->payout_transfer;
	$efund_format = number_format($efund, 2);

	$sa = settings('ancillaries');

	$currency = $sa->currency;
	$efund_name = $sa->efund_name;

	$form_token = HTMLHelper::_('form.token');

	$str = <<<HTML
    <div class="card-header">
        <i class="fas fa-money-bill me-1"></i>
        $efund_name Balance: $efund_format $currency
    </div>
    <div class="card-body">
        <form method="post" onsubmit="submit.disabled = true;">
            $form_token			
			<div class="form-group">
				<div id="usernameDiv" class="help-block validation-message"></div>
				<div class="input-group mb-3">
					<span class="input-group-text"><label for="username">Recipient</label></span>
					<input type="text" name="username" id="username" class="form-control" 
						placeholder="Enter Recipient Username" required aria-label="Recipient Username">
					<span class="input-group-text btn btn-primary" onClick="checkInput('username')">Validate</span>
				</div>
			</div>

			<div class="input-group mb-3">
                <span class="input-group-text"><label for="amount">Amount</label></span>
                <input type="text" name="amount" id="amount" class="form-control" placeholder="Enter Amount" required aria-label="Amount">
                <span class="input-group-text">$currency</span>
            </div>

            <div class="form-group actions">
                <button type="submit" class="btn btn-primary">Transfer</button>                
            </div>
        </form>
    </div>
HTML;

	return $str;
}

// /**
//  *
//  * @param $user_id
//  *
//  * @return string
//  *
//  * @since version
//  */
// function view_form($user_id): string
// {
// 	$settings_ancillaries = settings('ancillaries');

// 	$efund_name = $settings_ancillaries->efund_name;

// 	$transfer_from = user($user_id);

// 	$str = '<form method="post" onsubmit="submit.disabled = true; return true;">
//         <input type="hidden" name="final" value="1">
//         <table class="category table table-striped table-bordered table-hover">
//             <tr>
//                 <td colspan="2"><strong>' . $efund_name . ': </strong>' .
// 		number_format($transfer_from->payout_transfer, 18) . '</td>
//             </tr>
//             <tr>
//                 <td><label for="username">Recipient Username:</label></a></td>
//                 <td><input type="text"
//                            name="username"
//                            id="username"
//                            required="required"
//                            size="40"
//                            style="float:left; padding-right: 12px">
//                     <a href="javascript:void(0)" onClick="checkInput(\'username\')"
//                        class="uk-button uk-button-primary"
//                        style="float:left">Check Username</a>
//                     <div style="width:200px; height:20px; font-weight:bold; float:left; padding:7px 0 0 10px;"
//                          id="usernameDiv"></div>
//                 </td>
//             </tr>
//             <tr>
//                 <td><strong><label for="amount">Amount:</label></strong></td>
//                 <td>
//                     <input type="text" name="amount" id="amount" style="float:left">
//                     <input type="submit" name="submit" value="Transfer" class="uk-button uk-button-primary">                   
//                 </td>
//             </tr>
//         </table>
//     </form>
//     <hr>';

// 	return $str;
// }

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
function validate_input($user_id, $amount, $username)
{
	$sa = settings('ancillaries');

	$processing_fee = $sa->processing_fee;
	$currency = $sa->currency;

	$transfer_from = user($user_id);

	$app = application();

	if (
		$amount === '' ||
		!is_numeric($amount) ||
		$username === '' ||
		$username === $transfer_from->username
	) {
		// application()->redirect(Uri::root(true) .
		// 	'/' . sef(16), 'Invalid Transaction!', 'error');

		$app->enqueueMessage('Invalid Transaction!', 'error');
		$app->redirect(Uri::current());
	}

	if ($transfer_from->payout_transfer < ($amount + $processing_fee)) {
		// application()->redirect(Uri::root(true) .
		// 	'/' . sef(16), 'Please maintain at least ' .
		// 	number_format($amount + $processing_fee, 2) .
		// 	' ' . $currency, 'error');

		$app->enqueueMessage('Please maintain at least ' .
			number_format($amount + $processing_fee, 2) . ' ' . $currency, 'error');
		$app->redirect(Uri::current());
	}

	$transfer_to = user_username($username);

	if (!$transfer_to->id) {
		// application()->redirect(Uri::root(true) .
		// 	'/' . sef(16), 'Invalid user!', 'error');

		$app->enqueueMessage('Invalid User!', 'error');
		$app->redirect(Uri::current());
	}

	$edit = session_get('edit', false);

	if ($edit) {
		$date = input_get('date', '', 'RAW');

		if ($date === '') {
			// application()->redirect(
			// 	Uri::root(true) . '/' . sef(16),
			// 	'Please specify the Current Date!',
			// 	'error'
			// );

			$app->enqueueMessage('Please specify the Current Date!', 'error');
			$app->redirect(Uri::current());
		}
	}
}

/**
 * @param $user_id
 * @param $amount
 *
 *
 * @since version
 */
function update_user_transfer_from($user_id, $amount)
{
	update(
		'network_users',
		[
			'payout_transfer = payout_transfer - ' .
			((double) $amount + (double) settings('ancillaries')->processing_fee)
		],
		['id = ' . db()->quote(user($user_id)->id)]
	);
}

/**
 * @param $username
 * @param $amount
 *
 *
 * @since version
 */
function update_user_transfer_to($username, $amount)
{
	update(
		'network_users',
		['payout_transfer = payout_transfer + ' . $amount],
		['id = ' . db()->quote(user_username($username)->id)]
	);
}

/**
 * @param $user_id
 * @param $username
 * @param $amount
 * @param $date
 *
 *
 * @since version
 */
function log_activity($user_id, $username, $amount, $date)
{
	$db = db();

	$sa = settings('ancillaries');

	$transfer_from = user($user_id);
	$transfer_to = user_username($username);

	$activity = '<b>' . $sa->efund_name . ' Transfer: </b> <a href="' . sef(44) . qs() . 'uid=' . $user_id . '">' .
		$transfer_from->username . '</a> transferred ' . number_format($amount, 18) . ' ' .
		settings('ancillaries')->currency . ' to <a href="' . sef(44) . qs() . 'uid=' .
		$transfer_to->id . '">' . $transfer_to->username . '</a>.';

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
			$db->quote($transfer_from->sponsor_id),
			$db->quote(1),
			$db->quote($activity),
			$db->quote($date)
		]
	);
}

/**
 * @param $user_id
 * @param $username
 * @param $amount
 * @param $date
 *
 * @param $insert_id
 *
 * @since version
 */
function log_transactions($user_id, $username, $amount, $date, &$insert_id)
{
	$db = db();

	$settings_ancillaries = settings('ancillaries');

	$efund_name = $settings_ancillaries->efund_name;

	$transfer_from = user($user_id);
	$transfer_to = user_username($username);

	$details = '<b>' . $efund_name . ' Transfer: </b> <a href="' . sef(44) . qs() . 'uid=' . $user_id . '">' .
		$transfer_from->username . '</a> transferred ' . number_format($amount, 18) . ' ' .
		$settings_ancillaries->currency . ' to <a href="' . sef(44) . qs() . 'uid=' . $transfer_to->id . '">' .
		$transfer_to->username . '</a>.';

	$balance = $transfer_from->payout_transfer - ((double) $amount + (double) $settings_ancillaries->processing_fee);

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
			$db->quote($efund_name . ' Transfer'),
			$db->quote($details),
			$db->quote($amount),
			$db->quote($balance),
			$db->quote($date)
		]
	);

	$insert_id = $db->insertid();
}

/**
 * @param $user_id
 * @param $username
 * @param $amount
 * @param $date
 *
 *
 * @since version
 */
function log_transfer($user_id, $username, $amount, $date)
{
	$db = db();

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
			$db->quote($user_id),
			$db->quote(user_username($username)->id),
			$db->quote('transfer'),
			$db->quote($date),
			$db->quote($amount)
		]
	);
}

/**
 *
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
 * @param $date
 *
 * @param $insert_id
 *
 * @since version
 */
function log_income_admin($date, $insert_id)
{
	$db = db();

	$processing_fee = settings('ancillaries')->processing_fee;

	$income_total = income_admin();

	$income = $income_total ? ($income_total->income_total + $processing_fee) : 0;

	insert(
		'network_income',
		[
			'transaction_id',
			'amount',
			'income_total',
			'income_date'
		],
		[
			$db->quote($insert_id),
			$db->quote($processing_fee),
			$db->quote($income),
			$db->quote($date)
		]
	);
}

/**
 * @param $user_id
 * @param $username
 * @param $amount
 * @param $date
 *
 *
 * @since version
 */
function logs($user_id, $username, $amount, $date)
{
	$insert_id = 0;

	log_activity($user_id, $username, $amount, $date);
	log_transactions($user_id, $username, $amount, $date, $insert_id);
	log_transfer($user_id, $username, $amount, $date);
	log_income_admin($date, $insert_id);
}

/**
 * @param $user_id
 * @param $amount
 * @param $username
 *
 * @param $date
 *
 * @since version
 */
function process_form($user_id, $amount, $username, $date)
{
	$db = db();

	$sa = settings('ancillaries');

	$currency = $sa->currency;

	validate_input($user_id, $amount, $username);

	$transfer_from = user($user_id);
	$transfer_to = user_username($username);

	// mail admin and user
	$message = '<strong>Sender</strong>
			Username: ' . $transfer_from->username . '<br>
			Full Name: ' . $transfer_from->fullname . '<br>
			Email: ' . $transfer_from->email . '<br>
			Contact: ' . $transfer_from->contact . '<br><br>

			<strong>Recipient</strong>
			Username: ' . $transfer_to->username . '<br>
			Full Name: ' . $transfer_to->fullname . '<br>
			Email: ' . $transfer_to->email . '<br>
			Contact: ' . $transfer_to->contact . '<br><br>

			<strong>Amount Transferred</strong><br>' . number_format($amount) . ' ' . $currency . '<br>';

	try {
		$db->transactionStart();

		update_user_transfer_from($user_id, $amount);
		update_user_transfer_to($username, $amount);

		logs($user_id, $username, $amount, $date);

		send_mail($message, $sa->efund_name .
			' Transferred Successfully!', [$transfer_from->email, $transfer_to->email]);

		$db->transactionCommit();
	} catch (Exception $e) {
		$db->transactionRollback();

		ExceptionHandler::render($e);
	}

	$app = application();

	$app->enqueueMessage(number_format($amount, 2) . ' ' . $currency .
		' transferred to ' . $username . '.', 'success');
	$app->redirect(Uri::current());
}

/**
 * @param $edit
 *
 *
 * @return string
 * @since version
 */
function input_get_date($edit): string
{
	$date = time();

	if ($edit) {
		$date = input_get('date', '0', 'RAW');
	}

	return $date;
}

/**
 * @param $user_id
 *
 * @return array|mixed
 *
 * @since version
 */
function user_transfers($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_transfer ' .
		'WHERE (transfer_from = ' . $db->quote($user_id) .
		' OR transfer_to = ' . $db->quote($user_id) .
		') AND type = ' . $db->quote('transfer') .
		' ORDER BY date DESC'
	)->loadObjectList();
}

function view_transfer_history($user_id): string
{
	$sa = settings('ancillaries');

	$efund_name = $sa->efund_name;

	$table_transfer_history = table_transfer_history($user_id);

	return <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-hourglass me-1"></i>
				$efund_name Transfer History
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
	$sa = settings('ancillaries');

	$currency = $sa->currency;

	$row_transfer_history = row_transfer_history($user_id);

	$str = <<<HTML
		<thead>
			<tr>
				<th>Date Transferred</th>
				<th>Transfer From</th>
				<th>Transfer To</th>
				<th>Amount ($currency)</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Date Transferred</th>
				<th>Transfer From</th>
				<th>Transfer To</th>
				<th>Amount ($currency)</th>
			</tr>
		</tfoot>
		<tbody>
			$row_transfer_history						
		</tbody>
HTML;

	return $str;
}

/**
 *
 * @param $user_id
 *
 * @return string
 *
 * @since version
 */
function row_transfer_history($user_id): string
{
	$transfers = user_transfers($user_id);

	$str = '';

	// if (empty($transfers)) {
	// 	$str .= '<hr><p>No transfers yet.</p>';
	// } else {
	// 	$str .= '<table class="category table table-striped table-bordered table-hover">
	//     <thead>
	//     <tr>
	//         <th>Date Transferred</th>
	//         <th>Transfer From</th>
	//         <th>Transfer To</th>
	//         <th>Amount (' . settings('ancillaries')->efund_name . ')</th>
	//     </tr>
	//     </thead>
	//     <tbody>';

	$str .= view_row_transfers($transfers);

	// 	$str .= '</tbody>
	// 	</table>';
	// }

	return $str;
}