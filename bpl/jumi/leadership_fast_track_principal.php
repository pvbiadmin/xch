<?php

namespace BPL\Jumi\Leadership_Fast_Track_Principal;

require_once 'bpl/leadership_fast_track_principal.php';
require_once 'templates/sb_admin/tmpl/master.tmpl.php';
require_once 'bpl/mods/helpers.php';

use function BPL\Leadership_Fast_Track_Principal\user_directs;
use function BPL\Leadership_Fast_Track_Principal\members_total;
use function BPL\Leadership_Fast_Track_Principal\lftp_total;
use function BPL\Leadership_Fast_Track_Principal\get_level_users;
use function BPL\Leadership_Fast_Track_Principal\calculate_level_bonus;

use function Templates\SB_Admin\Tmpl\Master\main as master;

use function BPL\Mods\Url_SEF\sef;

// use function BPL\Mods\Helpers\menu;
use function BPL\Mods\Helpers\user;
use function BPL\Mods\Helpers\settings;
use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\input_get;
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
	$user_id = session_get('user_id');

	page_validate();

	$uid = input_get('uid');

	if ($uid) {
		$user_id = $uid;
	}

	$str = live_reload(true);

	$view_status = view_status($user_id);
	$view_total_members = view_total_members($user_id);
	$view_total_bonus = view_total_bonus($user_id);
	$view_lftp = view_lftp($user_id, true);

	$str .= <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Royalty Bonus</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">Income Summary</li>
		</ol>
		<div class="row">
			$view_status
			$view_total_members
			$view_total_bonus
		</div>				
		$view_lftp
	</div>
	HTML;

	return $str;
}

function view_status($user_id)
{
	$slftp = settings('leadership_fast_track_principal');

	$user = user($user_id);

	$account_type = $user->account_type;

	$required_directs = $slftp->{$account_type . '_leadership_fast_track_principal_sponsored'};

	$user_directs = user_directs($user->id);

	$status = count($user_directs) >= $required_directs ? 'Active' : 'Inactive';

	$link_add_royalty = sef(19);

	return <<<HTML
		<div class="col-xl-3 col-md-6">
			<div class="card bg-primary text-white mb-4">
				<div class="card-body">Status<span id="direct_referral" style="float:right">
					$status</span></div>
				<div class="card-footer d-flex align-items-center justify-content-between">
					<span><a class="small text-white stretched-link" href="$link_add_royalty">Add Royalty</a></span>
					<div class="small text-white"><i class="fas fa-angle-right"></i></div>
				</div>
			</div>
		</div>
	HTML;
}

function view_total_members($user_id)
{
	$user = user($user_id);

	$total_members = members_total($user);

	$link_genealogy_lftp = sef(157);

	return <<<HTML
		<div class="col-xl-3 col-md-6">
			<div class="card bg-warning text-white mb-4">
				<div class="card-body">Total Members<span id="sponsored_members" style="float:right">
					$total_members</span></div>
				<div class="card-footer d-flex align-items-center justify-content-between">
					<a class="small text-white stretched-link" href="$link_genealogy_lftp">View Genealogy</a>
					<div class="small text-white"><i class="fas fa-angle-right"></i></div>
				</div>
			</div>
		</div>
	HTML;
}

function view_total_bonus($user_id)
{
	$sa = settings('ancillaries');
	$slftp = settings('leadership_fast_track_principal');

	$user = user($user_id);

	$account_type = $user->account_type;
	$level = $slftp->{$account_type . '_leadership_fast_track_principal_level'};

	$lftp_total = lftp_total($user, $level);

	$lftp_total = lftp_total($user, $level);
	$lftp_total_format = number_format($lftp_total, 8);

	$link_add_royalty = sef(19);

	return <<<HTML
		<div class="col-xl-3 col-md-6">
			<div class="card bg-success text-white mb-4">
				<div class="card-body">Royalty Bonus<span id="royalty_bonus" style="float:right">
					$lftp_total_format $sa->currency</span></div>
				<div class="card-footer d-flex align-items-center justify-content-between">
					<a class="small text-white stretched-link" href="$link_add_royalty">Add Bonus</a>
					<div class="small text-white"><i class="fas fa-angle-right"></i></div>
				</div>
			</div>
		</div>
	HTML;
}

function view_lftp($user_id, $counter): string
{
	$counter_span = '';

	if ($counter) {
		$counter_span = '<span id="counter" style="float:right">00:00:00</span>';
	}

	$table_profit_chart = table_profit_chart($user_id);

	$str = <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-table me-1"></i>
				Profit Chart{$counter_span}
			</div>
			<div class="card-body">
				<table id="datatablesSimple">
					$table_profit_chart
				</table>
			</div>
		</div>
	HTML;

	return $str;
}

function table_profit_chart($user_id)
{
	$row_profit_chart = row_profit_chart($user_id);

	$str = <<<HTML
		<thead>
			<tr>
				<th>Level</th>
				<th>Accounts</th>
				<th>Profit</th>
				<th>Fixed Rate (%)</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Level</th>
				<th>Accounts</th>
				<th>Profit</th>
				<th>Fixed Rate (%)</th>
			</tr>
		</tfoot>
		<tbody>
		$row_profit_chart						
		</tbody>
	HTML;

	return $str;
}

function row_profit_chart($user_id)
{
	$slftp = settings('leadership_fast_track_principal');

	$user = user($user_id);

	$account_type = $user->account_type;
	$level = $slftp->{$account_type . '_leadership_fast_track_principal_level'};

	$str = '';

	// Generate rows for each level
	for ($i = 1; $i <= $level; $i++) {
		$str .= view_row($i, $user);
	}

	return $str;
}

/**
 * Generate a table row for a specific level.
 *
 * @param int $level The level.
 * @param object $user The user object.
 * @return string The HTML row.
 */
function view_row($level, $user): string
{
	$slftp = settings('leadership_fast_track_principal');

	$level_users = get_level_users($user, $level);

	$members = count($level_users);
	$bonus = calculate_level_bonus($user, $level);
	$bonus_format = number_format($bonus, 8);

	// Calculate the percentage based on the account type and level
	$share = $slftp->{$user->account_type . '_leadership_fast_track_principal_share_' . $level};
	$share_cut = $slftp->{$user->account_type . '_leadership_fast_track_principal_share_cut_' . $level};

	$percentage = $share * $share_cut / 100;
	$percentage_format = number_format($percentage, 2);

	return <<<HTML
		<tr>
			<td>$level</td>
			<td>$members</td>
			<td>$bonus_format</td>
			<td>$percentage_format</td>
		</tr>
	HTML;
}