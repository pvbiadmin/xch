<?php

namespace BPL\Jumi\Active_Income;

require_once 'bpl/leadership_fast_track_principal.php';
require_once 'templates/sb_admin/tmpl/master.tmpl.php';
require_once 'bpl/mods/income.php';
require_once 'bpl/mods/account_summary.php';
require_once 'bpl/mods/root_url_upline.php';
require_once 'bpl/mods/time_remaining.php';
require_once 'bpl/mods/helpers.php';

// use Joomla\CMS\Uri\Uri;

use DateTime;
use DateInterval;

use function BPL\Leadership_Fast_Track_Principal\lftp_total;

use function BPL\Mods\Account_Summary\row_referral_link;
use function BPL\Mods\Account_Summary\row_username;
use function BPL\Mods\Account_Summary\row_account_type;

use function BPL\Mods\Root_Url_Upline\main as root_url;

use function BPL\Mods\Time_Remaining\main as time_remaining;

use function Templates\SB_Admin\Tmpl\Master\main as master;

//use function BPL\Mods\Account_Summary\row_balance;
// use function BPL\Mods\Account_Summary\row_efund;
use function BPL\Mods\Account_Summary\row_points;
use function BPL\Mods\Account_Summary\row_daily_incentive;
use function BPL\Mods\Account_Summary\row_merchant;

use function BPL\Mods\Income\income_marketing;
use function BPL\Mods\Url_SEF\sef;

// use function BPL\Mods\Helpers\application;
use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\settings;
use function BPL\Mods\Helpers\page_validate;
// use function BPL\Mods\Helpers\page_reload;
// use function BPL\Mods\Helpers\menu;
use function BPL\Mods\Helpers\user;
use function BPL\Mods\Helpers\db;
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

	$str = live_reload(true);

	switch (session_get('usertype')) {
		case 'Admin':
			$str .= admin($user_id);

			break;
		case 'Member':
			$str .= member($user_id, true);

			break;
	}

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
function member($user_id, $counter = false): string
{
	$str = '';

	$sp = settings('plans');

	$reactivate_binary = '';

	if ($sp->binary_pair && user_binary($user_id)) {
		$user_binary = user_binary($user_id);

		$status = $user_binary->status;

		$reactivate_count = $user_binary->reactivate_count;
		$capping_cycle_max = settings('binary')->{$user_binary->account_type . '_capping_cycle_max'};

		if ($status === 'inactive' && $reactivate_count < $capping_cycle_max) {
			$reactivate_binary = <<<HTML
				<div class="reactivate_binary notification alert alert-info alert-dismissible fade show" 
					role="alert">Reactivate $sp->binary_pair_name!<button type="button" class="btn-close" 
						data-bs-dismiss="alert" aria-label="Close"></button>
				</div>	
			HTML;
		}
	}

	$view_agent_dashboard = view_agent_dashboard($user_id, $counter);
	$view_fast_track = view_fast_track($user_id);

	$str .= <<<HTML
		<div class="container-fluid px-4">
			<h1 class="mt-4">Marketing Plan</h1>
			<ol class="breadcrumb mb-4">
				<li class="breadcrumb-item active">Income Summary</li>
			</ol>
			$reactivate_binary						
			$view_agent_dashboard
			$view_fast_track
		</div>
	HTML;

	// $str .= tableStyle();

	// $str .= '<div class="card">';
	// $str .= '<div class="card-header">Agent Dashboard</div>';
	// $str .= '<div class="table-responsive">';
	// $str .= '<table class="category table table-striped table-bordered table-hover" style="width: 100%;">';

	// $str .= core($user_id);

	// $str .= '</table>';
	// $str .= '</div></div>';

	// $str .= table_binary_summary($user_id);
	// $str .= user_info(user($user_id));

	return $str;
}

