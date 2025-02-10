<?php

namespace BPL\Jumi\Withdrawal_Request;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
require_once 'bpl/mods/usdt_currency.php';
require_once 'bpl/mods/payout_method.php';
require_once 'bpl/menu.php';
require_once 'bpl/mods/query.php';
require_once 'bpl/mods/mailer.php';
require_once 'bpl/mods/helpers.php';

use Exception;

use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Exception\ExceptionHandler;

use function BPL\Menu\admin as menu_admin;
use function BPL\Menu\member as menu_member;
use function BPL\Menu\manager as menu_manager;

use function Templates\SB_Admin\Tmpl\Master\main as master;

use function BPL\Mods\Database\Query\insert;
use function BPL\Mods\Database\Query\update;
use function BPL\Mods\Database\Query\delete;

// use function BPL\Mods\Payout_Method\main as payout_method;

// use function BPL\Mods\USDT_Currency\main as usdt_currency;

use function BPL\Mods\Mailer\main as send_mail;

use function BPL\Mods\Url_SEF\sef;
use function BPL\Mods\Url_SEF\qs;

use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\session_set;
use function BPL\Mods\Helpers\page_validate;
use function BPL\Mods\Helpers\input_get;
use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\settings;
use function BPL\Mods\Helpers\user;
use function BPL\Mods\Helpers\application;

$content = main();

master($content);

/**
 *
 *
 * @since version
 */
function main()
{
	// $username = session_get('username');
	$usertype = session_get('usertype');
	// $admintype = session_get('admintype');
	// $account_type = session_get('account_type');
	// $merchant_type = session_get('merchant_type');
	$amount = input_get('amount');
	$user_id = session_get('user_id');

	page_validate();

	session_set('edit', false);

	// $str = menu($usertype, $admintype, $account_type, $username, $merchant_type, $user_id);

	$str = '';

	if ($usertype === 'Member' || $usertype === 'Admin') {
		if ($amount !== '') {
			$amount_final = input_get('amount_final');
			$deductions_total = input_get('total_deductions');
			$method = input_get('method');

			process_payout(
				$amount,
				$amount_final,
				$deductions_total,
				$method,
				$user_id
			);
		}

		$uid = input_get('uid');
		$cancel = input_get('cancel', 0);

		if ($cancel && $uid) {
			request_cancel($uid, $user_id);
		}

		$str .= js();

		// $str .= header($user_id);

		$str .= view_withdrawal_request($user_id);
	}

	return $str;
}

function view_withdrawal_request($user_id)
{
	$view_form = view_form($user_id);
	$view_pending_requests = view_pending($user_id);
	$notifications = notifications();

	$header = header($user_id);

	return <<<HTML
    <div class="container-fluid px-4">
        <h1 class="mt-4">Payout Request</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item active">$header</li>
        </ol>
		<div class="row justify-content-center">
			<div class="col-lg-5">
				$notifications
				<div class="card mb-4">
					$view_form
				</div>
        	</div>		
		</div>
        $view_pending_requests
    </div>
HTML;
}

