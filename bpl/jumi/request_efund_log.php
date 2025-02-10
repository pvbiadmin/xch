<?php

namespace BPL\Jumi\Request_Efund_Log;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
// require_once 'bpl/menu.php';
require_once 'bpl/mods/helpers.php';

// use function BPL\Menu\admin as menu_admin;
// use function BPL\Menu\member as menu_member;
// use function BPL\Menu\manager as menu_manager;

use function Templates\SB_Admin\Tmpl\Master\main as master;

use function BPL\Mods\Url_SEF\sef;
use function BPL\Mods\Url_SEF\qs;

use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\settings;
use function BPL\Mods\Helpers\page_validate;
// use function BPL\Mods\Helpers\page_reload;
use function BPL\Mods\Helpers\user;
use function BPL\Mods\Helpers\live_reload;

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
	$admintype = session_get('admintype');
	// $account_type = session_get('account_type');
	// $merchant_type = session_get('merchant_type');
	$user_id = session_get('user_id');
	$usertype = session_get('usertype');

	page_validate();

	// $str = menu($usertype, $admintype, $account_type, $username, $merchant_type, $user_id);

	$sa = settings('ancillaries');

	$efund_name = $sa->efund_name;

	$view_logs = view_logs($user_id, $usertype, $admintype, true);

	$str = live_reload(true);

	$str .= <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">$efund_name Request Logs</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">List of $efund_name Requests</li>
		</ol>				
		$view_logs
	</div>
	HTML;

	return $str;
}

function view_logs($user_id, $usertype, $admintype, $counter): string
{
	// $str = page_reload();

	// $str .= '<h1>' . settings('ancillaries')->efund_name . ' Request Logs</h1>';

	$str = '';

	if ($usertype === 'Admin' && $admintype === 'Super') {
		$str .= view_admin_efund_request_logs($counter);
	} else {
		$str .= view_user_efund_request_logs($user_id, $counter);
	}

	return $str;
}

function view_admin_efund_request_logs($counter)
{
	$counter_span = '';

	if ($counter) {
		$counter_span = '<span id="counter" style="float:right">00:00:00</span>';
	}

	$table_admin_efund_request_logs = table_admin_efund_request_logs();

	return <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-table me-1"></i>
				Sponsored Accounts{$counter_span}
			</div>
			<div class="card-body">
				<table id="datatablesSimple">
					$table_admin_efund_request_logs
				</table>
			</div>
		</div>
	HTML;
}

function table_admin_efund_request_logs()
{
	$row_admin_efund_request_logs = row_admin_efund_request_logs();

	$str = <<<HTML
		<thead>
			<tr>
				<th>Date</th>
                <th>User</th>
                <th>Details</th>
				<th>Amount</th>
				<th>Total Requests</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Date</th>
                <th>User</th>
                <th>Details</th>
				<th>Amount</th>
				<th>Total Requests</th>
			</tr>
		</tfoot>
		<tbody>
			$row_admin_efund_request_logs						
		</tbody>
HTML;

	return $str;
}

/**
 *
 * @return string
 *
 * @since version
 */
function row_admin_efund_request_logs(): string
{
	$currency = settings('ancillaries')->currency;

	// $efund_name = settings('ancillaries')->efund_name;

	$result = efund_request_transactions();

	$str = '';

	// if (!empty($result)) {
	// 	$str .= '<table class="category table table-striped table-bordered table-hover">
	//         <thead>
	//         <tr>
	//             <th>Date</th>
	//             <th>User</th>
	//             <th>Details</th>';
	// 	$str .= '<th>Amount</th>';
	// 	$str .= '<th>Total Requests</th>
	//         </tr>
	//         </thead>
	//         <tbody>';

	foreach ($result as $log) {
		$user = user($log->user_id);

		$str .= '<tr>
                    <td>' . date('M j, Y - g:i A', $log->request_date) . '</td>
                    <td><a href="' . sef(44) . qs() .
			'uid=' . $user->id . '">' . $user->username . '</a></td>
                    <td style="table-layout: fixed; width: 300px">' . $log->details . '</td>
                    <td>' . number_format($log->amount, 2) . ' ' . $currency . '</td>
                    <td>' . number_format($log->request_total, 2) . ' ' . $currency . '</td>
                </tr>';
	}

	// 	$str .= '</tbody>
	//     	</table>';
	// } else {
	// 	$str .= '<hr><p>No ' . settings('ancillaries')->efund_name . ' requests yet.</p>';
	// }

	return $str;
}

