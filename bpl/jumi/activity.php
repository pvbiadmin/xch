<?php

namespace BPL\Jumi\Activity;

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
// use function BPL\Mods\Helpers\input_get;
use function BPL\Mods\Helpers\page_validate;
// use function BPL\Mods\Helpers\page_reload;
use function BPL\Mods\Helpers\db;
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
	$usertype = session_get('usertype');
	// $admintype = session_get('admintype');
	// $account_type = session_get('account_type');
	// $merchant_type = session_get('merchant_type');
	$user_id = session_get('user_id');
	// $page = substr(input_get('page', 0), 0, 3);

	// $limit = 20;
	// $start = $limit * $page;

	page_validate();

	$str = live_reload(true);

	switch ($usertype) {
		case 'Admin':
			// $str .= menu_admin($admintype, $account_type, $user_id, $username);
			$str .= admin(true);
			break;
		case 'Member':
			// $str .= menu_member($account_type, $username, $user_id);
			$str .= member($user_id, true);
			break;
		// case 'manager':
		// 	// $str .= menu_manager();
		// 	$str .= admin($page, $start);
		// 	break;
	}

	return $str;
}

/**
 *
 * @param        $page
 * @param        $start
 * @param   int  $limit
 *
 * @return string
 *
 * @since version
 */
function admin($counter): string
{
	$view_activity_admin = view_activity_admin($counter);

	$str = <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Activity</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">List of All Activities</li>
		</ol>				
		$view_activity_admin
	</div>
	HTML;

	return $str;
}

function view_activity_admin($counter)
{
	$counter_span = '';

	if ($counter) {
		$counter_span = '<span id="counter" style="float:right">00:00:00</span>';
	}

	$table_activity_admin = table_activity_admin();

	return <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-table me-1"></i>
				List Activities{$counter_span}
			</div>
			<div class="card-body">
				<table id="datatablesSimple">
					$table_activity_admin
				</table>
			</div>
		</div>
	HTML;
}

function table_activity_admin()
{
	$row_activity_admin = row_activity_admin();

	$str = <<<HTML
		<thead>
			<tr>
				<th>Date</th>
				<th>Users Notified</th>
				<th>Activity</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Date</th>
				<th>Users Notified</th>
				<th>Activity</th>
			</tr>
		</tfoot>
		<tbody>
			$row_activity_admin						
		</tbody>
HTML;

	return $str;
}

function row_activity_admin(): string
{
	$activities = activity_all();

	// $str = page_reload();
	// $str .= paginate($page, $start, $limit);
	// $str .= header_admin($start, $page, $limit);

	// if (!empty($activities)) {
	// 	$str .= '<table class="category table table-striped table-bordered table-hover">
	//     <thead>
	//     <tr>
	//         <th>Date</th>
	//         <th>Users Notified</th>
	//         <th>Activity</th>
	//     </tr>
	//     </thead>
	//     <tbody>';

	// 	$activities_limit = activities_lim($start, $limit);

	$str = '';

	foreach ($activities as $activity) {
		$str .= '<tr>
                <td>' . date('M j, Y - G:i', $activity->activity_date) . '</td>
                <td>';

		if ($activity->user_id !== '0') {
			$tmp = user($activity->user_id);

			$str .= '<a href="' . sef(44) . qs() . 'uid=' .
				$activity->user_id . '">' . $tmp->username . '</a><br>';
		}

		if (
			$activity->sponsor_id !== '0' &&
			$activity->sponsor_id !== $activity->user_id
		) {
			$tmp = user($activity->sponsor_id);

			$str .= '<a href="' . sef(44) . qs() . 'uid=' .
				$activity->sponsor_id . '">' . $tmp->username . '</a><br>';
		}

		if (
			$activity->upline_id !== '0' &&
			$activity->upline_id !== $activity->sponsor_id &&
			$activity->upline_id !== $activity->user_id
		) {
			$tmp = user($activity->upline_id);

			$str .= '<a href="' . sef(44) . qs() . 'uid=' .
				$activity->upline_id . '">' . $tmp->username . '</a><br>';
		}

		$str .= '</td>
                <td>' . $activity->activity . '</td>
            </tr>';
	}

	// 	$str .= '</tbody></table>';
	// } else {
	// 	$str .= '<hr><p>No activities yet.</p>';
	// }

	return $str;
}

function member($user_id, $counter): string
{
	$view_activity_member = view_activity_member($user_id, $counter);

	$str = <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Activity</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">List of All Activities</li>
		</ol>				
		$view_activity_member
	</div>
	HTML;

	return $str;
}

function view_activity_member($user_id, $counter)
{
	$counter_span = '';

	if ($counter) {
		$counter_span = '<span id="counter" style="float:right">00:00:00</span>';
	}

	$table_activity_member = table_activity_member($user_id);

	return <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-table me-1"></i>
				List Activities{$counter_span}
			</div>
			<div class="card-body">
				<table id="datatablesSimple">
					$table_activity_member
				</table>
			</div>
		</div>
	HTML;
}

function table_activity_member($user_id)
{
	$row_activity_admin = row_activity_member($user_id);

	$str = <<<HTML
		<thead>
			<tr>
	            <th>Date</th>
	            <th>Users Notified</th>
	            <th>Activity</th>
	        </tr>
		</thead>
		<tfoot>
			<tr>
				<th>Date</th>
				<th>Users Notified</th>
				<th>Activity</th>
			</tr>
		</tfoot>
		<tbody>
			$row_activity_admin						
		</tbody>
HTML;

	return $str;
}