function view_form($user_id): string
{
	$sa = settings('ancillaries');

	$currency = $sa->currency;
	$processing_fee = $sa->processing_fee;
	$processing_fee_format = number_format($processing_fee, 2);

	$user = user($user_id);
	$efund = $user->payout_transfer;
	$efund_format = number_format($efund, 2);

	$form_token = HTMLHelper::_('form.token');

	return <<<HTML
    <div class="card-header">
        <i class="fas fa-money-bill me-1"></i>
        Wallet Available Balance: $efund_format $currency
    </div>
    <div class="card-body">
        <form method="post" onsubmit="submit.disabled = true;">
            $form_token
            <input type="hidden" readonly="readonly" name="amount_final" id="input_amount_final">
			<input type="hidden" readonly="readonly" name="total_deductions" id="input_total_deductions">

            <div class="input-group mb-3">
                <span class="input-group-text"><label for="amount">Amount</label></span>
                <input type="text" name="amount" id="amount" class="form-control" placeholder="Enter Amount" required aria-label="Amount">
                <span class="input-group-text">$currency</span>
            </div>

			<div class="input-group mb-3">
				<select class="form-select" name="method" id="method">
					<option selected value="">Choose...</option>
					<option value="gcash">Gcash</option>
					<option value="maya">Maya</option>
					<option value="bank">Bank</option>
				</select>
				<label class="input-group-text" for="method">Method</label>
			</div>

            <button type="button" class="btn btn-default mb-3 border">
                Service Charge <span class="badge text-bg-secondary"><span id="tax">0.00</span> $currency</span>
            </button>

            <button type="button" class="btn btn-default mb-3 border">
                Processing Fee <span class="badge text-bg-secondary"><span id="fee">$processing_fee_format</span> $currency</span>
            </button>

            <button type="button" class="btn btn-default mb-3 border">
                Total Deductions <span class="badge text-bg-secondary"><span id="total_deductions">0.00</span> $currency</span>
            </button>

			<button type="button" class="btn btn-default mb-3 border">
                Final Amount <span class="badge text-bg-secondary"><span id="amount_final">0.00</span> $currency</span>
            </button>

            <div class="form-group actions">
                <button type="submit" class="btn btn-primary">Submit</button>                
            </div>
        </form>
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
 * @param $usertype
 * @param $admintype
 * @param $account_type
 * @param $username
 * @param $merchant_type
 * @param $user_id
 *
 * @return string
 *
 * @since version
 */
function menu($usertype, $admintype, $account_type, $username, $merchant_type, $user_id): string
{
	$str = '';

	switch ($usertype) {
		case 'Admin':
			$str .= menu_admin($admintype, $account_type, $user_id, $username);
			break;
		case 'Member':
			$str .= menu_member($account_type, $username, $user_id);
			break;
		case 'manager':
			$str .= menu_manager();
			break;
	}

	return $str;
}

/**
 * @param $user_id
 *
 * @return array|mixed
 *
 * @since version
 */
function user_payout_pending($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_withdrawals ' .
		'WHERE user_id = ' . $db->quote($user_id) .
		' AND date_completed = ' . $db->quote(0)
	)->loadObjectList();
}

