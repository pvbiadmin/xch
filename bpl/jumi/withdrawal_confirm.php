<?php

namespace BPL\Jumi\Withdrawal_Confirm;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
require_once 'bpl/mods/payout_method.php';
require_once 'bpl/menu.php';
require_once 'bpl/mods/query.php';
require_once 'bpl/mods/mailer.php';
require_once 'bpl/mods/helpers.php';

use Exception;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Exception\ExceptionHandler;

// use function BPL\Mods\Payout_Method\main as payout_method;

use function Templates\SB_Admin\Tmpl\Master\main as master;

use function BPL\Menu\admin as menu_admin;
use function BPL\Menu\manager as menu_manager;

use function BPL\Mods\Database\Query\insert;
use function BPL\Mods\Database\Query\update;
use function BPL\Mods\Database\Query\delete;

use function BPL\Mods\Mailer\main as send_mail;

use function BPL\Mods\Url_SEF\sef;
use function BPL\Mods\Url_SEF\qs;

use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\application;
use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\settings;
use function BPL\Mods\Helpers\input_get;
use function BPL\Mods\Helpers\user;

$content = main();

master($content);

/**
 *
 *
 * @since version
 */
function main()
{
	$usertype = session_get('usertype');
	// $admintype = session_get('admintype');
	// $account_type = session_get('account_type');
	// $user_id = session_get('user_id');
	// $username = session_get('username');
	$uid = input_get('uid');
	$mode = input_get('mode');
	$final = input_get('final');

	page_validate($usertype);

	// $str = menu($usertype, $admintype, $account_type, $user_id, $username);

	if (!($uid !== '' && (int) $final !== 1)) {
		if ((int) $mode === 1) {
			approve_request($uid);
		}

		if ((int) $mode === 2) {
			deny_request($uid);
		}
	}

	$view_approve_withdrawals = view_approve_withdrawals($uid, $final, $mode);

	$str = <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Pending Payout Requests</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">Confirm Payout Requests</li>
		</ol>
		$view_approve_withdrawals
	</div>
	HTML;

	return $str;
}

function view_approve_withdrawals($uid, $final, $mode): string
{
	$view_form_card = '';

	if ($uid !== '' && (int) $final !== 1) {
		$view_form_approve = view_form_approve($uid, $mode);

		$view_form_card = <<<HTML
			<div class="card mb-4">
				$view_form_approve
			</div>
		HTML;
	}

	$notifications = notifications();

	$view_withdrawals_pending = view_withdrawals_pending();

	return <<<HTML
    <div class="container-fluid px-4">        
		<div class="row justify-content-center">
			<div class="col-lg-8">
				$notifications
				$view_form_card
        	</div>		
		</div>
        $view_withdrawals_pending
    </div>	
HTML;
}

function view_withdrawals_pending(): string
{
	$table_withdrawals_pending = table_withdrawals_pending();

	return <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-hourglass me-1"></i>
				Pending Payout Requests
			</div>
			<div class="card-body">
				<table id="datatablesSimple">
					$table_withdrawals_pending
				</table>
			</div>
		</div>
	HTML;
}

function table_withdrawals_pending()
{
	$sa = settings('ancillaries');

	$currency = $sa->currency;

	$row_withdrawals_pending = row_withdrawals_pending();

	$str = <<<HTML
		<thead>
			<tr>
	            <th>Date Requested</th>
	            <th>Username</th>
	            <th>Balance ($currency)</th>
	            <th>Amount ($currency)</th>
	            <th>Method</th>
	            <th>Actions</th>
	        </tr>
		</thead>
		<tfoot>
			<tr>
	            <th>Date Requested</th>
	            <th>Username</th>
	            <th>Balance ($currency)</th>
	            <th>Amount ($currency)</th>
	            <th>Method</th>
	            <th>Actions</th>
	        </tr>
		</tfoot>
		<tbody>
			$row_withdrawals_pending						
		</tbody>
	HTML;

	return $str;
}

/**
 *
 *
 * @since version
 */
