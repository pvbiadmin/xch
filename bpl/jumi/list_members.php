<?php

namespace BPL\Jumi\List_Members;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
require_once 'bpl/mods/helpers.php';

use function BPL\Mods\Url_SEF\sef;
use function BPL\Mods\Url_SEF\qs;

use function Templates\SB_Admin\Tmpl\Master\main as master;

use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\user;
use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\input_get;
use function BPL\Mods\Helpers\page_validate;
use function BPL\Mods\Helpers\settings;
// use function BPL\Mods\Helpers\page_reload;
// use function BPL\Mods\Helpers\live_reload;

$content = main();

master($content);

/**
 *
 *
 * @since version
 */
function main()
{
	page_validate();

	$user_id = session_get('user_id');

	$str = /* live_reload(true) */ '';

	$view_members = view_members($user_id, false);

	$str .= <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Monitoring Page</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">List of All Members</li>
		</ol>				
		$view_members
	</div>
	HTML;

	return $str;
}

function view_members($user_id, $counter)
{
	$counter_span = '';

	if ($counter) {
		$counter_span = '<span id="counter" style="float:right">00:00:00</span>';
	}

	$table_all_members = table_all_members($user_id);

	return <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-table me-1"></i>
				Summary{$counter_span}
			</div>
			<div class="card-body">
				<table id="datatablesSimple">
					$table_all_members
				</table>
			</div>
		</div>
	HTML;
}

function table_all_members($user_id)
{
	$user = user($user_id);

	$usertype = $user->usertype;

	$row_members = row_members();

	$actions = '';

	if ($usertype === 'Admin') {
		$actions = '<th>Actions</th>';
	}

	$str = <<<HTML
		<thead>
			<tr>
				<th>Date Registered</th>
				<th>Username</th>
				<th>Wallet Balance</th>
				<th>Cash Requests</th>
				<th>Payouts</th>
				<th>Subscriptions</th>
				<th>Transfer-outs</th>
				<th>Transfer-ins</th>
				$actions
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Date Registered</th>
				<th>Username</th>
				<th>Wallet Balance</th>
				<th>Cash Requests</th>
				<th>Payouts</th>
				<th>Subscriptions</th>
				<th>Transfer-outs</th>
				<th>Transfer-ins</th>
				$actions
			</tr>
		</tfoot>
		<tbody>
			$row_members						
		</tbody>
	HTML;

	return $str;
}

/**
 *
 * @param $limit
 *
 * @return string
 *
 * @since version
 */
function row_members(): string
{
	// $usertype = session_get('usertype');
	// $username = input_get('username');
	// $page = substr(input_get('page', 0), 0, 3);

	// $settings_ancillaries = settings('ancillaries');
	// $settings_plans = settings('plans');

	// $currency = $settings_ancillaries->currency;

	// $limit_to = $limit;
	// $limit_from = $limit_to * $page;

	// $str = '<table class="category table table-striped table-bordered table-hover">';
	// $str .= '<thead>';

	// $str .= search_member();

	// $str .= '<tr>';
	// $str .= '<th>Date Registered</th>';
	// $str .= '<th>Username</th>';
	// $str .= '<th>Account</th>';

	// if ($settings_plans->royalty) {
	// 	$str .= '<th>' . $settings_plans->royalty_name . '</th>';
	// }

	// $str .= '<th>Balance (' . $currency . ')</th>';

	// if (
	// 	$settings_plans->binary_pair &&
	// 	binary_all()
	// ) {
	// 	$str .= '<th>Left</th>';
	// 	$str .= '<th>Right</th>';
	// }

	// if ($usertype === 'Admin') {
	// 	$str .= '<th>Actions</th>';
	// }

	// $str .= '</tr>';
	// $str .= '</thead>';
	// $str .= '<tbody>';

	// if ($username === '') {
	// $members_limit = users_desc_lim($limit_from, $limit_to);

	$members = users_desc();

	$str = '';

	foreach ($members as $member) {
		$str .= view_member($member);
	}
	// } else {
	// 	$str .= search_result($username);
	// }

	// $str .= '</tbody>';
	// $str .= '</table>';

	return $str;
}

/**
 * @param $member
 *
 * @return string
 *
 * @since version
 */
