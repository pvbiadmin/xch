<?php

namespace BPL\Jumi\Member;

require_once 'bpl/ajax/ajaxer/time_remaining_to_activate.php';
require_once 'bpl/ajax/ajaxer/table_fixed_daily.php';
require_once 'bpl/mods/time_remaining_to_activate.php';
//require_once 'bpl/mods/api_coin_price.php';
require_once 'bpl/mods/account_summary.php';
require_once 'bpl/mods/table_daily_interest.php';
//require_once 'bpl/menu.php';
require_once 'bpl/mods/root_url_upline.php';
require_once 'bpl/mods/helpers.php';

use Exception;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

use function BPL\Ajax\Ajaxer\Time_Remaining_To_Activate\main as ajax_time_remaining_to_activate;
use function BPL\Ajax\Ajaxer\Table_Fixed_Daily\main as ajax_table_fixed_daily;
use function BPL\Mods\Table_Daily_Interest\main as table_daily;

use function BPL\Mods\Time_Remaining_To_Activate\main as time_remaining_to_activate;

//use function BPL\Mods\Account_Summary\row_referral_link;
//use function BPL\Mods\Account_Summary\row_username;
//use function BPL\Mods\Account_Summary\row_account_type;

//use function BPL\Mods\Account_Summary\row_balance;
//use function BPL\Mods\Account_Summary\row_efund;
//use function BPL\Mods\Account_Summary\row_points;
//use function BPL\Mods\Account_Summary\row_daily_incentive;
//use function BPL\Mods\Account_Summary\row_merchant;
use function BPL\Mods\Account_Summary\ticker_coin_price;
use function BPL\Mods\Account_Summary\script_coin_price;

use function BPL\Menu\admin as menu_admin;
use function BPL\Menu\member as menu_member;
use function BPL\Menu\manager as menu_manager;

use function BPL\Mods\Url_SEF\sef;

use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\session_set;
use function BPL\Mods\Helpers\input_get;
use function BPL\Mods\Helpers\application;
use function BPL\Mods\Helpers\settings;
use function BPL\Mods\Helpers\user;
use function BPL\Mods\Helpers\users;
use function BPL\Mods\Helpers\page_reload;

try
{
	main();
}
catch (Exception $e)
{
}

/**
 *
 *
 * @throws Exception
 * @since version
 */
function main()
{
	$attempts   = session_get('attempts', 0);
	$usertype   = session_get('usertype');
	$s_username = session_get('username');

	$username = input_get('username');
	$password = input_get('password');

	$max_attempts = 5;

	$str = process_login($username, $password, $usertype, $attempts, $max_attempts);

	$user_id      = session_get('user_id');
	$admintype    = session_get('admintype');
	$account_type = session_get('account_type');
//	$merchant_type = session_get('merchant_type');

	switch ($usertype)
	{
		case 'Admin':
			$str .= menu_admin($admintype, $account_type, $user_id, $s_username);
			$str .= admin();

			break;
		case 'Member':
			$str .= menu_member($account_type, $s_username, $user_id);
			$str .= member($user_id);

			break;
		case 'manager':
			$str .= menu_manager();
			$str .= manager();

			break;
	}

	echo $str;
}

/**
 *
 * @return string
 *
 * @since version
 */
function view_form_login(): string
{
//	$img = 'images/logo_responsive.png'/*'https://picsum.photos/300/100'*/
//	;

	$img = 'images/logo_responsive.png';

	$logo1 = '<svg data-jdenticon-value="' . \BPL\Mods\Helpers\time() . '" width="80" height="80"></svg>';
	$logo2 = '<a href="../"><img src="' . $img . '" class="img-responsive" alt=""></a>';

	$str = '<section class="tm-top-b uk-grid" data-uk-grid-match="{target:\'> div > .uk-panel\'}" data-uk-grid-margin="">
			<div class="uk-width-1-1 uk-row-first"><div class="uk-panel uk-text-center">';

	$str .= !1 ? $logo1 : $logo2; // logo2 enabled

	$str .= '<form class="uk-form" method="post" style="width: 200px; padding-top: 21px; margin-left: auto; margin-right: auto">' . HTMLHelper::_('form.token') .
		'<div class="uk-form-row">
						<input class="uk-width-1-1" name="username" size="18" placeholder="Username" type="text">
					</div>
					<div class="uk-form-row">
						<input class="uk-width-1-1" name="password" size="18" placeholder="Password" type="password">
					</div>
					<div class="uk-form-row">
						<button class="uk-button uk-button-primary" value="Login" name="submit" type="submit">
							Sign In
						</button>
					</div>
				</form>
			</div></div></section>';

	$str .= identicon_js();

	return $str;
}

