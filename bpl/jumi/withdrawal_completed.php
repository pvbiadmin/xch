<?php

namespace BPL\Jumi\Withdrawal_Completed;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
require_once 'bpl/menu.php';
require_once 'bpl/mods/payout_method.php';
require_once 'bpl/mods/helpers.php';

use function BPL\Menu\admin as menu_admin;
use function BPL\Menu\member as menu_member;
use function BPL\Menu\manager as menu_manager;

use function Templates\SB_Admin\Tmpl\Master\main as master;

// use function BPL\Mods\Payout_Method\main as payout_method;

use function BPL\Mods\Url_SEF\sef;
use function BPL\Mods\Url_SEF\qs;

use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\page_validate;
use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\settings;
// use function BPL\Mods\Helpers\page_reload;
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
	$usertype = session_get('usertype');
	// $account_type = session_get('account_type');
	// $merchant_type = session_get('merchant_type');
	$user_id = session_get('user_id');
	// $admintype = session_get('admintype');

	page_validate();

	// $str = menu($usertype, $admintype, $account_type, $username, $merchant_type, $user_id);

	// $str = '';

	// $str .= page_reload();

	$str = live_reload(true);

	$view_withdrawal_completed = view_withdrawal_completed($user_id, $usertype, true);

	$str .= <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Completed Payouts</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">List of Completed Payouts</li>
		</ol>				
		$view_withdrawal_completed
	</div>
HTML;

	return $str;
}

/**
 * @param $user_id
 * @param $usertype
 *
 * @return string
 *
 * @since version
 */
function view_withdrawal_completed($user_id, $usertype, $counter = false): string
{
	$str = '';

	if ($usertype === 'Admin' || $usertype === 'manager') {
		$str .= view_withdrawal_completed_admin($counter);
	} else {
		$str .= view_withdrawal_completed_user($user_id, $counter);
	}

	return $str;
}

/**
 *
 * @return string
 *
 * @since version
 */
function view_withdrawal_completed_admin($counter): string
{
	$counter_span = '';

	if ($counter) {
		$counter_span = '<span id="counter" style="float:right">00:00:00</span>';
	}

	$table_withdrawal_completed_user = table_withdrawal_completed_admin();

	return <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-table me-1"></i>
				Completed Payouts{$counter_span}
			</div>
			<div class="card-body">
				<table id="datatablesSimple">
					$table_withdrawal_completed_user
				</table>
			</div>
		</div>
	HTML;
}

function table_withdrawal_completed_admin(): string
{
	$sa = settings('ancillaries');

	$currency = $sa->currency;

	$row_withdrawal_completed_admin = row_withdrawal_completed_admin();

	$str = <<<HTML
		<thead>
			<tr>
				<th>Date</th>
				<th>Username</th>
				<th>Balance ($currency)</th>
				<th>Amount ($currency)</th>
				<th>Deductions ($currency)</th>
				<th>Method</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Date</th>
				<th>Username</th>
				<th>Balance ($currency)</th>
				<th>Amount ($currency)</th>
				<th>Deductions ($currency)</th>
				<th>Method</th>
			</tr>
		</tfoot>
		<tbody>
			$row_withdrawal_completed_admin						
		</tbody>
	HTML;

	return $str;
}

function row_withdrawal_completed_admin(): string
{
	$results = withdrawals_completed();

	$str = '';

	if (!empty($results)) {
		foreach ($results as $result) {
			$str .= '<tr>';
			$str .= '<td>' . date('M j, Y - g:i A', $result->date_requested) . '</td>';
			$str .= '<td><a href="' . sef(44) . qs() . 'uid=' . $result->id . '">' . $result->username . '</a></td>';
			$str .= '<td>' . number_format($result->balance, 2) . '</td>';
			$str .= '<td>' . number_format($result->amount, 2) . '</td>';
			$str .= '<td>' . number_format($result->deductions_total, 2) . '</td>';
			$str .= '<td>' . ucwords($result->method) . '</td>';
			$str .= '</tr>';
		}
	} else {
		$str .= 'No entries found.';
	}

	return $str;
}

/**
 * @param $user_id
 *
 * @return string
 *
 * @since version
 */
function view_withdrawal_completed_user($user_id, $counter): string
{
	$counter_span = '';

	if ($counter) {
		$counter_span = '<span id="counter" style="float:right">00:00:00</span>';
	}

	$table_withdrawal_completed_user = table_withdrawal_completed_user($user_id);

	return <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-table me-1"></i>
				Completed Payouts{$counter_span}
			</div>
			<div class="card-body">
				<table id="datatablesSimple">
					$table_withdrawal_completed_user
				</table>
			</div>
		</div>
	HTML;
}

function table_withdrawal_completed_user($user_id)
{
	$sa = settings('ancillaries');

	$currency = $sa->currency;

	$row_withdrawal_completed_user = row_withdrawal_completed_user($user_id);

	$str = <<<HTML
		<thead>
			<tr>
				<th>Date</th>
				<th>Amount ($currency)</th>
				<th>Final Deducted ($currency)</th>
				<th>Method</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Date</th>
				<th>Amount ($currency)</th>
				<th>Final Deducted ($currency)</th>
				<th>Method</th>
			</tr>
		</tfoot>
		<tbody>
			$row_withdrawal_completed_user						
		</tbody>
	HTML;

	return $str;
}

function row_withdrawal_completed_user($user_id)
{
	$sa = settings('ancillaries');

	$processing_fee = $sa->processing_fee;
	$cybercharge = $sa->cybercharge;

	$results = user_withdrawals_completed($user_id);

	$str = '';

	if (!empty($results)) {
		foreach ($results as $result) {
			$str .= '<tr>
		                <td>' . date('M j, Y - g:i A', $result->date_requested) . '</td>
		                <td>' . number_format($result->amount, 2) . '</td>
		                <td>' . number_format($result->amount - (($result->amount * $cybercharge / 100) + $processing_fee), 2) . '</td>
		                <td>' . ucwords($result->method) . '</td>
		            </tr>';
		}
	} else {
		$str .= 'No entries found.';
	}

	return $str;
}

/**
 *
 * @return array|mixed
 *
 * @since version
 */
function withdrawals_completed()
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_users u, network_withdrawals w ' .
		'WHERE u.id = w.user_id ' .
		'AND w.date_completed <> ' . $db->quote(0) .
		' ORDER BY w.withdrawal_id DESC'
	)->loadObjectList();
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
 *
 * @return array|mixed
 * @since version
 */
function user_withdrawals_completed($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_users u ' .
		'INNER JOIN network_withdrawals w ' .
		'WHERE u.id = w.user_id ' .
		'AND w.date_completed <> ' . $db->quote(0) .
		' AND w.user_id = ' . $db->quote($user_id) .
		' ORDER BY w.withdrawal_id DESC;'
	)->loadObjectList();
}