function row_withdrawals_pending(): string
{
	// $currency = settings('ancillaries')->currency;

	$results = user_withdrawals_pending();

	$str = '';

	// if (!empty($results)) {
	// 	$str .= '<table class="category table table-striped table-bordered table-hover">
	//         <thead>
	//         <tr>
	//             <th>Date Requested</th>
	//             <th>Username</th>
	//             <th>Balance (' . $currency . ')</th>
	//             <th>Amount (' . $currency . ')</th>
	//             <th>Method</th>
	//             <th>Actions</th>
	//         </tr>
	//         </thead>
	//         <tbody>';

	foreach ($results as $result) {
		$str .= '<tr>
				<td>' . date('M j, Y - g:i A', $result->date_requested) . '</td>
				<td><a href="' . sef(44) . qs() . 'uid=' . $result->id . '">' . $result->username . '</td>
				<td>' . number_format($result->balance, 2) . '</td>
				<td>' . number_format($result->amount, 2) . '</td>
				<td>' . ucwords($result->method) . '</td>
				<td>';

		$link_mode_approve = sef(112) . qs() . 'uid=' . $result->withdrawal_id . '&mode=1';
		$link_mode_deny = sef(112) . qs() . 'uid=' . $result->withdrawal_id . '&mode=2';

		$str .= '<a href="' . $link_mode_approve . '" type="button" class="btn btn-primary btn-sm">Approve</a>&nbsp;';
		$str .= '<a href="' . $link_mode_deny . '" type="button" class="btn btn-secondary btn-sm">Deny</a>';

		// $str .= '<div class="dropdown">
		// 		<button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
		// 		  Select Action
		// 		</button>
		// 		<ul class="dropdown-menu">
		// 		  <li><a class="dropdown-item" href="' . $link_mode_approve . '">Approve</a></li>
		// 		  <li><a class="dropdown-item" href="' . $link_mode_deny . '">Deny</a></li>				  
		// 		</ul>
		// 	  </div>';

		// $str .= '<div class="uk-button-group">
		//             <button class="uk-button uk-button-primary">Select</button>
		//             <div class="" data-uk-dropdown="{mode:\'click\'}">
		//                 <button class="uk-button uk-button-primary"><i class="uk-icon-caret-down"></i></button>
		//                 <div style="" class="uk-dropdown uk-dropdown-small">
		//                     <ul class="uk-nav uk-nav-dropdown">
		//                         <li>
		//                             <a href="' . sef(112) . qs() . 'uid=' .
		// 	$result->withdrawal_id . '&mode=1">Approve</a>
		//                         </li>
		//                         <li>
		//                             <a href="' . sef(112) . qs() . 'uid=' .
		// 	$result->withdrawal_id . '&mode=2">Deny</a>
		//                         </li>
		//                     </ul>
		//                 </div>
		//             </div>
		//         </div>';

		$str .= '
					</td>
				</tr>';
	}

	// 	$str .= '</tbody>
	// 		</table>';
	// } else {
	// 	$str .= '<hr><p>No pending requests yet.</p>';
	// }

	return $str;
}

function view_form_approve($uid, $mode): string
{
	// $sa = settings('ancillaries');

	// $efund_name = $sa->efund_name;

	$header = '<i class="fas fa-x me-1"></i> Deny Request';

	if ((int) $mode === 1) {
		$header = '<i class="fas fa-check me-1"></i> Approve Request';
	}

	$table_form_approve = table_form_approve($uid, $mode);

	return <<<HTML
		<div class="card mb-4">
			<div class="card-header">				
				$header
			</div>
			<div class="card-body">
				<form method="post">
					<input type="hidden" name="final" value="1">
					<input type="hidden" name="uid" value="$uid">
					<input type="hidden" name="mode" value="$mode">
					<table class="table table-bordered">
						$table_form_approve
					</table>
				</form>
			</div>
		</div>
HTML;
}

function table_form_approve($uid, $mode)
{
	$sa = settings('ancillaries');
	$currency = $sa->currency;

	$row_form_approve = row_form_approve($uid, $mode);

	$str = <<<HTML
		<thead>
			<tr>
				<th>Date Requested</th>
				<th>Username</th>
				<th>Balance ($currency)</th>
				<th>Amount ($currency)</th>
				<th>Method</th>
				<th>Action</th>
			</tr>
		</thead>		
		<tbody>
			$row_form_approve						
		</tbody>
HTML;

	return $str;
}

/**
 * @param $uid
 * @param $mode
 *
 * @return string
 *
 * @since version
 */
