<?php

namespace BPL\Jumi\Sales_Overview;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
require_once 'bpl/mods/helpers.php';

use Joomla\CMS\Uri\Uri;

use function Templates\SB_Admin\Tmpl\Master\main as master;

use function BPL\Mods\Url_SEF\sef;

use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\application;
use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\settings;
use function BPL\Mods\Helpers\users;
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

	page_validate($usertype);

	$str = live_reload(true);

	$view_sales = view_sales(true);

	$str .= <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Profit Summary</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">List of income</li>
		</ol>				
		$view_sales
	</div>
	HTML;

	return $str;
}

function view_sales($counter)
{
	$counter_span = '';

	if ($counter) {
		$counter_span = '<span id="counter" style="float:right">00:00:00</span>';
	}

	$row_sales = row_sales();

	return <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-table me-1"></i>
				Profit Book{$counter_span}
			</div>
			<div class="card-body">
				<table class="table">
					$row_sales
				</table>
			</div>
		</div>
	HTML;
}

/**
 *
 * @return string
 *
 * @since version
 */
function row_sales(): string
{
	$currency = settings('ancillaries')->currency;

	$total_sales = income_admin();

	$total_sales = $total_sales->income_total ?? 0;

	$cd_sales = cd_sales();

	$total_payouts = payouts();

	$total_payouts = $total_payouts->payout_total ?? 0;

	$fmc_purchase = token()->purchase_fmc ?? 0;

	$net_sales = $total_sales - $cd_sales - $total_payouts - $fmc_purchase;

	$count_users = count(users());

	$link_directs = sef(40);
	$button_directs = <<<HTML
		<a href="$link_directs" class="btn btn-primary btn-sm" style="float:right">View All Members</a>
	HTML;

	$total_sales_format = number_format($total_sales, 2);
	$link_income_log = sef(35);
	$button_income_log = <<<HTML
		<a href="$link_income_log" class="btn btn-primary btn-sm" style="float:right">View Income Log</a>
	HTML;

	$payouts_format = number_format($total_payouts, 2);
	$link_payout_log = sef(49);
	$button_payout_log = <<<HTML
		<a href="$link_payout_log" class="btn btn-primary btn-sm" style="float:right">View Payout Log</a>
	HTML;

	$net_profit_format = number_format($net_sales, 2);

	$str = <<<HTML
		<tbody>
			<tr>
				<th scope="row">Members</th>
				<td>$count_users{$button_directs}</td>			
			</tr>
			<tr>
				<th scope="row">Total Cash-in</th>
				<td>$total_sales_format $currency{$button_income_log}</td>
			</tr>
			<tr>
				<th scope="row">Payouts</th>
				<td colspan="2">$payouts_format $currency{$button_payout_log}</td>
			</tr>
			<tr>
				<th scope="row">Remaining Cash Balance</th>
				<td colspan="2">$net_profit_format $currency{$button_payout_log}</td>
			</tr>
		</tbody>
HTML;

	return $str;
}

/**
 * @param $usertype
 *
 *
 * @since version
 */
function page_validate($usertype)
{
	if ($usertype !== 'Admin' && $usertype !== 'manager') {
		application()->redirect(Uri::root(true) . '/' . sef(43));
	}
}

/**
 *
 * @return mixed|null
 *
 * @since version
 */
function income_admin()
{
	return db()->setQuery(
		'SELECT income_total ' .
		'FROM network_income ' .
		'ORDER BY income_id DESC'
	)->loadObject();
}

/**
 *
 * @return float|int
 *
 * @since version
 */
function cd_sales()
{
	$se = settings('entry');

	$cd_codes = db()->setQuery(
		'SELECT * FROM network_codes WHERE type LIKE \'%cd%\' AND user_id <> 0;'
	)->loadObjectList();

	$chairman_cd = 0;
	$executive_cd = 0;
	$regular_cd = 0;
	$associate_cd = 0;
	$basic_cd = 0;

	if ($cd_codes) {
		foreach ($cd_codes as $code) {
			if (strpos($code->type, 'chairman') !== false) {
				$chairman_cd++;
			}

			if (strpos($code->type, 'executive') !== false) {
				$executive_cd++;
			}

			if (strpos($code->type, 'regular') !== false) {
				$regular_cd++;
			}

			if (strpos($code->type, 'associate') !== false) {
				$associate_cd++;
			}

			if (strpos($code->type, 'basic') !== false) {
				$basic_cd++;
			}
		}
	}

	$sales_chairman_cd = $chairman_cd * $se->chairman_entry;
	$sales_executive_cd = $executive_cd * $se->executive_entry;
	$sales_regular_cd = $regular_cd * $se->regular_entry;
	$sales_associate_cd = $associate_cd * $se->associate_entry;
	$sales_basic_cd = $basic_cd * $se->basic_entry;

	return $sales_chairman_cd + $sales_executive_cd + $sales_regular_cd + $sales_associate_cd + $sales_basic_cd;
}

/**
 *
 * @return mixed|null
 *
 * @since version
 */
function payouts()
{
	return db()->setQuery(
		'SELECT payout_total ' .
		'FROM network_payouts ' .
		'ORDER BY payout_id DESC'
	)->loadObject();
}

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
