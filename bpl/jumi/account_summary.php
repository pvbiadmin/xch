<?php

namespace BPL\Jumi\Account_Summary;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
require_once 'bpl/mods/account_summary.php';
require_once 'bpl/ajax/ajaxer/table_fast_track.php';
require_once 'bpl/mods/helpers.php';

use function Templates\SB_Admin\Tmpl\Master\main as master;

use function BPL\Mods\Account_Summary\row_referral_link;
use function BPL\Mods\Account_Summary\row_username;
use function BPL\Mods\Account_Summary\row_account_type;
use function BPL\Mods\Account_Summary\row_balance;
use function BPL\Mods\Account_Summary\row_efund;
use function BPL\Mods\Account_Summary\row_points;
use function BPL\Mods\Account_Summary\row_daily_incentive;
use function BPL\Mods\Account_Summary\row_merchant;
use function BPL\Mods\Account_Summary\ticker_coin_price;
use function BPL\Mods\Account_Summary\script_coin_price;

use function BPL\Ajax\Ajaxer\Table_Fast_Track\main as table_fast_track;
use function BPL\Mods\Url_SEF\sef;

// use function BPL\Mods\Helpers\menu;
use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\page_validate;
// use function BPL\Mods\Helpers\page_reload;
use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\settings;
use function BPL\Mods\Helpers\user;
use function BPL\Mods\Helpers\live_reload;

$content = main();

master($content);

function main()
{
	page_validate();

	$user = user(session_get('user_id'));

	// $str = menu();

	$str = live_reload(true);

	$str .= script_trading();
	// $str .= page_reload();
	$str .= header();

	$str .= '<table class="category table table-striped table-bordered table-hover">';

	$str .= row_referral_link($user);
	$str .= row_username($user);
	$str .= row_account_type($user);
	$str .= row_royalty($user);
	$str .= row_balance($user);
	$str .= row_efund($user);
	$str .= row_points($user);
	$str .= row_daily_incentive($user);
	$str .= row_merchant($user);

	$str .= '</table>';

	$str .= row_fast_track($user);

	// $table_account_details = account_details(true);

	// $str .= <<<HTML
	// <div class="container-fluid px-4">
	// 	<h1 class="mt-4">Account Summary</h1>
	// 	<ol class="breadcrumb mb-4">
	// 		<li class="breadcrumb-item active">List of Details</li>
	// 	</ol>				
	// 	$table_account_details
	// </div>
	// HTML;

	return $str;
}

// function account_details($counter)
// {
// 	$counter_span = '';

// 	if ($counter) {
// 		$counter_span = '<span id="counter" style="float:right">00:00:00</span>';
// 	}

// 	// $row_details = row_details();

// 	return <<<HTML
// 		<div class="card mb-4">
// 			<div class="card-header">
// 				<i class="fas fa-table me-1"></i>
// 				Details{$counter_span}
// 			</div>
// 			<div class="card-body">
// 				<table class="table">
// 					<!-- $row_details -->
// 				</table>
// 			</div>
// 		</div>
// 	HTML;
// }

// function row_details()
// {
// 	$str = <<<HTML
// 		<tbody>
// 		<tr>
// 			<th scope="row">Members</th>
// 			<td>$count_users{$button_directs}</td>			
// 		</tr>
// 		<tr>
// 			<th scope="row">Profit</th>
// 			<td>$total_sales_format $currency{$button_income_log}</td>
// 		</tr>
// 		<tr>
// 			<th scope="row">Payouts</th>
// 			<td colspan="2">$payouts_format $currency{$button_payout_log}</td>
// 		</tr>
// 		<tr>
// 			<th scope="row">Net Profit</th>
// 			<td colspan="2">$net_profit_format $currency{$button_payout_log}</td>
// 		</tr>
// 	</tbody>
// HTML;

// 	return $str;
// }

/**
 *
 * @return mixed|null
 *
 * @since version
 */
function token()
{
	return db()->setQuery(
		'SELECT * ' .
		'FROM network_fmc'
	)->loadObject();
}

/**
 *
 * @return string
 *
 * @since version
 */
function script_trading(): string
{
	$str = '';

	$jquery_number = 'bpl/plugins/jquery.number.js';

	if (settings('plans')->trading) {
		$str .= '<script>';

		$str .= script_coin_price();

		$str .= '</script>';
		$str .= '<script src="' . $jquery_number . '"></script>';
	}

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
	return '<h2>Account Summary' . ticker_coin_price() . '</h2>';
}

/**
 *
 * @param $user
 *
 * @return string
 *
 * @since version
 */
function row_royalty($user): string
{
	$settings_plans = settings('plans');

	$str = '';

	if ($settings_plans->royalty) {
		$str .= '<tr>
        <td>' . $settings_plans->royalty_name . ':</td>
        <td>' . settings('royalty')->{$user->rank . '_rank_name'} . '</td>
        </tr>';
	}

	return $str;
}

/**
 * @param $user_id
 *
 * @return array|mixed
 *
 * @since version
 */
function user_fast_track($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_fast_track ' .
		'WHERE user_id = ' . $db->quote($user_id)
	)->loadObjectList();
}

/**
 * @param $user
 *
 * @return bool
 *
 * @since version
 */
function has_fast_track($user): bool
{
	return count(user_fast_track($user->id)) === 1;
}

/**
 * @param $user
 *
 * @return string
 *
 * @since version
 */
function row_fast_track($user): string
{
	$settings_plans = settings('plans');

	$str = '';

	if (
		$user->account_type !== 'starter' &&
		$settings_plans->fast_track &&
		has_fast_track($user)
	) {
		$str .= '<br>
            <hr><br>
            <h2>' . $settings_plans->fast_track_name .
			'<span style="float: right">
                <a href="' . sef(104) . '" class="uk-button">Deposit</a>
                </span>
            </h2>
            <div class="table-responsive">
                <table class="category table table-bordered table-hover">
                    <tr>
                        <td rowspan="3" style="text-align: center; width: 33%; vertical-align: middle">
                            <div class="table-responsive" id="table_fast_track"></div>
                        </td>
                    </tr>
                </table>
            </div>';

		$str .= table_fast_track($user->id);
	}

	return $str;
}