function view_agent_dashboard($user_id, $counter): string
{
	$sa = settings('ancillaries');

	$user = user($user_id);

	$total_income = total_income($user_id);
	$available_balance = $user->payout_transfer;

	$total_income_format = number_format($total_income, 8);
	$available_balance_format = number_format($available_balance, 8);

	$counter_span = '';

	if ($counter) {
		$counter_span = '<span id="counter" style="float:right">00:00:00</span>';
	}

	$view_account_status = view_account_status($user_id);
	$view_direct_agents = view_direct_agents($user_id);
	$view_direct_referral_ftp = view_direct_referral_ftp($user_id);
	$view_lftp = view_lftp($user_id);

	$str = <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-table me-1"></i>
				Dashboard{$counter_span}
			</div>
			<div class="card-body">
				<div class="row">
					$view_account_status
					$view_direct_agents	
					$view_direct_referral_ftp				
					$view_lftp
				</div>
			</div>
			<div class="card-footer small text-muted">
				<i class="fas fa-money-bill me-1"></i>
				Total Income: $total_income_format $sa->currency
				<span style="float:right"><i class="fas fa-wallet me-1"></i>
				Wallet Available Balance: $available_balance_format $sa->currency</span>
			</div>
		</div>
	HTML;

	return $str;
}

function view_account_status($user_id)
{
	$sp = settings('plans');

	$user = user($user_id);

	$account_status = $user->status_global;

	$view_account_status = ucwords($account_status);

	// $reactivate_link = sef(130);

	$account_type_format = ucwords($user->account_type);

	$reactivate = <<<HTML
		<span class="small">Package: $account_type_format</span>
	HTML;

	// if (!$sp->account_freeze && $account_status === 'inactive') {
	// 	$reactivate = <<<HTML
	// 		<span><a class="small stretched-link" href="$reactivate_link">Reactivate Account</a></span>
	// 	HTML;
	// }

	return <<<HTML
		<div class="col-xl-3 col-md-6">
			<div class="card mb-4">
				<div class="card-body">Package Status<span id="account_status" style="float:right">
					$view_account_status</span></div>
				<div class="card-footer d-flex align-items-center justify-content-between">
					$reactivate
					<div class="small"><i class="fas fa-angle-right"></i></div>
				</div>
			</div>
		</div>
	HTML;
}

function view_direct_agents($user_id)
{
	$user = user($user_id);

	$link_sponsored_members = sef(13);

	$user_directs = user_direct($user->id);

	$count_directs = count($user_directs);

	return <<<HTML
		<div class="col-xl-3 col-md-6">
			<div class="card mb-4">
				<div class="card-body">Direct Agents<span id="direct_agents" style="float:right">
					$count_directs</span></div>
				<div class="card-footer d-flex align-items-center justify-content-between">
					<span class="small"><a href="$link_sponsored_members">View Sponsored Members</a></span>
					<div class="small"><i class="fas fa-angle-right"></i></div>
				</div>
			</div>
		</div>
	HTML;
}

function view_direct_referral_ftp($user_id)
{
	$sp = settings('plans');
	$sa = settings('ancillaries');

	if (!$sp->direct_referral_fast_track_principal) {
		return '';
	}

	$currency = $sa->currency;

	$user = user($user_id);

	$income_referral_ftp = $user->income_referral_fast_track_principal;
	$income_referral_ftp_format = number_format($income_referral_ftp, 2);

	$link = 'http://' . $_SERVER['SERVER_NAME'] . root_url() . '/' . $user->username;

	return <<<HTML
		<div class="col-xl-3 col-md-6">
			<div class="card mb-4">
				<div class="card-body">{$sp->direct_referral_fast_track_principal_name}<span id="direct_referral_ftp" style="float:right">
					$income_referral_ftp_format $currency</span></div>
				<div class="card-footer d-flex align-items-center justify-content-between">
					<span class="small">Referral Link: <a href="$link">$link</a></span>					
					<div class="small"><i class="fas fa-angle-right"></i></div>
				</div>
			</div>
		</div>
	HTML;
}