function row_activity_member($user_id): string
{
	$activities = user_activity($user_id);

	// $str = page_reload();
	// $str .= paginate($page, $start, $limit);
	// $str .= header_user($user_id, $start, $page, $limit);

	// if (!empty($activities)) {
	// 	$str .= '<table class="category table table-striped table-bordered table-hover">
	//         <thead>
	//         <tr>
	//             <th>Date</th>
	//             <th>Users Notified</th>
	//             <th>Activity</th>
	//         </tr>
	//         </thead>
	//         <tbody>';

	// 	$activities_limit = user_activities_lim($start, $limit, $user_id);

	$str = '';

	foreach ($activities as $activity) {
		$str .= '<tr><td>' . date('M j, Y - G:i', $activity->activity_date) . '</td><td>';

		if ((int) $activity->user_id !== 0) {
			$tmp = user($activity->user_id);

			$str .= '<a href="' . sef(44) . qs() . 'uid=' .
				$activity->user_id . '">' . $tmp->username . '</a><br>';
		}

		if ((int) $activity->sponsor_id !== 0) {
			$tmp = user($activity->sponsor_id);

			$str .= '<a href="' . sef(44) . qs() . 'uid=' .
				$activity->sponsor_id . '">' . $tmp->username . '</a><br>';
		}

		if (
			(int) $activity->upline_id !== 0 &&
			$activity->upline_id !== $activity->sponsor_id &&
			$activity->upline_id !== $activity->user_id
		) {
			$tmp = user($activity->upline_id);

			$str .= '<a href="' . sef(44) . qs() . 'uid=' .
				$activity->upline_id . '">' . $tmp->username . '</a><br>';
		}

		$str .= '</td>
	                <td>' . $activity->activity . '</td>
	            </tr>';
	}

	// 	$str .= '<tbody></table>';
	// } else {
	// 	$str .= '<hr><p>No activities yet.</p>';
	// }

	return $str;
}

/**
 *
 * @return array|mixed
 *
 * @since version
 */
function activity_all()
{
	return db()->setQuery(
		'SELECT * ' .
		'FROM network_activity ' .
		'ORDER BY activity_date DESC'
	)->loadObjectList();
}

/**
 * @param $limit_from
 * @param $limit_to
 *
 *
 * @return array|mixed
 * @since version
 */
function activities_lim($limit_from, $limit_to)
{
	return db()->setQuery(
		'SELECT * ' .
		'FROM network_activity ' .
		'ORDER BY activity_date DESC ' .
		'LIMIT ' . $limit_from . ', ' . $limit_to
	)->loadObjectList();
}

/**
 * @param $user_id
 *
 * @return array|mixed
 *
 * @since version
 */
function user_activity($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_activity ' .
		'WHERE user_id = ' . $db->quote($user_id) .
		' OR sponsor_id = ' . $db->quote($user_id) .
		' OR upline_id = ' . $db->quote($user_id)
	)->loadObjectList();
}

/**
 * @param $limit_from
 * @param $limit_to
 * @param $user_id
 *
 * @return array|mixed
 *
 * @since version
 */
function user_activities_lim($limit_from, $limit_to, $user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_activity ' .
		'WHERE user_id = ' . $db->quote($user_id) .
		' OR sponsor_id = ' . $db->quote($user_id) .
		' OR upline_id = ' . $db->quote($user_id) .
		' ORDER BY activity_date DESC ' .
		'LIMIT ' . $limit_from . ',' . $limit_to
	)->loadObjectList();
}

/**
 * @param $page
 * @param $start
 * @param $limit
 *
 * @return string
 *
 * @since version
 */
function paginate($page, $start, $limit): string
{
	$activities = activity_all();

	$total = count($activities);

	$last_page = ($total - $total % $limit) / $limit;

	$str = '<div style="float:right; margin-top:30px;">';

	if ($total > ($start + $limit)) {
		if ((int) $page !== (int) $last_page) {
			$str .= '<a href="' . sef(3) . qs() . 'page=' . ($last_page) .
				'" class="uk-button uk-button-primary">Oldest</a>';
		}

		$str .= '<a href="' . sef(3) . qs() . 'page=' . ($page + 1) .
			'" class="uk-button uk-button-success">Previous</a>';
	}

	if ($page > 0 && $page) {
		$str .= '<a href="' . sef(3) . qs() . 'page=' . ($page - 1) .
			'" class="uk-button uk-button-primary">Next</a>';

		if ((int) $page !== 1) {
			$str .= '<a href="' . sef(3) . qs() . 'page=' . (1) .
				'" class="uk-button uk-button-success">Latest</a>';
		}
	}

	$str .= '</div>';

	return $str;
}

/**
 * @param $start
 * @param $page
 * @param $limit
 *
 * @return string
 *
 * @since version
 */
function header_admin($start, $page, $limit): string
{
	$str = '<h1>Account Activity | ';
	$str .= ((int) $page === 0) ? ('Latest ' . count(activity_all())) :
		('Past ' . $start . ' - ' . ($start + $limit));
	$str .= '</h1>';

	return $str;
}

/**
 * @param $user_id
 * @param $start
 * @param $page
 * @param $limit
 *
 * @return string
 *
 * @since version
 */
function header_user($user_id, $start, $page, $limit): string
{
	$str = '<h1>Account Activity | ';
	$str .= ((int) $page === 0) ?
		('Latest ' . count(user_activity($user_id))) :
		('Past ' . $start . ' - ' . ($start + $limit));
	$str .= '</h1>';

	return $str;
}