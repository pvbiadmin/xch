<?php

namespace BPL\Jumi\Member;

require_once 'templates/sb_admin/tmpl/login.tmpl.php';
require_once 'templates/sb_admin/tmpl/master.tmpl.php';
require_once 'templates/sb_admin/tmpl/error403.tmpl.php';
require_once 'bpl/mods/url_sef.php';
require_once 'bpl/mods/helpers.php';

use Exception;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Session\Session;

use function Templates\SB_Admin\Tmpl\Login\main as view_login;

use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\session_set;
use function BPL\Mods\Helpers\input_get;
use function BPL\Mods\Helpers\settings;
use function BPL\Mods\Url_SEF\sef;
use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\user;
use function BPL\Mods\Helpers\application;
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

	$str .= member($user_id, /* $admintype, */ true);

	return $str;
}

function member($user_id, /* $admintype, */ $counter = false)
{
	if (!$user_id) {
		return '';
	}

	$str = '';

	$str .= live_reload($counter);

	$sp = settings('plans');
	$sa = settings('ancillaries');

	$currency = $sa->currency;

	$user = user($user_id);
	$user_directs = user_direct($user_id);
	$count_directs = count($user_directs);
	$income_referral_ftp = $user->income_referral_fast_track_principal;
	$bonus_leadership_ftp = $user->bonus_leadership_fast_track_principal;

	// $view_admintype = '';
	// $view_user_directs = '';
	// $view_income_referral_ftp = '';
	// $view_bonus_leadership_ftp = '';

	// echo '<pre>';
	// print_r($user);
	// exit;

	// if ($admintype) {
	// 	$view_admintype = <<<HTML
	//     <p>AdminType: {$admintype}</p>
	//     HTML;
	// }

	// if ($count_directs) {
	// 	$view_user_directs = <<<HTML
	// 		<p>Directs: {$count_directs}</p>
	// 	HTML;
	// }

	$income_referral_ftp_format = 00.00;

	if ($sp->direct_referral_fast_track_principal) {
		$income_referral_ftp_format = number_format($income_referral_ftp, 2);
		// $view_income_referral_ftp = <<<HTML
		// 	<p>Direct Referral: {$income_referral_ftp_format} {$currency}</p>
		// HTML;
	}

	$bonus_leadership_ftp_format = 00.00;

	if (
		$sp->leadership_fast_track_principal
		&& has_leadership_fast_track_principal($user_id)
	) {
		$bonus_leadership_ftp_format = number_format($bonus_leadership_ftp, 2);
		// $view_bonus_leadership_ftp = <<<HTML
		// 	<p>Indirect Referral: {$bonus_leadership_ftp_format} {$currency}</p>
		// HTML;
	}

	// Add counter div without the JavaScript (moved to live_reload)
	// $counter_div = '';
	$counter_span = '';

	if ($counter) {
		// $counter_div = '<div id="counter">00:00:00</div>';
		$counter_span = '<span id="counter" style="float:right">00:00:00</span>';
	}

	// $account_type_format = ucwords($user->account_type);

	// $str .= <<<HTML
	// 	<p>User ID: {$user_id}</p>
	// 	<p>User Type: {$user->usertype}</p>
	// 	<p>Account Type: {$account_type_format}</p>
	// 	<p>Username: {$user->username}</p>
	// 	{$view_admintype}
	// 	{$view_user_directs}
	// 	{$view_income_referral_ftp}
	// 	{$view_bonus_leadership_ftp}
	// 	{$counter_div}
	// HTML;

	// $hold = live_reload($counter);

	$str .= <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Dashboard</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">Profit Summary</li>
		</ol>
		<div class="row">
			<div class="col-xl-3 col-md-6">
				<div class="card bg-primary text-white mb-4">
					<div class="card-body">Direct Referral<span id="direct_referral" style="float:right">$income_referral_ftp_format $currency</span></div>
					<div class="card-footer d-flex align-items-center justify-content-between">
						<a class="small text-white stretched-link" href="#">View Details</a>
						<div class="small text-white"><i class="fas fa-angle-right"></i></div>
					</div>
				</div>
			</div>
			<div class="col-xl-3 col-md-6">
				<div class="card bg-warning text-white mb-4">
					<div class="card-body">Sponsored Members<span id="sponsored_members" style="float:right">$count_directs</span></div>
					<div class="card-footer d-flex align-items-center justify-content-between">
						<a class="small text-white stretched-link" href="#">View Details</a>
						<div class="small text-white"><i class="fas fa-angle-right"></i></div>
					</div>
				</div>
			</div>
			<div class="col-xl-3 col-md-6">
				<div class="card bg-success text-white mb-4">
					<div class="card-body">Royalty Bonus<span id="royalty_bonus" style="float:right">$bonus_leadership_ftp_format $currency</span></div>
					<div class="card-footer d-flex align-items-center justify-content-between">
						<a class="small text-white stretched-link" href="#">View Details</a>
						<div class="small text-white"><i class="fas fa-angle-right"></i></div>
					</div>
				</div>
			</div>
			<div class="col-xl-3 col-md-6">
				<div class="card bg-danger text-white mb-4">
					<div class="card-body">Passive Income<span id="passive_income" style="float:right">00.00 $currency</span></div>
					<div class="card-footer d-flex align-items-center justify-content-between">
						<a class="small text-white stretched-link" href="#">View Details</a>
						<div class="small text-white"><i class="fas fa-angle-right"></i></div>
					</div>
				</div>
			</div>
		</div>		
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-table me-1"></i>
				DataTable Example{$counter_span}
			</div>
			<div class="card-body">
				<table id="datatablesSimple">
					<thead>
						<tr>
							<th>Name</th>
							<th>Position</th>
							<th>Office</th>
							<th>Age</th>
							<th>Start date</th>
							<th>Salary</th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th>Name</th>
							<th>Position</th>
							<th>Office</th>
							<th>Age</th>
							<th>Start date</th>
							<th>Salary</th>
						</tr>
					</tfoot>
					<tbody>
						<tr>
							<td>Tiger Nixon</td>
							<td>System Architect</td>
							<td>Edinburgh</td>
							<td>61</td>
							<td>2011/04/25</td>
							<td>$320,800</td>
						</tr>						
					</tbody>
				</table>
			</div>
		</div>
	</div>
	HTML;

	return $str;
}