function view_lftp($user_id)
{
	$sa = settings('ancillaries');
	$sp = settings('plans');
	$slftp = settings('leadership_fast_track_principal');

	if (!$sp->leadership_fast_track_principal) {
		return '';
	}

	$user = user($user_id);

	$account_type = $user->account_type;
	$level = $slftp->{$account_type . '_leadership_fast_track_principal_level'};

	$lftp_total = lftp_total($user, $level);
	$lftp_total_format = number_format($lftp_total, 8);

	$lftp_name = $sp->leadership_fast_track_principal_name;

	$link_add_royalty = sef(19);

	return <<<HTML
		<div class="col-xl-3 col-md-6">
			<div class="card mb-4">
				<div class="card-body">$lftp_name<span id="lftp" style="float:right">
					$lftp_total_format $sa->currency</span></div>
				<div class="card-footer d-flex align-items-center justify-content-between">
					<a class="small" href="$link_add_royalty">Add Package</a>
					<div class="small"><i class="fas fa-angle-right"></i></div>
				</div>
			</div>
		</div>
	HTML;
}

function total_income($user_id)
{
	$user = user($user_id);

	$income_referral_ftp = $user->income_referral_fast_track_principal;
	$bonus_leadership_ftp = $user->bonus_leadership_fast_track_principal;

	return $income_referral_ftp + $bonus_leadership_ftp;
}

function view_fast_track($user_id)
{
	$sa = settings('ancillaries');

	$user = user($user_id);

	$fast_track_interest = $user->fast_track_interest;
	$fast_track_interest_format = number_format($fast_track_interest, 8);

	$table_fast_track = table_fast_track($user_id);

	return <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-table me-1"></i>
				Passive Program<span style="float:right">
					Passive Wallet: $fast_track_interest_format $sa->currency</span>
			</div>
			<div class="card-body">
				<table id="datatablesSimple">
					$table_fast_track
				</table>
			</div>
		</div>
	HTML;
}

function table_fast_track($user_id)
{
	$si = settings('investment');

	$user = user($user_id);

	$account_type = $user->account_type;

	$maturity = $si->{$account_type . '_fast_track_maturity'};

	$row_fast_track = row_fast_track($user_id);

	$str = <<<HTML
		<thead>
			<tr>
				<th>Package</th>
				<th>Profit</th>
				<th>Day</th>
				<th>Time Frame ($maturity days)</th>
				<th>Remarks</th>							
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Package</th>
				<th>Profit</th>
				<th>Day</th>
				<th>Time Frame ($maturity days)</th>
				<th>Remarks</th>							
			</tr>
		</tfoot>
		<tbody>
			$row_fast_track						
		</tbody>
	HTML;

	return $str;
}

function row_fast_track($user_id)
{
	$si = settings('investment');

	$user = user($user_id);

	$account_type = $user->account_type;

	$interval = $si->{$account_type . '_fast_track_interval'};
	$maturity = $si->{$account_type . '_fast_track_maturity'};

	$fast_tracks = user_fast_track($user_id);

	$str = '';

	if (empty($fast_tracks)) {
		$str .= <<<HTML
			<tr>
				<td>0.00</td>
				<td>0.00</td>
				<td>0</td>
				<td>n/a</td>
				<td>n/a</td>				
			</tr>					
		HTML;
	} else {
		foreach ($fast_tracks as $ft) {
			$start = new DateTime('@' . $ft->date_entry);
			$end = new DateInterval('P' . $maturity . 'D');

			$start->add($end);

			$starting_value = number_format($ft->principal, 2);
			$current_value = number_format($ft->value_last, 2);
			$maturity_date = $start->format('F d, Y');
			$status = time_remaining($ft->day, $ft->processing, $interval, $maturity);

			$remaining = ($ft->processing + $maturity - $ft->day) * $interval;
			$remain_maturity = ($maturity - $ft->day) * $interval;

			$type_day = '';

			if ($remaining > $maturity && $ft->processing) {
				$type_day = 'Days for Processing: ';
			} elseif ($remain_maturity > 0) {
				$type_day = 'Days Remaining: ';
			}

			$str .= <<<HTML
				<tr>
					<td>$starting_value</td>
					<td>$current_value</td>
					<td>$ft->day</td>
					<td>$maturity_date</td>
					<td>{$type_day}{$status}</td>				
				</tr>
			HTML;
		}
	}

	return $str;
}