function row_form_approve($uid, $mode): string
{
	// $currency = settings('ancillaries')->currency;

	$withdrawal = user_payouts($uid);

	$str = '';

	// if ((int) $mode === 1) {
	// 	$str .= '<h3>Approve Request</h3>';
	// } else {
	// 	$str .= '<h3>Deny Request</h3>';
	// }

	// $str .= '<form method="post">
	//         <input type="hidden" name="final" value="1">
	//         <input type="hidden" name="uid" value="' . $uid . '">
	//         <input type="hidden" name="mode" value="' . $mode . '">
	//         <table class="category table table-striped table-bordered table-hover">
	//             <thead>
	//             <tr>
	//                 <th>Date Requested</th>
	//                 <th>Username</th>
	//                 <th>Balance (' . $currency . ')</th>
	//                 <th>Amount (' . $currency . ')</th>
	//                 <th>Method</th>
	//                 <th>Confirm</th>
	//             </tr>
	//             </thead>
	//             <tbody>';

	$str .= '<tr>
				<td>' . date('M j, Y - g:i A', $withdrawal->date_requested) . '</td>
				<td><a href="' . sef(44) . qs() . 'uid=' .
		$withdrawal->id . '">' . $withdrawal->username . '</a></td>' .
		'<td>' . number_format($withdrawal->balance, 2) . '</td>
				<td>' . number_format($withdrawal->amount, 2) .
		'</td>
				<td>' . ucwords($withdrawal->method) . '</td>
				<td><input type="submit" value="' . ((int) $mode === 1 ? 'Approve' : 'Deny') .
		'" name="submit" class="btn btn-primary"></td>
			</tr>';

	// $str .= '</tbody>
	//             </table>
	//         </form>';

	return $str;
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
 *
 *
 * @since version
 */
function page_validate($usertype)
{
	if ($usertype !== 'Admin' && $usertype !== 'manager') {
		application()->redirect(Uri::root(true) . '/' . sef(43));
	}
}

/**
 * @param $usertype
 * @param $admintype
 * @param $account_type
 * @param $user_id
 * @param $username
 *
 * @return string
 *
 * @since version
 */
function menu($usertype, $admintype, $account_type, $user_id, $username): string
{
	$str = '';

	switch ($usertype) {
		case 'Admin':
			$str .= menu_admin($admintype, $account_type, $user_id, $username);
			break;
		case 'manager':
			$str .= menu_manager();
			break;
	}

	return $str;
}

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
 *
 *
 * @since version
 */
function update_withdrawals($uid)
{
	$db = db();

	update(
		'network_withdrawals',
		['date_completed = ' . $db->quote(time())],
		['withdrawal_id = ' . $db->quote($uid)]
	);
}

/**
 * @param $uid
 *
 *
 * @since version
 */
function logs_approve_activity($uid)
{
	$db = db();

	$settings_ancillaries = settings('ancillaries');

	$currency = $settings_ancillaries->currency;

	$result = user_payouts($uid);

	$tax = ($result->amount * $settings_ancillaries->cybercharge / 100);

	$activity = '<b>Payout Confirmed: </b><a href="' . sef(44) . qs() . 'uid=' .
		$result->id . '">' . $result->username . '</a> has requested a payout of ' .
		number_format($result->amount, 2) . ' ' . $currency . '.<br>Cybercharge (' .
		$currency . '): ' . number_format($tax, 2) . '<br>Processing Fee (' .
		$currency . '): ' . number_format($settings_ancillaries->processing_fee, 2) . '.';

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
 * @return mixed|null
 *
 * @since version
 */
function user_transactions($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_transactions ' .
		'WHERE user_id = ' . $db->quote($user_id) .
		' ORDER BY transaction_id DESC'
	)->loadObject();
}

/**
 * @param $uid
 *
 *
 * @since version
 */
function logs_approve_transactions($uid)
{
	$db = db();

	$settings_ancillaries = settings('ancillaries');

	$currency = $settings_ancillaries->currency;

	$result = user_payouts($uid);

	$tax = ($result->amount * $settings_ancillaries->cybercharge / 100);

	$tmp = user_transactions($result->id);

	$details = 'Amount (' . $currency . '): ' . number_format($result->amount, 2) .
		'.<br>Cybercharge (' . $currency . '): ' . number_format($tax, 2) .
		'<br>Processing Fee (' . $currency . '): ' .
		number_format($settings_ancillaries->processing_fee, 2) . '.';

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
			$db->quote($result->id),
			$db->quote('Payout Confirmed'),
			$db->quote($details),
			$db->quote('-' . $result->amount),
			$db->quote($tmp->balance - $result->amount),
			$db->quote(time())
		]
	);
}

/**
 *
 * @return mixed|null
 *
 * @since version
 */
function payouts()
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_payouts ' .
		'ORDER BY payout_id DESC'
	)->loadObject();
}

