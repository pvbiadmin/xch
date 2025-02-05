<?php

namespace BPL\Jumi\Member;

require_once 'templates/sb_admin/tmpl/login.tmpl.php';
require_once 'templates/sb_admin/tmpl/master.tmpl.php';
require_once 'templates/sb_admin/tmpl/error403.tmpl.php';
require_once 'bpl/mods/root_url_upline.php';
require_once 'bpl/mods/time_remaining.php';
require_once 'bpl/mods/url_sef.php';
require_once 'bpl/mods/helpers.php';

use Exception;
use DateTime;
use DateInterval;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Session\Session;

use function Templates\SB_Admin\Tmpl\Login\main as view_login;

use function BPL\Mods\Root_Url_Upline\main as root_url;

use function BPL\Mods\Time_Remaining\main as time_remaining;

use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\session_set;
use function BPL\Mods\Helpers\input_get;
use function BPL\Mods\Helpers\settings;
use function BPL\Mods\Url_SEF\sef;
use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\user;
use function BPL\Mods\Helpers\application;
use function BPL\Mods\Helpers\live_reload;

use function Templates\SB_Admin\Tmpl\Master\main as master;
use function Templates\SB_Admin\Tmpl\Error403\main as error403;

$content = main();

master($content);

function main()
{
	$user_id = session_get('user_id');
	// $admintype = session_get('admintype');
	$attempts = session_get('attempts', 0);
	$usertype = session_get('usertype');

	$username = input_get('username');
	$password = input_get('password');

	$max_attempts = 5;

	$str = process_login($username, $password, $usertype, $attempts, $max_attempts);

	$str .= member($user_id, true);

	return $str;
}

function member($user_id, $counter = false)
{
	if (!$user_id) {
		return '';
	}

	$str = live_reload($counter);

	$view_direct_referral = view_direct_referral($user_id);
	$view_sponsored_members = view_sponsored_members($user_id);
	$view_royalty_bonus = view_royalty_bonus($user_id);
	$view_passive_income = view_passive_income($user_id);
	$view_fast_track = view_fast_track($user_id, $counter);

	$str .= <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Dashboard</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">Profit Summary</li>
		</ol>
		<div class="row">
			$view_direct_referral
			$view_sponsored_members
			$view_royalty_bonus
			$view_passive_income
		</div>		
		$view_fast_track
	</div>
	HTML;

	return $str;
}

function view_direct_referral($user_id)
{
	$sp = settings('plans');
	$sa = settings('ancillaries');

	$currency = $sa->currency;

	$user = user($user_id);

	$income_referral_ftp_format = 00.00;

	$income_referral_ftp = $user->income_referral_fast_track_principal;

	if ($sp->direct_referral_fast_track_principal) {
		$income_referral_ftp_format = number_format($income_referral_ftp, 2);
	}

	$link = 'http://' . $_SERVER['SERVER_NAME'] . root_url() . '/' . $user->username;

	return <<<HTML
		<div class="col-xl-3 col-md-6">
			<div class="card bg-primary text-white mb-4">
				<div class="card-body">Direct Referral<span id="direct_referral" style="float:right">
					$income_referral_ftp_format $currency</span></div>
				<div class="card-footer d-flex align-items-center justify-content-between">
					<span>Referral Link:</span><a class="small text-white stretched-link" href="$link">$link</a>
					<div class="small text-white"><i class="fas fa-angle-right"></i></div>
				</div>
			</div>
		</div>
	HTML;
}

function view_sponsored_members($user_id)
{
	$user_directs = user_direct($user_id);
	$count_directs = count($user_directs);

	$sponsored_members_link = sef(13);

	return <<<HTML
		<div class="col-xl-3 col-md-6">
			<div class="card bg-warning text-white mb-4">
				<div class="card-body">Sponsored Members<span id="sponsored_members" style="float:right">$count_directs</span></div>
				<div class="card-footer d-flex align-items-center justify-content-between">
					<a class="small text-white stretched-link" href="$sponsored_members_link">View Details</a>
					<div class="small text-white"><i class="fas fa-angle-right"></i></div>
				</div>
			</div>
		</div>
	HTML;
}

function view_royalty_bonus($user_id)
{
	$sp = settings('plans');
	$sa = settings('ancillaries');

	$currency = $sa->currency;

	$user = user($user_id);

	$bonus_leadership_ftp = $user->bonus_leadership_fast_track_principal;

	$bonus_leadership_ftp_format = 00.00;

	if (
		$sp->leadership_fast_track_principal
		&& has_leadership_fast_track_principal($user_id)
	) {
		$bonus_leadership_ftp_format = number_format($bonus_leadership_ftp, 2);
	}

	$lftp_link = sef(156);

	return <<<HTML
		<div class="col-xl-3 col-md-6">
			<div class="card bg-success text-white mb-4">
				<div class="card-body">Royalty Bonus<span id="royalty_bonus" style="float:right">$bonus_leadership_ftp_format $currency</span></div>
				<div class="card-footer d-flex align-items-center justify-content-between">
					<a class="small text-white stretched-link" href="$lftp_link">View Details</a>
					<div class="small text-white"><i class="fas fa-angle-right"></i></div>
				</div>
			</div>
		</div>
	HTML;
}