/**
 * @param $username
 * @param $password
 *
 * @return mixed|null
 *
 * @since version
 */
function login_user_get($username, $password)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_users ' .
		'WHERE username = ' . $db->quote($username) .
		' AND password = ' . $db->quote(md5($password))
	)->loadObject();
}

/**
 *
 * @param $username
 * @param $password
 * @param $usertype
 * @param $attempts
 * @param $max_attempts
 *
 * @return string
 *
 * @since version
 */
function process_login($username, $password, $usertype, $attempts, $max_attempts): string
{
	$str = '';

	if ($usertype === '')
	{
		if ($max_attempts - $attempts <= 0)
		{
			die('You are blocked from logging in for 150 minutes due to too many failed attempts.');
		}

		$app = application();

		try
		{
			if ($username === '')
			{
				$str .= view_form_login();
			}
			else
			{
				Session::checkToken() or $app->redirect(Uri::root(true) .
					'/' . sef(43), 'Invalid Transaction!', 'error');

				$login = login_user_get($username, $password);

				if (!empty($login))
				{
					if ((int) $login->block === 0)
					{
						session_set('usertype', $login->usertype);
						session_set('account_type', $login->account_type);
						session_set('merchant_type', $login->merchant_type);
						session_set('rank', $login->rank);
						session_set('username', $login->username);
						session_set('user_id', $login->id);
						session_set('attempts', 0);

						$app->redirect(Uri::root(true) . '/' . sef(43));
					}
					else
					{
						$app->redirect(Uri::root(true) . '/' . sef(43),
							'Your account has been disabled by an Admin. Contact for more info.', 'error');
					}
				}
				else
				{
					$attempts = session_set('attempts', $attempts + 1);

					$err = 'Incorrect Username or Password. Too many failed logins will block you for 150 minutes.' .
						'<br>Attempts left: ' . ($max_attempts - $attempts) . '.';

					$app->redirect(Uri::root(true) . '/' . sef(43), $err, 'error');
				}
			}
		}
		catch (Exception $e)
		{
		}
	}

	return $str;
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
		'SELECT * ' .
		'FROM network_income ' .
		'ORDER BY income_id DESC'
	)->loadObject();
}

/**
 *
 * @return mixed|null
 *
 * @since version
 */
