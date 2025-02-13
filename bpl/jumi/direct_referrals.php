<?php

namespace BPL\Jumi\Direct_Referrals;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
require_once 'bpl/mods/helpers.php';

use function BPL\Mods\Url_SEF\sef;
use function BPL\Mods\Url_SEF\qs;

use function Templates\SB_Admin\Tmpl\Master\main as master;

use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\input_get;
use function BPL\Mods\Helpers\page_validate;
use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\settings;
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
	page_validate();

	$uid = input_get('uid');

	$user_id = $uid !== '' ? $uid : session_get('user_id');

	$str = live_reload(true);

	$view_sponsored_members = view_sponsored_members($user_id, true);

	$str .= <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Sponsored</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">List of Sponsored Members</li>
		</ol>				
		$view_sponsored_members
	</div>
	HTML;

	return $str;
}

function view_sponsored_members($user_id, $counter)
{
	$counter_span = '';

	if ($counter) {
		$counter_span = '<span id="counter" style="float:right">00:00:00</span>';
	}

	$table_sponsored_members = table_sponsored_members($user_id);

	return <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-table me-1"></i>
				Sponsored{$counter_span}
			</div>
			<div class="card-body">
				<table id="datatablesSimple">
					$table_sponsored_members
				</table>
			</div>
		</div>
	HTML;
}

function table_sponsored_members($user_id)
{
	$row_sponsored_members = row_sponsored_members($user_id);

	$str = <<<HTML
		<thead>
			<tr>
				<th>#</th>
				<th>Direct Username</th>
				<th>Package</th>				
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>#</th>
				<th>Direct Username</th>
				<th>Package</th>				
			</tr>
		</tfoot>
		<tbody>
			$row_sponsored_members						
		</tbody>
	HTML;

	return $str;
}

function row_sponsored_members($user_id)
{
	$se = settings('entry');
	$sr = settings('royalty');

	$directs = user_directs($user_id);

	$str = '';

	// if (empty($directs)) {
	// 	$str .= <<<HTML
	// 		<tr>
	// 			<td>0</td>
	// 			<td>n/a</td>
	// 			<td>n/a</td>
	// 			<td>n/a</td>							
	// 		</tr>					
	// 	HTML;
	// } else {
	$ctr = 0;

	foreach ($directs as $member) {
		$ctr++;

		$profile_link = sef(44) . qs() . 'uid=' . $member->id;

		$package_name = $se->{$member->account_type . '_package_name'};
		$rank_name = $sr->{$member->rank . '_rank_name'};

		$str .= <<<HTML
				<tr>
					<td>$ctr</td>
					<td><a href="$profile_link">$member->username</a></td>
					<td>$package_name</td>
					<!-- <td>$rank_name</td> -->									
				</tr>
			HTML;
	}
	// }

	return $str;
}

/**
 * @param $user_id
 *
 * @return array|mixed
 *
 * @since version
 */
function user_directs($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_users ' .
		'WHERE sponsor_id = ' . $db->quote($user_id)
	)->loadObjectList();
}