function view_passive_income($user_id)
{
	$sa = settings('ancillaries');

	$currency = $sa->currency;

	$user = user($user_id);

	$fast_track_interest = $user->fast_track_interest;

	$fast_track_interest_format = number_format($fast_track_interest, 2);

	return <<<HTML
		<div class="col-xl-3 col-md-6">
			<div class="card bg-danger text-white mb-4">
				<div class="card-body">Passive Income<span id="passive_income" style="float:right">$fast_track_interest_format $currency</span></div>
				<div class="card-footer d-flex align-items-center justify-content-between">
					<a class="small text-white stretched-link" href="#">View Details</a>
					<div class="small text-white"><i class="fas fa-angle-right"></i></div>
				</div>
			</div>
		</div>
	HTML;
}

function view_fast_track($user_id, $counter)
{
	$counter_span = '';

	if ($counter) {
		$counter_span = '<span id="counter" style="float:right">00:00:00</span>';
	}

	$table_fast_track = table_fast_track($user_id);

	return <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-table me-1"></i>
				Profit Share{$counter_span}
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
				<th>Initial</th>
				<th>Accumulated</th>
				<th>Running Days</th>
				<th>Maturity Date ($maturity days)</th>
				<th>Status</th>							
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Initial</th>
				<th>Accumulated</th>
				<th>Running Days</th>
				<th>Maturity Date ($maturity days)</th>
				<th>Status</th>
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

function process_login($username, $password, $usertype, $attempts, $max_attempts): string
{
	$str = '';

	if ($usertype === '') {
		// Check if the user is currently blocked
		$last_failed_attempt_time = session_get('last_failed_attempt_time', 0);
		$block_duration = 150 * 60; // 150 minutes in seconds

		if ($max_attempts - $attempts <= 0) {
			// Check if the block duration has passed
			if (time() - $last_failed_attempt_time < $block_duration) {
				// Display the custom error page
				error403('You are blocked from logging in for 150 minutes due to too many failed attempts.', '403');
			} else {
				// Reset the attempts and last failed attempt time if the block duration has passed
				session_set('attempts', 0);
				session_set('last_failed_attempt_time', 0);
			}

		}

		$app = application();

		try {
			if (empty($username)) {
				// view login form
				view_login();
			} else {
				// Validate CSRF token
				if (!Session::checkToken()) {
					$app->enqueueMessage('Invalid Transaction!', 'error');
					$app->redirect(Uri::current());
				}

				// Sanitize username to prevent XSS
				$username = filter_var($username, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

				// Passwords should not be sanitized; they are hashed
				// Attempt to login
				$login = login_user_get($username, $password);

				if (!empty($login)) {
					if ((int) $login->block === 0) {
						// Set session variables
						set_user_session($login);

						// Reset login attempts and last failed attempt time
						session_set('attempts', 0);
						session_set('last_failed_attempt_time', 0);

						// Redirect to the desired page
						$app->redirect(Uri::current());
					} else {
						$app->enqueueMessage('Your account has been disabled by an Admin. Contact support for more info.', 'error');
						$app->redirect(Uri::current());
					}
				} else {
					// Increment failed login attempts
					$attempts = session_set('attempts', $attempts + 1);
					session_set('last_failed_attempt_time', time());

					$err = 'Incorrect Username or Password. Too many failed logins will block you for 150 minutes.' .
						'<br>Attempts left: ' . ($max_attempts - $attempts);

					$app->enqueueMessage($err, 'error');
					$app->redirect(Uri::current());
				}
			}
		} catch (Exception $e) {
			// Log the exception and show a generic error message
			error_log($e->getMessage());
			$app->enqueueMessage('An error occurred during login. Please try again.', 'error');
			$app->redirect(Uri::current());
		}
	}

	return $str;
}

function set_user_session($login)
{
	session_set('usertype', $login->usertype);
	session_set('account_type', $login->account_type);
	session_set('merchant_type', $login->merchant_type);
	session_set('rank', $login->rank);
	session_set('username', $login->username);
	session_set('user_id', $login->id);
}

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

function has_leadership_fast_track_principal($user_id)
{
	return user_lftp($user_id) ? true : false;
}

function user_lftp($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_leadership_fast_track_principal ' .
		'WHERE user_id = ' . $db->quote($user_id)
	)->loadObject();
}

function user_direct($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_users ' .
		'WHERE sponsor_id = ' . $db->quote($user_id)
	)->loadObjectList();
}