function view_user_efund_request_logs($user_id, $counter)
{
	$counter_span = '';

	if ($counter) {
		$counter_span = '<span id="counter" style="float:right">00:00:00</span>';
	}

	$sa = settings('ancillaries');

	$currency = $sa->currency;

	$table_user_efund_request_logs = table_user_efund_request_logs($user_id);

	$result = user_efund_request_transactions($user_id);

	$total = 0;

	foreach ($result as $log) {
		$total += $log->amount;
	}

	$total_format = number_format($total, 2);

	return <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-table me-1"></i>
				Request Logs{$counter_span}
			</div>
			<div class="card-body">
				<table id="datatablesSimple">
					$table_user_efund_request_logs
				</table>
			</div>
			<div class="card-footer">
				Total Request: $total_format $currency
			</div>
		</div>
	HTML;
}

function table_user_efund_request_logs($user_id)
{
	$row_user_efund_request_logs = row_user_efund_request_logs($user_id);

	$str = <<<HTML
		<thead>
			<tr>
				<th>Date</th>
				<th>Details</th>
				<th>Amount</th>				
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Date</th>
				<th>Details</th>
				<th>Amount</th>				
			</tr>
		</tfoot>
		<tbody>
			$row_user_efund_request_logs						
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
function row_user_efund_request_logs($user_id): string
{
	$sa = settings('ancillaries');

	// $efund_name = $sa->efund_name;

	$currency = $sa->currency;

	$result = user_efund_request_transactions($user_id);

	$str = '';

	// if ($result) {
	// 	$str .= '<h1>' . $efund_name . ' Activity</h1>
	//     <table class="category table table-striped table-bordered table-hover">
	//         <thead>
	//         <tr>
	//             <th>Date</th>
	//             <th>Details</th>';
	// 	$str .= '<th>Amount</th>';
	// 	$str .= '</tr>
	//         </thead>
	//         <tbody>';

	$total = 0;

	foreach ($result as $log) {
		$str .= '<tr>
                    <td>' . date('M j, Y - g:i A', $log->request_date) . '</td>
                    <td style="table-layout: fixed; width: 300px">' . $log->details . '</td>
                    <td>' . number_format($log->amount, 2) . '' . $currency . '</td>
                </tr>';

		$total += $log->amount;
	}

	// 	$str .= '</tbody>
	//     </table>
	//     <p><strong>Total ' . $efund_name . ' Request: </strong>' . number_format($total, 8) . ' ' . $efund_name;
	// } else {
	// 	$str .= '<hr><p>No ' . $efund_name . ' requests yet.</p>';
	// }

	return $str;
}

// /**
//  *
//  * @param $usertype
//  * @param $admintype
//  * @param $account_type
//  * @param $username
//  * @param $merchant_type
//  * @param $user_id
//  *
//  * @return string
//  *
//  * @since version
//  */
// function menu($usertype, $admintype, $account_type, $username, $merchant_type, $user_id): string
// {
// 	$str = '';

// 	switch ($usertype) {
// 		case 'Admin':
// 			$str .= menu_admin($admintype, $account_type, $user_id, $username);
// 			break;
// 		case 'Member':
// 			$str .= menu_member($account_type, $username, $user_id);
// 			break;
// 		case 'manager':
// 			$str .= menu_manager();
// 			break;
// 	}

// 	return $str;
// }

/**
 *
 * @return array|mixed
 *
 * @since version
 */
function efund_request_transactions()
{
	return db()->setQuery(
		'SELECT * ' .
		'FROM network_efund_requests r ' .
		'INNER JOIN network_transactions t ' .
		'ON r.transaction_id = t.transaction_id ' .
		'ORDER BY r.request_id DESC'
	)->loadObjectList();
}

/**
 *
 * @return array|mixed
 *
 * @since version
 */
function user_efund_request_transactions($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_efund_requests r ' .
		'INNER JOIN network_transactions t ' .
		'ON r.transaction_id = t.transaction_id ' .
		'AND t.user_id = ' . $db->quote($user_id) .
		' ORDER BY r.request_id DESC'
	)->loadObjectList();
}