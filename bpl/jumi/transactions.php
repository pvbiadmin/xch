<?php

namespace BPL\Jumi\Transactions;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
// require_once 'bpl/menu.php';
require_once 'bpl/mods/helpers.php';

// use function BPL\Menu\admin as menu_admin;
// use function BPL\Menu\member as menu_member;
// use function BPL\Menu\manager as menu_manager;

use function Templates\SB_Admin\Tmpl\Master\main as master;

use function BPL\Mods\Url_SEF\qs;
use function BPL\Mods\Url_SEF\sef;

use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\page_validate;
use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\input_get;
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
	$usertype = session_get('usertype');
	$admintype = session_get('admintype');
	// $account_type  = session_get('account_type');
//	$merchant_type = session_get('merchant_type');
	$user_id = session_get('user_id');
	// $page = substr(input_get('page', 0), 0, 3);
	$uid = input_get('uid');
	// $username      = session_get('username');

	page_validate();

	if ($uid !== '') {
		$user_id = $uid;
	}

	// $limit_to = 20;

	$str = live_reload(true);

	switch ($usertype) {
		case 'Admin':
			// $str .= menu_admin($admintype, $account_type, $user_id, $username);
			$str .= view_admin($user_id, $admintype, true);
			break;
		case 'Member':
			// $str .= menu_member($account_type, $username, $user_id);
			$str .= member($user_id, true);
			break;
		// case 'Manager':
		// 	// $str .= menu_manager();
		// 	$str .= view_admin($user_id, $admintype, $page, $limit_to);
		// 	break;
	}

	return $str;
}

function view_admin($user_id, $admintype, $counter): string
{
	// $str = page_reload();

	$str = '';

	if ($admintype === 'Super') {
		$str .= view_transactions_admin($counter);
	} else {
		$str .= view_transactions_user($user_id, $counter);
	}

	return $str;
}

function view_transactions_admin($counter): string
{
	$transactions_admin = transactions_admin($counter);

	$str = <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Transactions</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">List of Account Transactions</li>
		</ol>				
		$transactions_admin
	</div>
	HTML;

	return $str;
}

function transactions_admin($counter)
{
	$counter_span = '';

	if ($counter) {
		$counter_span = '<span id="counter" style="float:right">00:00:00</span>';
	}

	$table_transactions_admin = table_transactions_admin();

	return <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-table me-1"></i>
				List Transactions{$counter_span}
			</div>
			<div class="card-body">
				<table id="datatablesSimple">
					$table_transactions_admin
				</table>
			</div>
		</div>
	HTML;
}

function table_transactions_admin()
{
	$sa = settings('ancillaries');

	$currency = $sa->currency;

	$row_transactions_admin = row_transactions_admin();

	$str = <<<HTML
		<thead>
			<tr>
				<th>Date</th>
				<th>Transaction</th>
				<th>Details</th>
				<th>Spent Value / Gained Value ($currency)</th>
				<th>Total Credit Balance ($currency)</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Date</th>
				<th>Transaction</th>
				<th>Details</th>
				<th>Spent Value / Gained Value ($currency)</th>
				<th>Total Credit Balance ($currency)</th>
			</tr>
		</tfoot>
		<tbody>
			$row_transactions_admin						
		</tbody>
HTML;

	return $str;
}

function row_transactions_admin(): string
{
	$transactions = transactions_get();

	// $total = count($transactions);

	// $limit_from = $limit_to * $page;

	// $str = '<h1>Account Transaction | ';
	// $str .= ((int) $page === 0) ? ('Latest ' . $total) :
	// 	('Past ' . $limit_from . ' - ' . ($limit_from + $limit_to));
	// $str .= '</h1>';

	// // Wrap the pagination in a div with custom styles to position it
	// $str .= '<div class="pagination-container" style="text-align: right; margin-bottom: 10px;">';
	// $str .= view_paginate($page, $limit_to);
	// $str .= '</div>';

	// if (!empty($transactions)) {
	// 	$currency = settings('ancillaries')->currency;

	// 	$str .= '<table class="category table table-striped table-bordered table-hover">
	//             <thead>
	//             <tr>
	//                 <th>Date</th>
	//                 <th>Transaction</th>
	//                 <th>Details</th>
	//                 <th>Spent Value / Gained Value (' . $currency . ')</th>
	//                 <th>Total Credit Balance (' . $currency . ')</th>
	//             </tr>
	//             </thead>
	//             <tbody>';

	// 	$transactions_limit = transactions_limit_get($page, $limit_to);

	$str = '';

	foreach ($transactions as $transaction) {
		$str .= '<tr>
	                    <td>' . date('M j, Y - g:i A', $transaction->transaction_date) . '</td>
	                    <td>' . $transaction->transaction . '</td>
	                    <td>' . $transaction->details . '</td>
	                    <td>' . number_format($transaction->value, 2) . '</td>
	                    <td>' . number_format($transaction->balance, 2) . '</td>
	                </tr>';
	}

	// 	$str .= '</tbody></table>';
	// } else {
	// 	$str .= '<hr><p>No transactions yet.</p>';
	// }

	return $str;
}