function validate_input($amount, $user_id, $method, $edit)
{
	$sa = settings('ancillaries');

	$currency = $sa->currency;
	$cybercharge = $sa->cybercharge;
	$processing_fee = $sa->processing_fee;

	$user = user($user_id);

	$minimum_withdraw = $sa->{$user->account_type . '_min_withdraw'}; // php
	$minimum_bal_usd = $sa->{$user->account_type . '_min_bal_usd'}; // php

	$minimum_withdraw_format = number_format($minimum_withdraw, 2);

	$pending = user_payout_pending($user_id);

	$app = application();

	if (empty($method)) {
		$app->enqueueMessage("Please Fillup Payment Method!", 'error');
		$app->redirect(Uri::current());
	}

	$pending_requests = 0;

	if ($pending) {
		foreach ($pending as $requested) {
			$pending_requests += $requested->amount;
		}
	}

	$pending_requests_format = number_format($pending_requests, 2);

	// $gcash = explode('|', $user->bank);

	$user = user($user_id);

	$payment_method = arr_payment_method($user);

	$bank_name = '';
	$account_number = '';

	$gcash_name = '';
	$gcash_number = '';

	$maya_name = '';
	$maya_number = '';

	if (!empty($payment_method['bank'])) {
		foreach ($payment_method['bank'] as $k => $v) {
			$bank_name = $k;
			$account_number = $v;
		}
	}

	if (!empty($payment_method['gcash'])) {
		foreach ($payment_method['gcash'] as $k => $v) {
			$gcash_name = $k;
			$gcash_number = $v;
		}
	}

	if (!empty($payment_method['maya'])) {
		foreach ($payment_method['maya'] as $k => $v) {
			$maya_name = $k;
			$maya_number = $v;
		}
	}

	$has_bank = !empty($bank_name) && !empty($account_number);
	$has_gcash = !empty($gcash_name) && !empty($gcash_number);
	$has_maya = !empty($maya_name) && !empty($maya_number);

	if (!$has_bank && !$has_gcash && !$has_maya) {
		$app->enqueueMessage('Please Completely Fillup Your Gcash, Maya, or Bank Details.', 'error');
		$app->redirect(Uri::root(true) . '/' . sef(60) . qs() . 'uid=' . $user_id);
	}

	if ($method === 'bank' && !$has_bank) {
		$app->enqueueMessage('Please Completely Fillup Your Bank Details.', 'error');
		$app->redirect(Uri::root(true) . '/' . sef(60) . qs() . 'uid=' . $user_id);
	}

	if ($method === 'gcash' && !$has_gcash) {
		$app->enqueueMessage('Please Completely Fillup Your Gcash Details.', 'error');
		$app->redirect(Uri::root(true) . '/' . sef(60) . qs() . 'uid=' . $user_id);
	}

	if ($method === 'maya' && !$has_maya) {
		$app->enqueueMessage('Please Completely Fillup Your Maya Details.', 'error');
		$app->redirect(Uri::root(true) . '/' . sef(60) . qs() . 'uid=' . $user_id);
	}

	// if (!empty($bank_name) && !empty($account_number)) {
	// 	$app->enqueueMessage('Please Completely Fillup Your Gcash, Maya, or Bank Details.', 'error');
	// 	$app->redirect(Uri::root(true) . '/' . sef(60) . qs() . 'uid=' . $user_id);
	// }

	if (empty($amount)) {
		// $app->redirect(
		// 	Uri::root(true) . '/' . sef(113),
		// 	'Enter any amount!',
		// 	'error'
		// );

		$app->enqueueMessage('Amount is required!', 'error');
		$app->redirect(Uri::current());
	}

	if ($amount < $minimum_withdraw) {
		// $app->redirect(
		// 	Uri::root(true) . '/' . sef(113),
		// 	"Minimum withdraw is $minimum_withdraw_format",
		// 	'error'
		// );

		$app->enqueueMessage("Minimum withdraw is $minimum_withdraw_format", 'error');
		$app->redirect(Uri::current());
	}

	if (
		($user->payout_transfer - $pending_requests) < (
			$amount * (1 + $cybercharge / 100) + $processing_fee + $minimum_bal_usd)
	) {
		$err = 'Maintain at least ' . number_format(($amount * (
			1 + $cybercharge / 100) + $processing_fee + $minimum_bal_usd), 2) .
			(!$pending_requests ? (' ' . $currency . '!') : (' ' . $currency .
				' due to your pending requests amounting to ' .
				$pending_requests_format) . ' ' . $currency . '!');

		// $app->redirect(Uri::root(true) . '/' . sef(113), $err, 'error');

		$app->enqueueMessage($err, 'error');
		$app->redirect(Uri::current());
	}

	if ($edit) {
		$date = input_get('date', '', 'RAW');
	}

	if ($edit && $date === '') {
		// $app->redirect(Uri::root(true) . '/' . sef(113), 'Please specify Date!', 'error');

		$app->enqueueMessage('Please specify Date!', 'error');
		$app->redirect(Uri::current());
	}
}

function arr_payment_method($user)
{
	$payout_method = empty($user->payment_method) ? '{}' : $user->payment_method;

	return json_decode($payout_method, true);
}

function insert_payout($amount, $amount_final, $deductions_total, $method, $user_id, $edit)
{
	$db = db();

	insert(
		'network_withdrawals',
		[
			'user_id',
			'amount',
			'amount_final',
			'deductions_total',
			'method',
			'date_requested'
		],
		[
			$db->quote($user_id),
			$db->quote($amount),
			$db->quote($amount_final),
			$db->quote($deductions_total),
			$db->quote($method),
			$db->quote(date_get($edit))
		]
	);
}

/**
 * @param $edit
 *
 * @return int|string
 *
 * @since version
 */
function date_get($edit)
{
	$date = input_get('date', '', 'RAW');

	return $edit && isset($date) ? $date : time();
}