function user_fast_track($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_fast_track ' .
		'WHERE user_id = ' . $db->quote($user_id) .
		' ORDER BY fast_track_id DESC'
	)->loadObjectList();
}

/**
 * @param $user_id
 * @param $type
 *
 * @return array|mixed
 *
 * @since version
 */
function user_power($user_id, $type)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_power_' . $type .
		' WHERE user_id = ' . $db->quote($user_id)
	)->loadObjectList();
}

/**
 * @param $user_id
 *
 * @return mixed|null
 *
 * @since version
 */
function binary($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_binary ' .
		'WHERE user_id = ' .
		$db->quote($user_id)
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
		'FROM network_users u ' .
		'INNER JOIN network_binary b ' .
		'ON u.id = b.user_id ' .
		'WHERE user_id = ' . $db->quote($user_id)
	)->loadObject();
}

function user_indirect($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_indirect ' .
		'WHERE user_id = ' . $db->quote($user_id)
	)->loadObject();
}

/**
 * @param $user_id
 *
 * @return mixed|null
 *
 * @since version
 */
function user_unilevel($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_unilevel ' .
		'WHERE user_id = ' . $db->quote($user_id)
	)->loadObject();
}

/**
 *
 * @param $user_id
 *
 * @return string
 *
 * @since version
 */
function row_income_active_total($user_id): string
{
	return '<tr>
	        <td><a href="javascript:void(0)">Total Income</a>:</td>
	        <td>' . number_format(income_marketing($user_id), 8) . ' ' .
		settings('ancillaries')->currency .
		'</td>
	    </tr>';
}

/**
 * @param $user_id
 *
 * @return array|mixed
 *
 * @since version
 */
function user_direct($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_users ' .
		'WHERE sponsor_id = ' . $db->quote($user_id)
	)->loadObjectList();
}

/**
 *
 * @param $user_id
 *
 * @return string
 *
 * @since version
 */