function view_transactions_user($user_id, $counter)
{
	$transactions_user = transactions_user($user_id, $counter);

	$str = <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Transactions</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">List of Account Transactions</li>
		</ol>				
		$transactions_user
	</div>
	HTML;

	return $str;
}

function transactions_user($user_id, $counter)
{
	$counter_span = '';

	if ($counter) {
		$counter_span = '<span id="counter" style="float:right">00:00:00</span>';
	}

	$table_transactions_user = table_transactions_user($user_id);

	return <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-table me-1"></i>
				List Transactions{$counter_span}
			</div>
			<div class="card-body">
				<table id="datatablesSimple">
					$table_transactions_user
				</table>
			</div>
		</div>
	HTML;
}

function table_transactions_user($user_id)
{
	$sa = settings('ancillaries');

	$currency = $sa->currency;

	$row_transactions_user = row_transactions_user($user_id);

	$str = <<<HTML
		<thead>
			<tr>
                <th>Date</th>
                <th>Transaction</th>
                <th>Details</th>
                <th>Value ($currency)</th>
                <th>Balance ($currency)</th>
            </tr>
		</thead>
		<tfoot>
			<tr>
                <th>Date</th>
                <th>Transaction</th>
                <th>Details</th>
                <th>Value ($currency)</th>
                <th>Balance ($currency)</th>
            </tr>
		</tfoot>
		<tbody>
			$row_transactions_user						
		</tbody>
HTML;

	return $str;
}

function row_transactions_user($user_id): string
{
	$transactions = transactions_user_get($user_id);

	// $limit_from = $limit_to * $page;

	// $str = view_paginate($page, $limit_to);

	// $str .= '<h1>Account Transaction | ';
	// $str .= ((int) $page === 0) ? ('Latest ' . count($transactions)) :
	// 	('Past ' . $limit_from . ' - ' . ($limit_from + $limit_to));
	// $str .= '</h1>';

	// if (!empty($transactions)) {
	// 	$currency = settings('ancillaries')->currency;

	// 	$str .= '<table class="category table table-striped table-bordered table-hover">
	//         <thead>
	//         <tr>
	//             <th>Date</th>
	//             <th>Transaction</th>
	//             <th>Details</th>
	//             <th>Value (' . $currency . ')</th>
	//             <th>Balance (' . $currency . ')</th>
	//         </tr>
	//         </thead>
	//         <tbody>';

	// 	$transactions_limit = transactions_user_limit_get($user_id, $page, $limit_to);

	$str = '';

	foreach ($transactions as $transaction) {
		$str .= '<tr>
                    <td>' . date('M j, Y - g:i A', $transaction->transaction_date) . '</td>
                    <td>' . $transaction->transaction . '</td>
                    <td>' . $transaction->details . '</td>
                    <td>' . number_format($transaction->value, 2) . '</td>
                    <td>' . number_format($transaction->balance, 2) . '</td>
                </tr>';
	}

	// 	$str .= '</tbody></table>';
	// } else {
	// 	$str .= '<hr><p>No transactions yet.</p>';
	// }

	return $str;
}

/**
 * @param $user_id
 * @param $page
 * @param $limit_to
 *
 * @return string
 *
 * @since version
 */
function member($user_id, $counter): string
{
	$view_user_transactions = view_user_transactions($user_id, $counter);

	$str = <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Transactions</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">List of Account Transactions</li>
		</ol>				
		$view_user_transactions
	</div>
	HTML;

	return $str;
}

function view_user_transactions($user_id, $counter)
{
	$counter_span = '';

	if ($counter) {
		$counter_span = '<span id="counter" style="float:right">00:00:00</span>';
	}

	$table_user_transactions = table_user_transactions($user_id);

	return <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-table me-1"></i>
				List Transactions{$counter_span}
			</div>
			<div class="card-body">
				<table id="datatablesSimple">
					$table_user_transactions
				</table>
			</div>
		</div>
	HTML;
}

function table_user_transactions($user_id)
{
	$sa = settings('ancillaries');

	$currency = $sa->currency;

	$row_user_transactions = row_user_transactions($user_id);

	$str = <<<HTML
		<thead>
			<tr>
	            <th>Date</th>
	            <th>Transaction</th>
	            <th>Details</th>
	            <th>Value ($currency)</th>
	            <th>Balance ($currency)</th>
	        </tr>
		</thead>
		<tfoot>
			<tr>
	            <th>Date</th>
	            <th>Transaction</th>
	            <th>Details</th>
	            <th>Value ($currency)</th>
	            <th>Balance ($currency)</th>
	        </tr>
		</tfoot>
		<tbody>
			$row_user_transactions						
		</tbody>
HTML;

	return $str;
}