function process_payout($amount, $amount_final, $deductions_total, $method, $user_id)
{
	$db = db();

	$app = application();

	$edit = session_get('edit', false);

	validate_input($amount, $user_id, $method, $edit);

	$sa = settings('ancillaries');

	$currency = $sa->currency;

	$user = user($user_id);

	// mail admin and user
	$message = 'Username: ' . $user->username . '<br>
			Full Name: ' . $user->fullname . '<br>
			Email: ' . $user->email . '<br>
			Contact Number: ' . $user->contact . '<br><br>
			
			e-Wallet Balance: ' . $user->payout_transfer . ' ' . $currency . '<br>
			Amount Requested: ' . $amount . ' ' .
		$currency . '<br>
			Method: ' . ucwords($method);

	try {
		$db->transactionStart();

		insert_payout($amount, $amount_final, $deductions_total, $method, $user_id, $edit);

		update_payout_user($db->insertid());

		send_mail($message, 'Payout Request', [$user->email]);

		$db->transactionCommit();
	} catch (Exception $e) {
		$db->transactionRollback();

		ExceptionHandler::render($e);
	}

	// application()->redirect(
	// 	Uri::root(true) . '/' . sef(113),
	// 	'Payout processing time: Within 48 hours',
	// 	'success'
	// );

	$app->enqueueMessage('Payout processing time: Within 48 hours', 'success');
	$app->redirect(Uri::current());

	//	send_mail($amount, $user_id);
}

/**
 * @param $uid
 *
 *
 * @since version
 */
function update_payout_user($uid)
{
	$db = db();

	$payout = user_payouts($uid);

	update(
		'network_users',
		[
			'payout_transfer = payout_transfer - ' . ($payout->amount + $payout->deductions_total),
			'payout_total = payout_total + ' . $payout->amount
		],
		['id = ' . $db->quote($payout->user_id)]
	);
}

function js(): string
{
	$sa = settings('ancillaries');

	$cybercharge = $sa->cybercharge / 100;
	$processing_fee = $sa->processing_fee;

	$script = <<<JS
    document.addEventListener('DOMContentLoaded', function () {
        const amountInput = document.getElementById('amount');
        const taxSpan = document.getElementById('tax');
        const feeSpan = document.getElementById('fee');

        const totalDeductionsSpan = document.getElementById('total_deductions');
		const totalDeductionsInput = document.getElementById('input_total_deductions');

        const amountFinalSpan = document.getElementById('amount_final');
		const amountFinalInput = document.getElementById('input_amount_final');

        amountInput.addEventListener('input', function () {
            const amount = parseFloat(amountInput.value) || 0;
            const cybercharge = $cybercharge;
            const processingFee = $processing_fee;
            const tax = amount * cybercharge;
            const deductions = tax + processingFee;
			const amountFinal = amount - deductions;
            
            taxSpan.textContent = tax.toFixed(2);

            totalDeductionsSpan.textContent = deductions.toFixed(2);
			totalDeductionsInput.value = deductions.toFixed(2);      

			amountFinalSpan.textContent = amountFinal.toFixed(2);
			amountFinalInput.value = amountFinal.toFixed(2);
        });
    });
JS;

	return "<script>$script</script>";
}

/**
 * @param $user_id
 *
 * @return string
 *
 * @since version
 */
function header($user_id): string
{
	$sa = settings('ancillaries');

	$user = user($user_id);
	$account_type = $user->account_type;

	$currency = $sa->currency;
	$cybercharge = $sa->cybercharge;
	$processing_fee = $sa->processing_fee;
	$min_bal_usd = $sa->{$account_type . '_min_bal_usd'};

	$processing_fee_format = number_format($processing_fee, 2);

	$minimum_withdraw = $sa->{$account_type . '_min_withdraw'};

	$minimum_withdraw_format = number_format($minimum_withdraw, 2);

	$pending = user_payout_pending($user_id);

	$pending_requests = 0;

	if ($pending) {
		foreach ($pending as $requested) {
			$pending_requests += $requested->amount;
		}
	}

	$max_withdraw = ($user->payout_transfer - $pending_requests) * (1 - $cybercharge / 100) -
		$processing_fee - $min_bal_usd;

	$max_withdraw_format = number_format($max_withdraw, 2);

	$cybercharge_note = "$cybercharge% cybercharge";
	$processing_fee_note = "+ $processing_fee_format $currency processing fee";

	$str = '<p>';

	if ($cybercharge || $processing_fee) {
		$str .= <<<HTML
			Payouts are subject to $cybercharge_note $processing_fee_note per withdrawal transaction, and is non-refundable.
		HTML;
	}

	if ($minimum_withdraw > 0 || $max_withdraw > 0) {
		$str .= $max_withdraw ? " Maximum request is $max_withdraw_format $currency" : '';
		$str .= $minimum_withdraw ? " and Minimum request is $minimum_withdraw_format $currency." : '';
	}

	$str .= '</p>';

	return $str;
}