function payout_admin()
{
	return db()->setQuery(
		'SELECT * ' .
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
function token_admin()
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
function header_admin(): string
{
	return '<h2>Sales Overview</h2>';
}

/**
 *
 * @return string
 *
 * @since version
 */
function row_member_count(): string
{
	return '<tr>
            <td style="width: 21%">Members:</td>
            <td style="width: 43%">' . count(users()) .
		'<a style="float:right" href="' . sef(40) . '">View All Members</a>
            </td>
        </tr>';
}

/**
 *
 * @return string
 *
 * @since version
 */
function row_sales(): string
{
	return '<tr>
            <td>Sales:</td>
            <td>' . number_format((income_admin()->income_total ?? 0), 2) .
		' ' . /*settings('ancillaries')->currency .*/
		'<a style="float:right" href="' . sef(35) . '">View Income Log</a>
            </td>
        </tr>';
}

/**
 *
 * @return string
 *
 * @since version
 */
function row_payouts(): string
{
	return '<tr>
            <td>Payouts:</td>
            <td>' . number_format((payout_admin()->payout_total ?? 0), 2) .
		' ' . /*settings('ancillaries')->currency .*/
		'<a style="float:right" href="' . sef(49) . '">View Payout Log</a>
            </td>
        </tr>';
}

/**
 *
 * @return string
 *
 * @since version
 */
function row_token_profit(): string
{
	$str = '';

	if (settings('plans')->trading)
	{
		$str .= '<tr>
                <td>' . settings('trading')->token_name . ' Profit:</td>
                <td>' . number_format(token_admin()->purchase_fmc, 2) .
			' ' . /*settings('ancillaries')->currency .*/
			'</td>
            </tr>';
	}

	return $str;
}

/**
 *
 * @return string
 *
 * @since version
 */
function row_sales_net(): string
{
	$total_sales   = income_admin()->income_total ?? 0;
	$total_payouts = payout_admin()->payout_total ?? 0;
	$net_sales     = $total_sales - $total_payouts - token_admin()->purchase_fmc;

	return '<tr>
            <td>Net Sales:</td>
            <td>' . number_format($net_sales, 2) .
		' ' . /*settings('ancillaries')->currency .*/ '</td>
        </tr>';
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

	if (settings('plans')->trading)
	{
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
function header_member(): string
{
	$str = '<h2>Home<span style="float: right">
	        <a href="https://apexproadvance.com?ref=0xA4995eeC39E73Dfa33eE7E8eB585425A4473F113" 
	        class="uk-button uk-button-primary">Crypto Check</a></span></span>';

	$str .= ticker_coin_price();

	$str .= '</h2>';

	$str .= '<p style="font-size: small; font-weight: lighter">';
	$str .= 'This site has been audited by a third party, and according to the entire data of the site, it is fine and no malware has been detected so it is 100% recommended because its system is stable and very secure at the moment. however you can also do your own research to prove its existence and legitimacy.';
	$str .= '</p>';

//	$str .= view_chart();

	return $str;
}

/**
 * @param $user_id
 *
 *
 * @return array|mixed
 * @since version
 */
function user_fixed_daily($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_fixed_daily ' .
		'WHERE user_id = ' . $db->quote($user_id)
	)->loadObjectList();
}

/**
 * @param $user_id
 *
 * @return bool
 *
 * @since version
 */
function has_fixed_daily($user_id): bool
{
	return count(user_fixed_daily($user_id)) === 1;
}

/**
 * @param $user_id
 *
 * @return string
 *
 * @throws Exception
 * @since version
 */
function table_fixed_daily($user_id): string
{
	$si   = settings('investment');
	$user = user($user_id);
	$acct = $user->account_type;

	$user_fixed_daily = user_fixed_daily($user_id)[0];

	return table_daily(
		settings('entry')->{$acct . '_entry'},
		$user->date_activated,
		$user_fixed_daily->value_last,
		$user_fixed_daily->day,
		$user_fixed_daily->processing,
		$si->{$acct . '_fixed_daily_maturity'},
		$si->{$acct . '_fixed_daily_interval'}
	);
}

/**
 *
 * @param $user_id
 *
 * @return string
 *
 * @throws Exception
 * @since version
 */
function fixed_daily($user_id): string
{
	$settings_plans = settings('plans');

	$user = user($user_id);

	$str = '';

	if ($user->account_type !== 'starter' &&
		$settings_plans->fixed_daily &&
		has_fixed_daily($user_id))
	{
		$str .= '<br>
			<hr><br>
			<h2>' . settings('entry')->{$user->account_type . '_package_name'} . ' ' .
			$settings_plans->fixed_daily_name . /*'<span style="float: right">
				<a href="' . sef(18) . '" class="uk-button uk-button-primary">Deposit</a>
				</span>' .*/
			'
			</h2>
			<div class="table-responsive">
			<table class="category table table-bordered table-hover">
			<tr>
				<td rowspan="3" style="text-align: center; width: 33%; vertical-align: middle">
				    <div class="table-responsive" id="table_fixed_daily">' . table_fixed_daily($user_id) . '</div>
                </td>
			</tr>
			</table>
			</div>';

		$str .= ajax_table_fixed_daily($user_id);

		$currency = settings('ancillaries')->currency;

		$symbol = $currency === 'USD' ? 'TUSDUSDT' : 'EURUSDT';

		$url = $currency === 'PHP' ? 'https://quote.coins.ph/v1/markets/BTC-' .
			$currency : 'https://api.binance.com/api/v3/ticker/price?symbol=' . $symbol;

		$str .= '<script>';
		$str .= '(function ($) {
					setInterval(
					function () {';
		$str .= '$.ajax({
					url: "' . $url . '",
					success: function (data) {
						var coin_balance = $("#coin_balance").val();
						var fmc = data.price * ' . settings('trading')->fmc_to_usd . ';
						var coin_usd = fmc * parseFloat(coin_balance);
						$("#coin_price").html($.number(fmc, 5));
						$("#fmc_bal_now_user_usd").html($.number(coin_usd, 2));
						$("#fmc_bal_now_user").html($.number(coin_balance, 8));
					},
					error: function () {
						var coin_balance = $("#coin_balance").val();
						var temp = parseFloat(coin_balance) * 0.01200;
						$("#coin_price").html(0.01200);
						$("#fmc_bal_now_user_usd").html($.number(temp, 2));
						$("#fmc_bal_now_user").html($.number(coin_balance, 8));
					}
				});';
		$str .= '},
					5000
				);';
		$str .= '</script>';

		$str .= '<hr>';
	}

	return $str;
}

//function view_chart(): string
//{
//	return '<div class="uk-panel uk-text-center">
//        <table class="category table table-bordered table-hover">
//            <tr>
//                <td>
//                    <section id="tm-top-b" class="tm-top-b uk-grid"
//                        data-uk-grid-match="{target:\'> div > .uk-panel\'}" data-uk-grid-margin="">
//                        <div class="uk-width-1-1">
//                            <div class="uk-panel uk-panel-box uk-text-center">
//                                <div class="nomics-ticker-widget" data-name="Bitcoin 3.0" data-base="BTC32" data-quote="USD"></div>
//                                <script src="https://widget.nomics.com/embed.js"></script>
//                            </div>
//                        </div>
//                    </section>
//                </td>
//            </tr>
//        </table>
//    </div>
//    <br>';
//}

/**
 *
 * @return array|mixed
 *
 * @since version
 */
function pending_request_withdrawal()
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_users, network_withdrawals ' .
		'WHERE network_users.id = network_withdrawals.user_id ' .
		'AND network_withdrawals.date_completed = ' . $db->quote(0) .
		' ORDER BY network_withdrawals.withdrawal_id ASC'
	)->loadObjectList();
}

