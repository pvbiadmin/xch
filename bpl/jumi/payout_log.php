<?php

namespace BPL\Jumi\Payout_Log;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
require_once 'bpl/menu.php';
require_once 'bpl/mods/helpers.php';

use function BPL\Menu\admin as menu_admin;
use function BPL\Menu\manager as menu_manager;
use function BPL\Menu\member as menu_member;

use function Templates\SB_Admin\Tmpl\Master\main as master;

use function BPL\Mods\Url_SEF\qs;
use function BPL\Mods\Url_SEF\sef;

use function BPL\Mods\Helpers\db;
// use function BPL\Mods\Helpers\page_reload;
use function BPL\Mods\Helpers\page_validate;
use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\settings;
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
	$usertype = session_get('usertype');
	$admintype = session_get('admintype');
	$user_id = session_get('user_id');

	page_validate();

	// $str = menu($usertype, $admintype, $account_type, $username, $merchant_type, $user_id);

	$str = '';
	// $str .= page_reload();

	$str = live_reload(true);

	$str .= view_payouts($user_id, $usertype, $admintype);

	return $str;
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
 *
 * @return array|mixed
 *
 * @since version
 */
function payouts_admin()
{
	return db()->setQuery(
		'SELECT * ' .
		'FROM network_payouts p ' .
		'INNER JOIN network_transactions t ' .
		'WHERE p.transaction_id = t.transaction_id ' .
		'ORDER BY p.payout_id DESC'
	)->loadObjectList();
}

/**
 * @param $user_id
 *
 * @return array|mixed
 *
 * @since version
 */
function payouts_user($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_payouts p ' .
		'INNER JOIN network_transactions t ' .
		'WHERE p.transaction_id = t.transaction_id ' .
		'AND t.user_id = ' . $db->quote($user_id) .
		' ORDER BY p.payout_id DESC'
	)->loadObjectList();
}

/**
 * @param           $user
 *
 * @return string
 *
 * @since version
 */
function payout_method($user): string
{
	$payout_member = explode('|', $user->bank);

	switch ($payout_member[0]) {
		case 'bank':
			[, $bank_type, $bank_name, $bank_account] = $payout_member;

			$payout_method = 'Bank: [' . $bank_type . '][' . $bank_name . '][' . $bank_account . ']';
			break;
		case 'other':
			$other_method = $payout_member[1];

			$payout_method = '[' . $other_method . ']';
			break;
		default:
			[, $gcash_name, $gcash_number,] = $payout_member;

			$payout_method = 'G-Cash: [' . $gcash_name . '][' . $gcash_number . ']';
			break;
	}

	return $payout_method;
}

/**
 *
 * @return string
 *
 * @since version
 */
function view_payouts_admin($counter): string
{
	$currency = settings('ancillaries')->currency;

	$counter_span = '';

	if ($counter) {
		$counter_span = '<span id="counter" style="float:right">00:00:00</span>';
	}

	$table_payouts = table_payouts_admin();

	$str = <<<HTML
	<div class="card mb-4">
		<div class="card-header">
			<i class="fas fa-table me-1"></i>
			Payout List{$counter_span}
		</div>
		<div class="card-body">
			<table id="datatablesSimple">
				$table_payouts
			</table>
		</div>		
	</div>
HTML;

	if (!empty($payouts)) {
		$str .= '<table class="category table table-striped table-bordered table-hover">
            <thead>
            <tr>
                <th>Date</th>
                <th>User</th>
                <th>Method</th>
                <th>Amount (' . $currency . ')</th>
                <th>Charge (' . $currency . ')</th>
                <th>Total Payouts (' . $currency . ')</th>
            </tr>
            </thead>
            <tbody>';

		foreach ($payouts as $payout) {
			$user = user($payout->user_id);

			$str .= '<tr>';
			$str .= '<td>' . date('M j, Y - g:i A', $payout->payout_date) . '</td>';
			$str .= '<td><a href="' . sef(44) . qs() . 'uid=' .
				$user->id . '">' . $user->username . '</a></td>';
			$str .= '<td>' . payout_method($user) . '</td>';
			$str .= '<td>' . number_format($payout->amount, 2) . '</td>';
			$str .= '<td>' . number_format($payout->total_tax, 2) . '</td>';
			$str .= '<td>' . number_format($payout->payout_total, 2) . '</td>';
			$str .= '</tr>';
		}

		$str .= '</tbody>
        </table>';
	} else {
		$str .= '<hr><p>No payouts yet.</p>';
	}

	return $str;
}