// /**
//  * @param $user_id
//  *
//  * @return string
//  *
//  * @since version
//  */
// function view_form_old($user_id): string
// {
// 	$settings_ancillaries = settings('ancillaries');

// 	$currency = $settings_ancillaries->currency;
// 	$cybercharge = $settings_ancillaries->cybercharge;
// 	$processing_fee = $settings_ancillaries->processing_fee;

// 	$user = user($user_id);

// 	$pending = user_payout_pending($user_id);

// 	$pending_requests = 0;

// 	if ($pending) {
// 		foreach ($pending as $requested) {
// 			$pending_requests += $requested->amount;
// 		}
// 	}

// 	$str = '<form method="post" onsubmit="submit.disabled = true; return true;">
//         <table class="category table table-striped table-bordered table-hover">
//             <tr>
//                 <td style="width: 150px">
//                     <div style="text-align: right"><strong>Balance</strong>:</div>
//                 </td>';

// 	$max_withdraw = (($user->balance - $pending_requests) * (1 - $cybercharge / 100) -
// 		$processing_fee - $settings_ancillaries->{$user->account_type . '_min_bal_usd'});

// 	$str .= '<td>' . number_format($user->balance, 2) . ' ' . $currency . '<span
//                 style="float:right;">Max. Withdraw: ' . number_format(
// 		($max_withdraw > 0 ? $max_withdraw : 0),
// 		2
// 	) . ' ' . $currency . '</span>
//     </td>
//     </tr>
//     <tr>
//         <td>
//             <div style="text-align: right"><strong><label for="amount">Amount Requested:</label></strong></div>
//         </td>
//         <td><input style="text-align: center" type="text" name="amount" id="amount"> ' . $currency . '</td>
//     </tr>
//     <tr>
//         <td colspan="2"><strong>Service Charge</strong></td>
//     </tr>
//     <tr>
//         <td>
//             <div style="text-align: right"><label for="tax">Cybercharge:</label></div>
//         </td>
//         <td><input type="text" name="tax" style="text-align: center" id="tax" value="0.00"
//                    readonly> ' . $currency .
// 		'</td>
//     </tr>
//     <tr>
//         <td>
//             <div style="text-align: right"><label for="fee">Processing Fee:</label></div>
//         </td>
//         <td><input type="text" id="fee" style="text-align: center"
//                    value="' . number_format($processing_fee, 2) . '" readonly> ' . $currency .
// 		'</td>
//     </tr>
//     <tr>
//         <td>
//             <div style="text-align: right"><label for="total_deductions">Total Deductions:</label></div>
//         </td>
//         <td>
//             <input type="text" style="text-align: center" readonly="readonly" name="total_deductions" id="total_deductions"> ' .
// 		$currency . '</td>
//     </tr>';

// 	$str .= (settings('plans')->trading ? '<tr>
//         <td><strong><label for="amount_final">Final amount:</strong></label></td>
//         <td>
//             <input type="text" readonly="readonly" name="amount_final" id="amount_final" style="text-align: center"> BTC
//         </td>
//     </tr>' : '<input type="hidden" readonly="readonly" name="amount_final" id="amount_final">');
// 	$str .= '<tr>
//         <td colspan="2">
//             <div style="text-align: center"><input type="submit" name="submit" value="Submit Request"
//                                        class="uk-button uk-button-primary"></div>
//         </td>
//     </tr>
//     </table>
//     </form>';

// 	return $str;
// }

/**
 * @param $uid
 *
 * @return mixed|null
 *
 * @since version
 */
function user_payouts($uid)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_users u ' .
		'INNER JOIN network_withdrawals w ' .
		'WHERE u.id = w.user_id ' .
		'AND w.withdrawal_id = ' . $db->quote($uid)
	)->loadObject();
}

/**
 * @param $uid
 * @param $user_id
 *
 *
 * @since version
 */