function view_member($member): string
{
	$usertype = session_get('usertype');

	// $settings_ancillaries = settings('ancillaries');
	// $settings_plans = settings('plans');

	$account_type = $member->account_type;

	// $payment_mode = $settings_ancillaries->payment_mode;

	$user_cd = user_cd($member->id);

	// $user_binary = user_binary($member->id);

	$account_rd = settings('entry')->{$account_type . '_package_name'} . ($user_cd ? ' CD' : '');

	$transfer = user_transfer($member->id);

	$transfer_ins = $transfer['ins'];
	$transfer_outs = $transfer['outs'];

	// $account_rd .= !0 ? '' : harvest_append($member);

	// $psv = $account_type !== 'starter' ? settings('binary')->{$account_type . '_pairs'} : 0; // pair set value

	$str = '<tr>';
	$str .= '<td>' . date('M j, Y - G:i', $member->date_registered) . '</td>';
	$str .= '<td><a href="' . sef(44) . qs() .
		'uid=' . $member->id . '">' . $member->username . '</a></td>';
	$str .= '<td>' . number_format($member->payout_transfer, 2) . '</td>';
	// $str .= ($settings_plans->royalty ? ('<td>' .
	// 	settings('royalty')->{$member->rank . '_rank_name'} . '</td>') : "\n");
	$str .= '<td>' . number_format(total_cash_ins($member->id), 2) . '</td>';
	$str .= '<td>' . number_format($member->payout_total, 2) . '</td>';
	$str .= '<td>' . number_format($member->fast_track_principal, 2) . '</td>';
	$str .= '<td>' . number_format($transfer_outs, 2) . '</td>';
	$str .= '<td>' . number_format($transfer_ins, 2) . '</td>';
	$str .= '<td>';

	// if (
	// 	$settings_plans->binary_pair &&
	// 	binary_all()
	// ) {
	// 	$str .= '<td>' . ($user_binary->ctr_left ?? 'n/a') . '</td>';
	// 	$str .= '<td>' . ($user_binary->ctr_right ?? 'n/a') . '</td>';
	// }

	// $str .= ($settings_plans->binary_pair && $user_binary && $psv ?
	// 	('<td>' . $user_binary->ctr_left . '</td>' . '<td>' .
	// 		$user_binary->ctr_right . '</td>') : ('<td>N/A</td>' . '<td>N/A</td>'));

	$link_block = sef(8) . qs() . 'uid=' . $member->id;
	$link_unblock = sef(107) . qs() . 'uid=' . $member->id;

	$block_btn = <<<HTML
		<a href="$link_block" type="button" class="btn btn-primary btn-sm">Block</a>
HTML;

	$unblock_btn = <<<HTML
		<a href="$link_unblock" type="button" class="btn btn-primary btn-sm">Unblock</a>
HTML;

	$actions = $block_btn;

	if ($member->block) {
		$actions = $unblock_btn;
	}

	if ($usertype === 'Admin') {
		$str .= $actions;

		// $str .= '<td>';
		// $str .= '<div class="uk-button-group">';
		// $str .= '<button class="uk-button uk-button-primary">Select</button>';
		// $str .= '<div class="" data-uk-dropdown="{mode:\'click\'}">';
		// $str .= '<button class="uk-button uk-button-primary"><i class="uk-icon-caret-down"></i></button>';
		// $str .= '<div style="" class="uk-dropdown uk-dropdown-small">';
		// $str .= '<ul class="uk-nav uk-nav-dropdown">';
		// $str .= '<li>';
		// $str .= (!$member->block ?
		// 	'<a href="' . sef(8) . qs() . 'uid=' . $member->id . '">Block</a>' :
		// 	'<a href="' . sef(107) . qs() . 'uid=' . $member->id . '">Unblock</a>');
		// $str .= '</li>';
		// $str .= $payment_mode === 'CODE' && $settings_ancillaries->cd_mode === 'cd' ?
		// 	('<li>' . (!$user_cd ? '<a href="' . sef(11) . qs() . 'uid=' . $member->id . '">CD</a>' :
		// 		'<a href="' . sef(108) . qs() . 'uid=' . $member->id . '">UnCD</a>') . '</li>') : '';
		// $str .= '</ul>';
		// $str .= '</div>';
		// $str .= '</div>';
		// $str .= '</div>';
		$str .= '</td>';
	}

	$str .= '</tr>';

	return $str;
}

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

function total_cash_ins($user_id)
{
	$result = user_efund_request_confirmed($user_id);

	$total = 0;

	foreach ($result as $member) {
		$total += $member->amount;
	}

	return $total;
}

function user_transfer($user_id)
{
	$sql = <<<SQL
		SELECT 
			u.id AS user_id,
			u.username,
			COALESCE(SUM(CASE WHEN t.transfer_from = u.id THEN t.amount ELSE 0 END), 0) AS total_transferred_out,
			COALESCE(SUM(CASE WHEN t.transfer_to = u.id THEN t.amount ELSE 0 END), 0) AS total_received
		FROM 
			network_users u
		LEFT JOIN 
			network_transfer t ON u.id = t.transfer_from OR u.id = t.transfer_to
		WHERE 
			u.id = $user_id
		GROUP BY 
			u.id, u.username;
	SQL;

	$db = db();

	$transfers = $db->setQuery($sql)->loadObject();

	return [
		'ins' => $transfers->total_received,
		'outs' => $transfers->total_transferred_out
	];
}