function table_payouts_admin()
{
	$sa = settings('ancillaries');

	$currency = $sa->currency;

	$row_payouts_admin = row_payouts_admin();

	$str = <<<HTML
	<thead>
		<tr>
			<th>Date</th>
			<th>User</th>
			<th>Method</th>
			<th>Amount $currency</th>
			<th>Charge $currency</th>
			<th>Total Payouts $currency</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th>Date</th>
			<th>User</th>
			<th>Method</th>
			<th>Amount $currency</th>
			<th>Charge $currency</th>
			<th>Total Payouts $currency</th>
		</tr>
	</tfoot>
	<tbody>
		$row_payouts_admin						
	</tbody>		
HTML;

	return $str;
}

function row_payouts_admin()
{
	$payouts = payouts_admin();

	$str = '';

	if (empty($payouts)) {
		$str .= <<<HTML
			<tr>
				<td>n/a</td>
				<td>n/a</td>
				<td>n/a</td>
				<td>0</td>
				<td>0</td>
				<td>0</td>															
			</tr>					
		HTML;
	} else {
		foreach ($payouts as $payout) {
			$user = user($payout->user_id);

			$str .= '<tr>';
			$str .= '<td>' . date('M j, Y - g:i A', $payout->payout_date) . '</td>';
			$str .= '<td><a href="' . sef(44) . qs() . 'uid=' .
				$user->id . '">' . $user->username . '</a></td>';
			$str .= '<td>' . payout_method($user) . '</td>';
			$str .= '<td>' . number_format($payout->amount, 2) . '</td>';
			$str .= '<td>' . number_format($payout->total_tax, 2) . '</td>';
			$str .= '<td>' . number_format($payout->payout_total, 2) . '</td>';
			$str .= '</tr>';
		}
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
function view_payouts_user($user_id, $counter = false): string
{
	$setting_ancillaries = settings('ancillaries');

	$cybercharge = $setting_ancillaries->cybercharge / 100;
	$processing_fee = $setting_ancillaries->processing_fee;
	$currency = $setting_ancillaries->currency;

	$payouts = payouts_user($user_id);

	$total = 0;

	foreach ($payouts as $payout) {
		$total += ($payout->amount - (($payout->amount * $cybercharge) + $processing_fee));
	}

	$total_formatted = number_format($total, 2);

	$counter_span = '';

	if ($counter) {
		$counter_span = '<span id="counter" style="float:right">00:00:00</span>';
	}

	$table_payouts = table_payouts($user_id);

	$str = <<<HTML
	<div class="card mb-4">
		<div class="card-header">
			<i class="fas fa-table me-1"></i>
			Payouts List{$counter_span}
		</div>
		<div class="card-body">
			<table id="datatablesSimple">
				$table_payouts
			</table>
		</div>
		<div class="card-footer small text-muted">
			<i class="fas fa-money-bill me-1"></i>
			Total Paid: $total_formatted $currency			
		</div>
	</div>
HTML;

	return $str;
}

function table_payouts($user_id)
{
	$row_payouts = row_payouts($user_id);

	$str = <<<HTML
		<thead>
			<tr>
				<th>Date</th>
				<th>Method</th>
				<th>Amount</th>				
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Date</th>
				<th>Method</th>
				<th>Amount</th>				
			</tr>
		</tfoot>
		<tbody>
			$row_payouts						
		</tbody>		
	HTML;

	return $str;
}

function row_payouts($user_id)
{
	$setting_ancillaries = settings('ancillaries');

	$cybercharge = $setting_ancillaries->cybercharge / 100;
	$processing_fee = $setting_ancillaries->processing_fee;

	$payouts = payouts_user($user_id);

	$str = '';

	if (empty($payouts)) {
		$str .= <<<HTML
			<tr>
				<td>n/a</td>
				<td>n/a</td>
				<td>0</td>															
			</tr>					
		HTML;
	} else {
		foreach ($payouts as $payout) {
			$user = user($payout->user_id);

			$str .= '<tr>';
			$str .= '<td>' . date('M j, Y - g:i A', $payout->payout_date) . '</td>';

			$str .= payout_method($user);
			$str .= '<td>' . number_format($payout->amount - (
				($payout->amount * $cybercharge) + $processing_fee), 2) . '</td>';
			$str .= '</tr>';
		}
	}

	return $str;
}

/**
 * @param $user_id
 * @param $usertype
 * @param $admintype
 *
 * @return string
 *
 * @since version
 */
function view_payouts($user_id, $usertype, $admintype): string
{
	// $str = '<h1>Payout Logs</h1>';

	if ($usertype === 'Admin' && $admintype === 'Super') {
		$view_payouts = view_payouts_admin(true);
	} else {
		$view_payouts = view_payouts_user($user_id, true);
	}

	$str = <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Payout Logs</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">List of Payouts</li>
		</ol>				
		$view_payouts
	</div>
	HTML;

	return $str;
}