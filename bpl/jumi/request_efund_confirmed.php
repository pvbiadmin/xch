<?php

namespace BPL\Jumi\Request_Efund_Confirmed;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
require_once 'bpl/menu.php';
require_once 'bpl/mods/helpers.php';

use function BPL\Menu\admin as menu_admin;
use function BPL\Menu\member as menu_member;
use function BPL\Menu\manager as menu_manager;

use function Templates\SB_Admin\Tmpl\Master\main as master;

use function BPL\Mods\Url_SEF\sef;
use function BPL\Mods\Url_SEF\qs;

use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\settings;
use function BPL\Mods\Helpers\page_validate;
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
	$usertype = session_get('usertype');
	$admintype = session_get('admintype');
	$account_type = session_get('account_type');
	$user_id = session_get('user_id');
	$merchant_type = session_get('merchant_type');
	$username = session_get('username');

	page_validate();

	// $str = menu($usertype, $admintype, $account_type, $username, $merchant_type, $user_id);

	$str = live_reload(true);

	$sa = settings('ancillaries');

	$efund_name = $sa->efund_name;

	$view_efund_request_confirmed = view_efund_request_confirmed($usertype, $user_id, true);

	$str .= <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Completed $efund_name Requests</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">List of $efund_name Requests that have been Confirmed</li>
		</ol>				
		$view_efund_request_confirmed
	</div>
	HTML;

	return $str;
}

function view_efund_request_confirmed($usertype, $user_id, $counter): string
{
	// $str = page_reload();

	// $str .= '<h1> ' . settings('ancillaries')->efund_name . ' Transactions</h1>';

	$str = '';

	if ($usertype === 'Admin' || $usertype === 'manager') {
		$str .= view_admin_efund_request_confirmed($counter);
	} else {
		$str .= view_user_efund_request_confirmed($user_id, $counter);
	}

	return $str;
}

/**
 *
 * @return string
 *
 * @since version
 */
function view_admin_efund_request_confirmed($counter): string
{
	$counter_span = '';

	if ($counter) {
		$counter_span = '<span id="counter" style="float:right">00:00:00</span>';
	}

	$sa = settings('ancillaries');

	$efund_name = $sa->efund_name;

	$table_admin_efund_request_confirmed = table_admin_efund_request_confirmed();

	return <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-table me-1"></i>
				Completed $efund_name Requests{$counter_span}
			</div>
			<div class="card-body">
				<table id="datatablesSimple">
					$table_admin_efund_request_confirmed
				</table>
			</div>
		</div>
	HTML;
}

function table_admin_efund_request_confirmed(): string
{
	$sa = settings('ancillaries');

	$currency = $sa->currency;

	$row_admin_efund_request_confirmed = row_admin_efund_request_confirmed();

	$str = <<<HTML
		<thead>
			<tr>
				<th>Date Requested</th>
				<th>Username</th>
				<th>Balance ($currency)</th>
				<th>Amount ($currency)</th>
                <th>Price ($currency)</th>
            </tr>
		</thead>
		<tfoot>
			<tr>
				<th>Date Requested</th>
				<th>Username</th>
				<th>Balance ($currency)</th>
				<th>Amount ($currency)</th>
                <th>Price ($currency)</th>
            </tr>
		</tfoot>
		<tbody>
			$row_admin_efund_request_confirmed						
		</tbody>
HTML;

	return $str;
}

function row_admin_efund_request_confirmed(): string
{
	// $sa = settings('ancillaries');

	// $efund_name = $sa->efund_name;
	// $currency = $sa->currency;

	$result = efund_request_confirmed();

	$str = '';

	foreach ($result as $member) {
		$str .= '<tr>
                <td>' . date('M j, Y - g:i A', $member->date_requested) . '</td>
                <td><a href="' . sef(44) . qs() . 'uid=' . $member->id . '">' . $member->username . '</a>' . '</td>
                <td>' . number_format($member->payout_transfer, 2) . ' ' . /* $efund_name . */ '</td>
                <td>' . number_format($member->amount, 2) . ' ' . /* $efund_name . */ '</td>
                <td>' . number_format($member->price, 2) . ' ' . strtoupper($member->method) . '</td>
            </tr>';
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
function view_user_efund_request_confirmed($user_id, $counter): string
{
	$counter_span = '';

	if ($counter) {
		$counter_span = '<span id="counter" style="float:right">00:00:00</span>';
	}

	$sa = settings('ancillaries');

	$efund_name = $sa->efund_name;

	$table_withdrawal_completed_user = table_user_efund_request_confirmed($user_id);

	return <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-table me-1"></i>
				Completed $efund_name Requests{$counter_span}
			</div>
			<div class="card-body">
				<table id="datatablesSimple">
					$table_withdrawal_completed_user
				</table>
			</div>
		</div>
	HTML;
}

function table_user_efund_request_confirmed($user_id): string
{
	$sa = settings('ancillaries');

	$currency = $sa->currency;

	$row_user_efund_request_confirmed = row_user_efund_request_confirmed($user_id);

	$str = <<<HTML
		<thead>
			<tr>
                <th>Date Requested</th>
                <th>Amount ($currency)</th>
                <th>Price ($currency)</th>
            </tr>
		</thead>
		<tfoot>
			<tr>
                <th>Date Requested</th>
                <th>Amount ($currency)</th>
                <th>Price ($currency)</th>
            </tr>
		</tfoot>
		<tbody>
			$row_user_efund_request_confirmed						
		</tbody>
HTML;

	return $str;
}

function row_user_efund_request_confirmed($user_id): string
{
	$result = user_efund_request_confirmed($user_id);

	$str = '';

	// if ($result) {
	foreach ($result as $member) {
		$str .= '<tr>
                <td>' . date('M j, Y - g:i A', $member->date_confirmed) . '</td>
                <td>' . number_format($member->amount, 2) . '</td>
                <td>' . number_format($member->price, 2) . '</td>
            </tr>';
	}
	/* } *//*  else {
							   $str .= 'No ' . settings('ancillaries')->efund_name . ' requests yet.';
						   } */

	return $str;
}

/**
 *
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
function efund_request_confirmed()
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_users u, ' .
		'network_efund_request r ' .
		'WHERE u.id = r.user_id ' .
		'AND r.date_confirmed <> ' . $db->quote(0) .
		' ORDER BY r.request_id DESC'
	)->loadObjectList();
}

/**
 * @param $user_id
 *
 * @return array|mixed
 *
 * @since version
 */
function user_efund_request_confirmed($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_users u, ' .
		'network_efund_request r ' .
		'WHERE u.id = r.user_id ' .
		'AND r.date_confirmed <> ' . $db->quote(0) .
		' AND r.user_id = ' . $db->quote($user_id) .
		' ORDER BY r.request_id DESC'
	)->loadObjectList();
}