function row_direct_referral($user_id): string
{
	$settings_plans = settings('plans');
	$settings_ancillaries = settings('ancillaries');

	$str = '';

	if (
		$settings_plans->direct_referral &&
		$settings_ancillaries->referral_mode === 'standard'
	) {
		$str .= '<tr>
	            <td><a href="' .
			sef(13) . '">Direct Agents</a>:</td>
	            <td>' . count(user_direct($user_id)) . '</td>
	        </tr>
	        <tr>
	            <td><a href="javascript:void(0)">' .
			$settings_plans->direct_referral_name . '</a>:</td>
	            <td>' . number_format(user($user_id)->income_referral, 8) .
			' ' . $settings_ancillaries->currency .
			'</td>
	        </tr>';
	}

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
function row_indirect_referral($user_id): string
{
	$settings_plans = settings('plans');

	$user = user($user_id);

	$str = '';

	if (
		$settings_plans->indirect_referral &&
		settings('indirect_referral')->{$user->account_type . '_indirect_referral_level'}
	) {
		$str .= '<tr>
	            <td><a href="javascript:void(0)">' .
			$settings_plans->indirect_referral_name . '</a>:</td>
	            <td>' . number_format($user->bonus_indirect_referral, 8) .
			' ' . settings('ancillaries')->currency .
			'</td>
	        </tr>';
	}

	return $str;
}

/**
 *
 * @param         $user_id
 *
 * @return string
 *
 * @since version
 */
function row_binary($user_id): string
{
	//	$app = application();

	$sp = settings('plans');
	$sb = settings('binary');

	$binary = binary($user_id);

	$str = '';

	//	$flushout = $sb->hedge === 'flushout';

	if ($binary && ($sp->binary_pair || $sp->redundant_binary)) {
		$user_binary = user_binary($user_id);

		$status = $user_binary->status;

		//		if ($status === 'inactive')
//		{
//			$app->redirect(Uri::root(true) . '/' .
//				sef(2), 'Reactivate Your ' . $sp->binary_pair_name . '!', 'notice');
//		}

		//		$account_type = $user_binary->account_type;

		//		$reactivate_count = $binary->reactivate_count;
//		$income_cycle     = $binary->income_cycle;
//
//		$s_capping_cycle_max = $sb->{$account_type . '_capping_cycle_max'};
//		$s_maximum_income    = $sb->{$account_type . '_maximum_income'};

		//		$reactivate = $reactivate_count >= $s_capping_cycle_max ?
//			($income_cycle >= $s_maximum_income ?
//				'<a style="float:right; color: orange" href="' . sef(110) . '">Upgrade Account</a>' : '') :
//			(($status === 'active'
//				|| $status === 'reactivated'
//				|| $status === 'graduate'
//			) ? '' :
//				'<a style="float:right" href="' . sef(120) . '">Reactivate Binary</a>');

		switch ($status) {
			case 'active':
				$flag = 'Active';
				break;
			case 'reactivated':
				$flag = 'Reactivated';
				break;
			case 'graduate':
				$flag = 'Maxed Out';
				break;
			default:
				$flag = 'Inactive';
				break;
		}

		$str .= '<tr>
            <td><a href="javascript:void(0)">' . $sp->binary_pair_name . /* ' (' . $flag . ')' . */ '</a>:</td>
           	<td>' . number_format($binary->income_cycle, 8) . ' ' .
			settings('ancillaries')->currency . /*($flushout ? '' : $reactivate) .*/
			'</td></tr>';
	}

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
function row_passup_binary($user_id): string
{
	$settings_plans = settings('plans');

	$str = '';

	if (
		$settings_plans->passup_binary &&
		$settings_plans->binary_pair &&
		binary($user_id)
	) {
		$str .= '<tr>
	        <td><a href="javascript:void(0)">' .
			$settings_plans->passup_binary_name . '</a>:</td>
	        <td>' . number_format(user($user_id)->passup_binary_bonus, 8) .
			' ' . settings('ancillaries')->currency .
			'</td>
	        </tr>';
	}

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
function row_leadership_binary($user_id): string
{
	$settings_plans = settings('plans');

	$str = '';

	if (
		$settings_plans->leadership_binary &&
		$settings_plans->binary_pair &&
		binary($user_id)
	) {
		$str .= '<tr>
	        <td><a href="javascript:void(0)">' .
			$settings_plans->leadership_binary_name . '</a>:</td>
	        <td>' . number_format(user($user_id)->bonus_leadership, 8) .
			' ' . settings('ancillaries')->currency .
			'</td>
	        </tr>';
	}

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
function row_leadership_passive($user_id): string
{
	$settings_plans = settings('plans');

	return $settings_plans->leadership_passive ? '<tr>
	            <td><a href="javascript:void(0)">' .
		$settings_plans->leadership_passive_name . '</a>:</td>
	            <td>' . number_format(user($user_id)->bonus_leadership_passive, 8) .
		' ' . settings('ancillaries')->currency . '</td>
	        </tr>' : '';
}

/**
 *
 * @param $user_id
 *
 * @return string
 *
 * @since version
 */
function row_matrix($user_id): string
{
	$settings_plans = settings('plans');

	return $settings_plans->matrix ? '<tr>
	            <td><a href="javascript:void(0)">' .
		$settings_plans->matrix_name . '</a>:</td>
	            <td>' . number_format(user($user_id)->bonus_matrix, 8) .
		' ' . settings('ancillaries')->currency . '</td>
	        </tr>' : '';
}

/**
 *
 * @param $user_id
 *
 * @return string
 *
 * @since version
 */
function row_harvest($user_id): string
{
	$settings_plans = settings('plans');

	return $settings_plans->harvest ? '<tr>
	        <td><a href="javascript:void(0)">' . $settings_plans->harvest_name . '</a>:</td>
	        <td>' . number_format(user($user_id)->bonus_harvest, 8) .
		' ' . settings('ancillaries')->currency . '</td>
	    </tr>' : '';
}

/**
 *
 * @param $user_id
 *
 * @return string
 *
 * @since version
 */
function row_power($user_id): string
{
	$settings_plans = settings('plans');

	$str = '';

	if (
		$settings_plans->power &&
		(user_power($user_id, 'executive') ||
			user_power($user_id, 'regular') ||
			user_power($user_id, 'associate') ||
			user_power($user_id, 'basic'))
	) {
		$str .= '<tr>
	            <td><a href="javascript:void(0)">' .
			$settings_plans->power_name . '</a>:</td>
	            <td>' . number_format(user($user_id)->bonus_power, 8) .
			' ' . settings('ancillaries')->currency .
			'</td>
	        </tr>';
	}

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
function row_unilevel($user_id): string
{
	$sp = settings('plans');
	$sul = settings('unilevel');

	$unilevel_maintain = user_unilevel($user_id)->period_unilevel ?? 0;

	$user = user($user_id);

	$str = '';

	if (
		$sp->unilevel
		&& $sul->{$user->account_type . '_unilevel_level'}
		&& !empty(user_unilevel($user_id))
	) {
		$requirement = $sul->{$user->account_type . '_unilevel_maintenance'};
		$offset = abs($unilevel_maintain - $requirement);
		$has_maintain = $unilevel_maintain >= $requirement;

		$str .= '<tr>
	            <td><a href="javascript:void(0)">' .
			$sp->unilevel_name . '</a>:</td>
	            <td>' . number_format($user->unilevel, 8) .
			' ' . settings('ancillaries')->currency .
			'<a style="float:right" href="' . ($has_maintain ? sef(109) : sef(9)) . '">' .
			($has_maintain ? 'Active' : $offset . ' pts. to activate') . '</a></td>
	        </tr>';
	}

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
function row_royalty($user_id): string
{
	$settings_plans = settings('plans');

	return $settings_plans->royalty ? '<tr>
	            <td><a href="javascript:void(0)">' .
		$settings_plans->royalty_name . '</a>:</td>
	            <td>' . number_format(user($user_id)->rank_reward, 8) .
		' ' . settings('ancillaries')->currency . '</td>
	        </tr>' : '';
}

/**
 *
 * @param $user_id
 *
 * @return string
 *
 * @since version
 */
function row_echelon($user_id): string
{
	$settings_plans = settings('plans');

	return $settings_plans->echelon ? '<tr>
	            <td><a href="javascript:void(0)">' .
		$settings_plans->echelon_name . '</a>:</td>
	            <td>' . number_format(user($user_id)->bonus_echelon, 8) .
		' ' . settings('ancillaries')->currency . '</td>
	        </tr>' : '';
}

/**
 *
 * @param $user_id
 *
 * @return string
 *
 * @since version
 */
function row_upline_support($user_id): string
{
	$settings_plans = settings('plans');

	return $settings_plans->upline_support ? '<tr>
	            <td><a href="javascript:void(0)">' .
		$settings_plans->upline_support_name . '</a>:</td>
	            <td>' . number_format(user($user_id)->upline_support, 8) .
		' ' . settings('ancillaries')->currency . '</td>
	        </tr>' : '';
}

/**
 *
 * @param $user_id
 *
 * @return string
 *
 * @since version
 */
function row_passup($user_id): string
{
	$settings_plans = settings('plans');

	return $settings_plans->passup ? '<tr>
	            <td><a href="javascript:void(0)">' .
		$settings_plans->passup_name . '</a>:</td>
	            <td>' . number_format(user($user_id)->passup_bonus, 8) .
		' ' . settings('ancillaries')->currency . '</td>
	        </tr>' : '';
}

/**
 *
 * @param $user_id
 *
 * @return string
 *
 * @since version
 */
function row_elite($user_id): string
{
	$settings_plans = settings('plans');

	$user = user($user_id);

	$str = '';

	if ($settings_plans->elite_reward && $user->elite) {
		$str .= '<tr>
	            <td><a href="javascript:void(0)">' .
			$settings_plans->elite_reward_name . '</a>:</td>
	            <td>' . number_format($user->elite_reward, 8) .
			' ' . settings('ancillaries')->currency .
			'</td>
	        </tr>';
	}

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
function row_stockist($user_id): string
{
	$user = user($user_id);

	$str = '';

	if ($user->account_type === 'regular' && settings('entry')->regular_global) {
		$str .= '<tr>
	            <td><a href="javascript:void(0)">Stockist Bonus</a>:</td>
	            <td>' . number_format($user->stockist_bonus, 8) .
			' ' . settings('ancillaries')->currency .
			'</td>
	        </tr>';
	}

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
function row_franchise($user_id): string
{
	$user = user($user_id);

	$str = '';

	if ($user->account_type === 'executive' && settings('entry')->executive_global) {
		$str .= '<tr>
	            <td><a href="javascript:void(0)">Franchise Bonus</a>:</td>
	            <td>' . number_format($user->franchise_bonus, 8) .
			' ' . settings('ancillaries')->currency .
			'</td>
	        </tr>';
	}

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
function row_balance($user_id): string
{
	$sa = settings('ancillaries');
	$sp = settings('plans');

	$user = user($user_id);

	$field_balance = $sa->withdrawal_mode === 'standard' ?
		'balance' : 'payout_transfer';

	$reactivate = $user->status_global === 'active' ? '' :
		'<a style="float:right" href="' . sef(130) . '">Reactivate Account</a>';

	return '<tr>
	        <td><a href="javascript:void(0)">Wallet Balance</a>:</td>
	        <td>' . number_format(user($user_id)->{$field_balance}, 8) .
		' ' . $sa->currency . (!$sp->account_freeze ? '' : $reactivate) .
		'</td>
	    </tr>';
}

/**
 *
 * @param $user_id
 *
 * @return string
 *
 * @since version
 */
function table_binary_summary($user_id): string
{
	$sp = settings('plans');

	$user = user($user_id);

	$binary = binary($user_id);

	$str = '';

	if ($binary && ($sp->binary_pair || $sp->redundant_binary)) {
		// $str .= '<hr class="uk-grid-divider">
		//     <h3>' . $sp->binary_pair_name . ' Summary</h3>';
		$str .= '<div class="card">
			<div class="card-header">' . $sp->binary_pair_name . ' Dashboard</div>
        		<div class="table-responsive">';
		$str .= '<table class="category table table-striped table-bordered table-hover">
		        <tr>
		            <td><a href="' . sef(14) . '">Group Points (A/B):</a></td>
		            <td>' . $binary->ctr_left . ' / ' .
			$binary->ctr_right . /* '<a style="float:right" href="' . sef(14) . '">View All Downlines</a>' . */ '
		            </td>
		        </tr>
		        <tr>
		            <td><a href="javascript:void(0)">Match Bonus</a>:</td>
		            <td>' . number_format($binary->pairs, 8) . ' USDT</td>
		        </tr>';

		$str .= settings('binary')->{user($user_id)->account_type . '_pairs_safety'} ? '<tr>
	            <td><a href="javascript:void(0)">Loyalty Bonus</a>:</td>
	            <td>' . number_format($binary->income_cycle, 8) . ' USDT</td>
	        </tr>
	        <tr>
	            <td><a href="javascript:void(0)">Loyalty Token</a>:</td>
	            <td>' . /* $binary->income_giftcheck */ number_format($user->fifth_pair_token_balance, 8) . ' B2P<a style="float:right" href="' . sef(153) . '">Withdraw B2P</a></td>
	        </tr>' . /* '
<tr>
<td><a href="javascript:void(0)">Flush Out (pts.)</a>:</td>
<td>' . number_format($binary->income_flushout) . '</td>
</tr>' . */ '
	        <tr>
	            <td><a href="javascript:void(0)">Total Earned Token Today</a>:</td>
	            <td>' . number_format($binary->pairs_today_total, 8) . ' B2P</td>
	        </tr>' : '';

		$str .= '</table>';
		$str .= '</div></div>';
	}

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
function admin($user_id): string
{
	// $str = page_reload();

	// $str .= tableStyle();

	$str = '<h2>Profit Chart</h2>';
	$str .= '<div class="card">
		<div class="table-responsive">';
	$str .= '<table class="category table table-striped table-bordered table-hover" style="width: 100%;">';

	$str .= core($user_id);

	$str .= '</table>';
	$str .= '</div></div>';

	$str .= table_binary_summary($user_id);

	return $str;
}

function user_info($user): string
{
	$str = '<div class="card">
		<div class="card-header">User Dashboard</div>
			<div class="table-responsive">';
	$str .= '<table class="category table table-striped table-bordered table-hover">';

	$str .= row_referral_link($user);
	$str .= row_username($user);
	$str .= row_account_type($user);
	// $str .= row_balance($user);
	// $str .= row_efund($user);
	$str .= '</table>';
	$str .= '</div></div>';

	$row_points = row_points($user);
	$row_daily_incentive = row_daily_incentive($user);
	$row_merchant = row_merchant($user);

	if ($row_points || $row_daily_incentive || $row_merchant) {
		$str .= '<div class="card">
			<div class="card-header">Support Program</div>
				<div class="table-responsive">';
		$str .= '<table class="category table table-striped table-bordered table-hover">';

		$str .= row_points($user);
		$str .= row_daily_incentive($user);
		$str .= row_merchant($user);

		$str .= '</table>';
		$str .= '</div></div>';
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
function core($user_id): string
{
	$str = '';
	$str = row_direct_referral($user_id);
	$str .= row_indirect_referral($user_id);
	// $str .= row_binary($user_id);
	$str .= row_passup_binary($user_id);
	$str .= row_leadership_binary($user_id);
	$str .= row_leadership_passive($user_id);
	$str .= row_matrix($user_id);
	$str .= row_harvest($user_id);
	$str .= row_power($user_id);
	$str .= row_unilevel($user_id);
	$str .= row_royalty($user_id);
	$str .= row_echelon($user_id);
	$str .= row_upline_support($user_id);
	$str .= row_passup($user_id);
	$str .= row_elite($user_id);
	$str .= row_stockist($user_id);
	$str .= row_franchise($user_id);
	$str .= row_income_active_total($user_id);
	$str .= row_balance($user_id);

	return $str;
}

function tableStyle(): string
{
	return <<<HTML
		<style>
			.card {
				background: white;
				border-radius: 8px;
				box-shadow: 0 2px 4px rgba(0,0,0,0.1);
				margin-bottom: 20px;
			}

			.card-header {
				background: #007bff;
				color: #ffffff;
				padding: 15px 20px;
				border-bottom: 1px solid #2d2d2d;
				border-radius: 8px 8px 0 0;
				font-size: 1.17em;
				font-weight: bold;
			}

			.table-responsive {
				overflow-x: auto;
				-webkit-overflow-scrolling: touch;
			}

			table {
				width: 100%;
				border-collapse: collapse;
				margin-bottom: 0;
			}

			.table-striped tbody tr:nth-of-type(odd) {
				background-color: rgba(0,0,0,.05);
			}

			.table-bordered {
				border: 1px solid #dee2e6;
			}

			th, td {
				padding: 12px;
				border: 1px solid #dee2e6;
			}

			a {
				color: #007bff;
				text-decoration: none;
			}

			a:hover {
				text-decoration: underline;
			}

			/* Mobile responsiveness */
			@media screen and (max-width: 768px) {
				.rewards-table td {
					display: block;
					width: 100%;
					box-sizing: border-box;
				}

				.rewards-table td:first-child {
					font-weight: bold;
					background: #f8f9fa;
				}

				.rewards-table td a {
					word-break: break-all;
				}
			}
		</style>
	HTML;
}