function live_reload(bool $counter, int $s = 5000): string
{
	$counter_script = '';

	$increment = $s / 1000;

	if ($counter) {
		$counter_script = <<<JS
            // Initialize counter functionality
            let counter = parseInt(localStorage.getItem('counter')) || 0;
            const counterElement = document.getElementById('counter');

            const increment = $increment;

            function formatTime(seconds) {
                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                const secs = seconds % 60;
                return String(hours).padStart(2, '0') + ':' + 
                        String(minutes).padStart(2, '0') + ':' + 
                        String(secs).padStart(2, '0');
            }

            function updateCounter() {
                counter += increment;
                counterElement.textContent = formatTime(counter);
                localStorage.setItem('counter', counter);
            }

            // Update the counter every second
            setInterval(updateCounter, $s);

            // Restore counter value after reload
            const savedCounter = localStorage.getItem('counter');
            if (savedCounter) {
                document.getElementById('counter').textContent = formatTime(savedCounter);
            }
        JS;
	}

	return <<<HTML
    <script>
        // Live reload functionality
        setInterval(() => {
            // Save the current state of the DataTable
            const datatablesSimple = document.getElementById('datatablesSimple');
            let tableState = null;
            if (datatablesSimple && window.dataTable) {
                tableState = window.dataTable.getState(); // Save the current state
            }

            fetch(window.location.href, { headers: { "X-Requested-With": "XMLHttpRequest" } })
                .then(response => response.text())
                .then(data => {
                    const parser = new DOMParser();
                    const newDocument = parser.parseFromString(data, "text/html");
                    const newBody = newDocument.querySelector("main").innerHTML;
                    document.querySelector("main").innerHTML = newBody;

                    // Reinitialize DataTable after content update
                    const newDatatablesSimple = document.getElementById('datatablesSimple');
                    if (newDatatablesSimple) {
                        window.dataTable = new simpleDatatables.DataTable(newDatatablesSimple);

                        // Restore the saved state of the DataTable
                        if (tableState) {
                            window.dataTable.setState(tableState);
                        }
                    }

                    // Counter functionality
                    if ({$counter}) {
                        {$counter_script}
                    }
                })
                .catch(error => console.error("Error fetching page:", error));
        }, {$s});
    </script>
HTML;
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
	return user_leadership_fast_track_principal($user_id) ? true : false;
}

function user_leadership_fast_track_principal($user_id)
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