/**
 *
 * @return string
 *
 * @since version
 */
function admin(): string
{
//	reminder();

	$str = page_reload();

	$str .= header_admin();

	$str .= '<table class="category table table-striped table-bordered table-hover">';

	$str .= row_member_count();
	$str .= row_sales();
	$str .= row_payouts();
	$str .= row_token_profit();
	$str .= row_sales_net();

	$str .= '</table>';

	return $str;
}

/**
 *
 * @param $user_id
 *
 * @return string
 *
 * @throws Exception
 * @since version
 */
function member($user_id): string
{
	$user = user($user_id);

//    if (has_internet()) {
	$str = script_trading();
//    }

	$str .= $user->account_type === 'starter' ?
		'<div style="text-align: center"><h3 id="time_remaining_to_activate">' .
		time_remaining_to_activate($user_id) . '</h3></div>' : '';

	$str .= header_member();

//    $str .= fixed_daily($user_id);

	$str .= video();

	if (settings('ancillaries')->payment_mode === 'ECASH')
	{
		$str .= ajax_time_remaining_to_activate($user_id);
	}

	return $str;
}

//function user_info($user): string
//{
//	$str = '<table class="category table table-striped table-bordered table-hover">';
//
//	$str .= row_referral_link($user);
//	$str .= row_username($user);
//	$str .= row_account_type($user);
////	$str .= row_balance($user);
//	$str .= row_efund($user);
//	$str .= row_points($user);
//	$str .= row_daily_incentive($user);
//	$str .= row_merchant($user);
//
//	$str .= '</table>';
//
//    return $str;
//}