/**
 * @param $username
 *
 * @return string
 *
 * @since version
 */
function search_result($username): string
{
	$users = username_users($username);

	$str = '';

	if ($users) {
		foreach ($users as $user) {
			$str .= view_member($user);
		}
	}

	return $str;
}

/**
 *
 * @return string
 *
 * @since version
 */
function search_member(): string
{
	return '<tr><td colspan="5"><form method="post">
		<input type="text" name="username" style="float:left" placeholder="Search member">
		<input type="submit" class="uk-button uk-button-primary">
		</form></td></tr>';
}

/**
 *
 * @return string
 *
 * @since version
 */
function paginate(): string
{
	$page = substr(input_get('page', 0), 0, 3);

	$limit_to = 5;
	$limit_from = $limit_to * $page;

	$members = users_desc();

	$total = count($members);

	$last_page = ($total - $total % $limit_to) / $limit_to;

	$str = '<div style="float:right; margin-top:30px;">';

	if ($total > ($limit_from + $limit_to)) {
		if ((int) $page !== (int) $last_page) {
			$str .= '<a href="' . sef(40) . qs() . 'page=' . ($last_page) .
				'" class="uk-button uk-button-primary">Oldest</a>';
		}

		$str .= '<a href="' . sef(40) . qs() . 'page=' . ($page + 1) .
			'" class="uk-button uk-button-danger">Previous</a>';
	}

	if ($page > 0 && $page) {
		$str .= '<a href="' . sef(40) . qs() . 'page=' . ($page - 1) .
			'" class="uk-button uk-button-primary">Next</a>';

		if ((int) $page !== 1) {
			$str .= '<a href="' . sef(40) . qs() . 'page=' . (1) .
				'" class="uk-button uk-button-danger">Latest</a>';
		}
	}

	$str .= '</div>';

	return $str;
}

/**
 *
 * @return string
 *
 * @since version
 */
function header(): string
{
	$page = substr(input_get('page', 0), 0, 3);

	$limit_to = 5;
	$limit_from = $limit_to * $page;

	$members = users_desc();

	$total = count($members);

	$str = '<h1>Members | ';
	$str .= ((int) $page === 0) ? ('Latest ' . $total) :
		('Past ' . $limit_from . ' - ' . ($limit_from + $limit_to));
	$str .= '</h1>';

	return $str;
}

/**
 *
 * @return array|mixed
 *
 * @since version
 */
function users_desc()
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_users ' .
		'ORDER BY id DESC'
	)->loadObjectList();
}

/**
 * @param $limit_from
 * @param $limit_to
 *
 * @return array|mixed
 *
 * @since version
 */
function users_desc_lim($limit_from, $limit_to)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_users ' .
		'ORDER BY id DESC ' .
		'LIMIT ' . $limit_from . ', ' . $limit_to
	)->loadObjectList();
}

/**
 * @param $user_id
 *
 * @return mixed|null
 *
 * @since version
 */
function user_cd($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_commission_deduct ' .
		'WHERE id = ' . $db->quote($user_id)
	)->loadObject();
}

/**
 * @param $user_id
 *
 * @return mixed|null
 *
 * @since version
 */
function user_binary($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_binary ' .
		'WHERE id = ' . $db->quote($user_id)
	)->loadObject();
}

/**
 *
 * @return mixed|null
 *
 * @since version
 */
function binary_all()
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_binary'
	)->loadObject();
}

/**
 * @param $user_id
 * @param $type
 *
 * @return array|mixed
 *
 * @since version
 */
function user_harvest($user_id, $type)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_harvest_' . $type .
		' WHERE user_id = ' . $db->quote($user_id)
	)->loadObjectList();
}

/**
 * @param $username
 *
 * @return mixed|null
 *
 * @since version
 */
function username_users($username)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_users ' .
		'WHERE username LIKE ' . $db->quote('%' . $username . '%')
	)->loadObjectList();
}

function harvest_append($member): string
{
	$user_cd = user_cd($member->id);

	$account_rd = settings('entry')->{$member->account_type .
		'_package_name'} . ($user_cd ? ' CD' : '');

	if (
		!empty(user_harvest($member->id, 'basic')) ||
		!empty(user_harvest($member->id, 'associate'))
	) {
		if (!empty(user_harvest($member->id, 'basic'))) {
			$account_rd .= ' + <a href="' . sef(23) . qs() .
				'uid=' . $member->id . '">(Bronze Harvest)</a>';
		}

		if (!empty(user_harvest($member->id, 'associate'))) {
			$account_rd .= ' + <a href="' . sef(22) . qs() .
				'uid=' . $member->id . '">(Silver Harvest)</a>';
		}
	}

	return $account_rd;
}