function row_user_transactions($user_id): string
{
	// $limit_from = $limit_to * $page;

	// $str = page_reload();

	$transactions = transactions_user_get($user_id);

	$str = '';

	// $str .= view_paginate($page, $limit_to);

	// $str .= '<h1>Account Transaction | ';
	// $str .= ((int) $page === 0) ? ('Latest ' . count($transactions)) :
	// 	('Past ' . $limit_from . ' - ' . ($limit_from + $limit_to));
	// $str .= '</h1>';

	// if (!empty($transactions)) {
	// 	$currency = settings('ancillaries')->currency;

	// 	$str .= '<table class="category table table-striped table-bordered table-hover">
	//         <thead>
	//         <tr>
	//             <th>Date</th>
	//             <th>Transaction</th>
	//             <th>Details</th>
	//             <th>Value (' . $currency . ')</th>
	//             <th>Balance (' . $currency . ')</th>
	//         </tr>
	//         </thead>
	//         <tbody>';

	// 	$transactions_limit = transactions_user_limit_get($user_id, $page, $limit_to);

	foreach ($transactions as $transaction) {
		$str .= '<tr>
	            <td>' . date('M j, Y - g:i A', $transaction->transaction_date) . '</td>
	            <td>' . $transaction->transaction . '</td>
	            <td>' . $transaction->details . '</td>
	            <td>' . number_format($transaction->value, 2) . '</td>
	            <td>' . number_format($transaction->balance, 2) . '</td>
        	</tr>';
	}

	// 	$str .= '</tbody></table>';
	// } else {
	// 	$str .= '<hr><p>No transactions yet.</p>';
	// }

	return $str;
}

/**
 *
 * @return array|mixed
 *
 * @since version
 */
function transactions_get()
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_transactions ' .
		'ORDER BY transaction_id DESC'
	)->loadObjectList();
}

function view_paginate($page, $limit_to): string
{
	$limit_from = $limit_to * $page;
	$total = count(transactions_get());
	$last_page = ($total - $total % $limit_to) / $limit_to;

	$str = '<div class="pagination-container" style="text-align:right; margin-top:30px;">';

	if ($total > ($limit_from + $limit_to)) {
		if ($page !== $last_page) {
			$str .= '<a href="' . sef(106) . qs() . 'page=' . ($last_page) .
				'" class="btn btn-primary">Oldest</a>';
		}

		$str .= '<a href="' . sef(106) . qs() . 'page=' . ($page + 1) .
			'" class="btn btn-danger">Previous</a>';
	}

	if ($page > 0 && $page) {
		$str .= '<a href="' . sef(106) . qs() . 'page=' . ($page - 1) .
			'" class="btn btn-primary">Next</a>';

		if ((int) $page !== 1) {
			$str .= '<a href="' . sef(106) . qs() . 'page=' . (1) .
				'" class="btn btn-danger">Latest</a>';
		}
	}

	$str .= '</div>';

	$str .= '<style>
		.pagination-container {
		    display: flex;
		    flex-wrap: wrap;
		    gap: 10px; /* Adds spacing between buttons */
		}
		
		.pagination-container a {
		    flex: 1; /* Makes buttons flexible to occupy available space */
		    min-width: 100px; /* Ensures a minimum width for buttons */
		    text-align: center;
		    margin-bottom: 10px; /* Adds margin at the bottom for small screens */
		}
		
		@media (max-width: 600px) {
		    .pagination-container {
		        flex-direction: column; /* Stacks buttons vertically on small screens */
		    }
		}
	</style>';

	return $str;
}

/**
 * @param $page
 * @param $limit_to
 *
 * @return array|mixed
 *
 * @since version
 */
function transactions_limit_get($page, $limit_to)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_transactions ' .
		'ORDER BY transaction_id DESC ' .
		'LIMIT ' . ($limit_to * $page) . ', ' . $limit_to
	)->loadObjectList();
}

/**
 * @param $user_id
 *
 *
 * @return array|mixed
 * @since version
 */
function transactions_user_get($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_transactions ' .
		'WHERE user_id = ' . $db->quote($user_id) .
		' ORDER BY transaction_id DESC'
	)->loadObjectList();
}

/**
 * @param $user_id
 * @param $page
 * @param $limit_to
 *
 * @return array|mixed
 *
 * @since version
 */
function transactions_user_limit_get($user_id, $page, $limit_to)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_transactions ' .
		'WHERE user_id = ' . $db->quote($user_id) .
		' ORDER BY transaction_id DESC ' .
		'LIMIT ' . ($limit_to * $page) . ', ' . $limit_to
	)->loadObjectList();
}