function video(): string
{
	$str = '<section id="tm-top-a" class="tm-top-a uk-grid" data-uk-grid-match="{target:\'> div > .uk-panel\'}" data-uk-grid-margin="">';
	$str .= '<div class="uk-width-1-1 uk-row-first">';
	$str .= '<div class="uk-panel uk-text-center">';
	$str .= '<iframe src="https://www.youtube-nocookie.com/embed/3wGv-eiJa30?autoplay=0&amp;showinfo=0&amp;rel=0&amp;modestbranding=1&amp;playsinline=1" width="854" height="480" allowfullscreen></iframe>';
	$str .= '<h1 class="uk-heading-large uk-margin-top-remove">P2P Blockchain Network in e-commerce</h1>';
	$str .= '<p class="uk-text-large">Blockchain technology is here to stay and make our complicated world a better world. The best, today we have peer-to-peer cryptocurrency transactions, which arrives to streamline processes in a safe, transparent and low-cost way.</p>';
	$str .= '</section>';

	return $str;
}

/**
 * @param   false  $local
 *
 * @return bool
 *
 * @since version
 */
//function has_internet(bool $local = true): bool
//{
//    $host_name = 'tokenshibs.org';
//    $port_no = '80';
//
//    $st = (bool)@fsockopen($host_name, $port_no, $err_no, $err_str, 10);
//
//    if ((!$local && $st) || $local) {
//        return true;
//    }
//
//    return false;
//}

/**
 *
 * @return string
 *
 * @since version
 */
function manager(): string
{
	$str = '<h1>Pending Withdrawal Requests</h1>';

	$pending_requests = pending_request_withdrawal();

	if (!empty($pending_requests))
	{
		$str .= '<table class="category table table-striped table-bordered table-hover">
            <thead>
            <tr>
                <th>Date Requested</th>
                <th>Username</th>
                <th>Account</th>
                <th>Title</th>
                <th>Credit Balance</th>
                <th>Request</th>
                <th>Method</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>';

		foreach ($pending_requests as $request)
		{
			$str .= '<tr>
                <td>' . date('M j, Y - g:i A', $request->date_requested) . '</td>
                <td><a href="' . sef(44) . '?uid=' . $request->id . '">' .
				$request->username . '</a></td>
                <td>' . settings('entry')->{$request->account_type . '_package_name'} . '</td>
                <td>' . settings('royalty')->{$request->rank . '_rank_name'} . '</td>
                <td>' . number_format($request->balance, 2) . '</td>
                <td>' . number_format($request->amount, 2) . '</td>
                <td>' . $request->method . '</td>
                <td>';

			$str .= '<div class="uk-button-group">
                    <button class="uk-button uk-button-primary">Select</button>
                    <div class="" data-uk-dropdown="{mode:\'click\'}">
                        <button class="uk-button uk-button-primary"><i class="uk-icon-caret-down"></i></button>
                        <div style="" class="uk-dropdown uk-dropdown-small">
                            <ul class="uk-nav uk-nav-dropdown">
                                <li>
                                    <a href="' . sef(112) . '?uid=' .
				$request->withdrawal_id . '&mode=1">Confirm</a>
                                </li>
                                <li>
                                    <a href="' . sef(112) . '?uid=' .
				$request->withdrawal_id . '&mode=2">Delete</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>';

			$str .= '</td>
                </tr>';
		}

		$str .= '</tbody>
        </table>';
	}
	else
	{
		$str .= '<hr><p>No pending requests.</p>';
	}

	return $str;
}

//function reminder()
//{
//
?>
    <!--	<div class="uk-width-1-1">-->
    <!--		<div class="uk-alert" data-uk-alert>-->
    <!--			<a class="uk-alert-close uk-close"></a>-->
    <!--			<p>Info message</p>-->
    <!--		</div>-->
    <!--	</div>-->
    <!--	--><?php
//}

function identicon_js(): string
{
	return '<script src="https://cdn.jsdelivr.net/npm/jdenticon@3.1.1/dist/jdenticon.min.js" async
        integrity="sha384-l0/0sn63N3mskDgRYJZA6Mogihu0VY3CusdLMiwpJ9LFPklOARUcOiWEIGGmFELx" crossorigin="anonymous">
</script>';
}