/**
 * @param $uid
 *
 * @param $insert_id
 *
 * @since version
 */
function logs_payouts($uid, $insert_id)
{
	$db = db();

	$result = user_payouts($uid);

	$tax = ($result->amount * settings('ancillaries')->cybercharge / 100);

	$tmp = payouts();

	insert(
		'network_payouts',
		[
			'transaction_id',
			'amount',
			'payout_date',
			'payout_total',
			'amount_tax',
			'total_tax'
		],
		[
			$db->quote($insert_id),
			$db->quote($result->amount),
			$db->quote(time()),
			$db->quote(($tmp->payout_total ?? 0) + $result->amount),
			$db->quote($tax),
			$db->quote(($tmp->total_tax ?? 0) + $tax)
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
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_income ' .
		'ORDER BY income_id DESC'
	)->loadObject();
}

/**
 * @param $insert_id
 *
 *
 * @since version
 */
function logs_income_admin($insert_id)
{
	$db = db();

	$processing_fee = settings('ancillaries')->processing_fee;

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
			$db->quote((income_admin()->income_total ?? 0) + $processing_fee),
			$db->quote(time())
		]
	);
}

/**
 * @param $uid
 *
 *
 * @since version
 */
function logs_approve($uid)
{
	logs_approve_activity($uid);
	logs_approve_transactions($uid);

	$insert_id = db()->insertid();

	logs_payouts($uid, $insert_id);

	logs_income_admin($insert_id);
}

/**
 * @param $uid
 *
 *
 * @since version
 */
function approve_request($uid)
{
	$db = db();

	$settings_ancillaries = settings('ancillaries');

	$result = user_payouts($uid);

	$message = 'Username: ' . $result->username . '<br>
			Full Name: ' . $result->fullname . '<br>
			Email: ' . $result->email . '<br>
			Contact Number: ' . $result->contact . '<br>
			Amount (' . $settings_ancillaries->currency . '): ' . number_format($result->amount, 2) . '<br>
			Method: ' . $result->method;

	try {
		$db->transactionStart();

		update_withdrawals($uid);

		logs_approve($uid);

		send_mail($message, 'Payout Confirmed', [$result->email]);

		$db->transactionCommit();
	} catch (Exception $e) {
		$db->transactionRollback();

		ExceptionHandler::render($e);
	}

	//	send_mail($uid);

	$app = application();

	// application()->redirect(
	// 	Uri::root(true) . '/' . sef(112),
	// 	'Payout Request Confirmed!',
	// 	'success'
	// );

	$app->enqueueMessage('Payout Request Confirmed!', 'success');
	$app->redirect(Uri::current());
}

/**
 * @param $uid
 *
 *
 * @since version
 */
function delete_request($uid)
{
	delete(
		'network_withdrawals',
		['withdrawal_id = ' . db()->quote($uid)]
	);
}

/**
 * @param $uid
 *
 *
 * @since version
 */
function logs_deny($uid)
{
	$db = db();

	$result = user_payouts($uid);

	$activity = '<b>Payout Denied: </b><a href="' . sef(44) . qs() . 'uid=' . $result->id . '">' .
		$result->username . '</a>,	' . number_format($result->amount, 2) . ' ' .
		settings('ancillaries')->currency . '. Contact for more info.';

	$user = user($result->user_id);

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
			$db->quote($result->user_id),
			$db->quote($user->sponsor_id),
			$db->quote(1),
			$db->quote($activity),
			$db->quote(time())
		]
	);
}

/**
 * @param $uid
 *
 *
 * @since version
 */
function deny_request($uid)
{
	$db = db();

	try {
		$db->transactionStart();

		delete_request($uid);

		logs_deny($uid);

		$db->transactionCommit();
	} catch (Exception $e) {
		$db->transactionRollback();

		ExceptionHandler::render($e);
	}

	$app = application();

	// application()->redirect(
	// 	Uri::root(true) . '/' . sef(112),
	// 	'Payout Request Denied!',
	// 	'notice'
	// );

	$app->enqueueMessage('Payout Request Denied!', 'info');
	$app->redirect(Uri::current());
}

/**
 *
 * @return array|mixed
 *
 * @since version
 */
function user_withdrawals_pending()
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_users u ' .
		'INNER JOIN network_withdrawals w ' .
		'WHERE u.id = w.user_id ' .
		'AND w.date_completed = ' . $db->quote(0) .
		' ORDER BY w.withdrawal_id ASC'
	)->loadObjectList();
}