function request_cancel($uid, $user_id)
{
	$app = application();

	$result = user_payouts($uid);

	if ($result->user_id !== $user_id) {

		$app->enqueueMessage('Transaction Invalid!', 'error');
		$app->redirect(Uri::root(true) . '/' . sef(113));
	}

	$db = db();

	try {
		$db->transactionStart();

		payout_cancel($uid);

		update_user_payout_cancel($result->amount, $result->user_id);

		logs_cancel($uid);

		$db->transactionCommit();
	} catch (Exception $e) {
		$db->transactionRollback();

		ExceptionHandler::render($e);
	}

	$app->enqueueMessage('Payout Request Cancelled!', 'info');
	$app->redirect(Uri::root(true) . '/' . sef(113));
}

/**
 * @param $uid
 *
 *
 * @since version
 */
function payout_cancel($uid)
{
	delete(
		'network_withdrawals',
		['withdrawal_id = ' . db()->quote($uid)]
	);
}

/**
 * @param $amount
 * @param $user_id
 *
 * @since version
 */
function update_user_payout_cancel($amount, $user_id)
{
	$db = db();

	update(
		'network_users',
		[
			'payout_transfer = payout_transfer + ' . $amount,
			'payout_total = payout_total - ' . $amount
		],
		['id = ' . $db->quote($user_id)]
	);
}

/**
 * @param $uid
 *
 *
 * @since version
 */
function logs_cancel($uid)
{
	$result = user_payouts($uid);

	$db = db();

	$activity = '<b>Payout Request of ' . number_format($result->amount, 2) .
		settings('ancillaries')->currency . ' was cancelled by </b><a href="' . sef(44) . qs() . 'uid=' .
		$result->id . '">' . $result->username . '</a>' . '.';

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
			$db->quote($result->id),
			$db->quote($result->id),
			$db->quote(1),
			$db->quote($activity),
			$db->quote(time())
		]
	);
}

/**
 * @param $user_id
 *
 * @return string
 *
 * @since version
 */
function view_pending($user_id): string
{
	$table_pending = table_pending($user_id);

	return <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-hourglass me-1"></i>
				Pending Requests
			</div>
			<div class="card-body">
				<table id="datatablesSimple">
					$table_pending
				</table>
			</div>
		</div>
	HTML;
}

function table_pending($user_id)
{
	$sa = settings('ancillaries');
	$currency = $sa->currency;

	$row_pending = row_pending($user_id);

	$str = <<<HTML
		<thead>
			<tr>
				<th>Date</th>
				<th>Amount ($currency)</th>
				<th>Deductions ($currency)</th>
				<th>Final Amount ($currency)</th>
				<th>Method</th>
				<th>Action</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Date</th>
				<th>Amount ($currency)</th>
				<th>Deductions ($currency)</th>
				<th>Final Amount ($currency)</th>
				<th>Method</th>
				<th>Action</th>
			</tr>
		</tfoot>
		<tbody>
			$row_pending						
		</tbody>
	HTML;

	return $str;
}

function row_pending($user_id)
{
	$pendings = user_payout_pending($user_id);

	$str = '';

	if (empty($pendings)) {
		$str .= <<<HTML
			<tr>
				<td>n/a</td>
				<td>0</td>
				<td>0</td>
				<td>0</td>
				<td>n/a</td>
				<td>n/a</td>							
			</tr>					
		HTML;
	} else {
		foreach ($pendings as $pending) {
			// $payout_method = payout_method(user($user_id));

			$str .= '<tr>';
			$str .= '<td>' . date('M j, Y - g:i A', $pending->date_requested) . '</td>';
			$str .= '<td>' . number_format($pending->amount, 2) . '</td>';
			$str .= '<td>' . number_format($pending->deductions_total, 2) . '</td>';
			$str .= '<td>' . number_format($pending->amount_final, 2) . '</td>';
			$str .= '<td>' . strtoupper($pending->method) . '</td>';
			$str .= '<td><a href="' . sef(113) . qs() . 'uid=' . $pending->withdrawal_id . '&cancel=1">Cancel</a></td>';
			$str .= '</tr>';
		}
	}

	return $str;
}