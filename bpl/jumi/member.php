<?php

namespace BPL\Jumi\Member;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
require_once 'templates/sb_admin/tmpl/error403.tmpl.php';
require_once 'bpl/mods/url_sef.php';
require_once 'bpl/mods/helpers.php';

use Exception;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Session\Session;

use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\session_set;
use function BPL\Mods\Helpers\input_get;
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
	$admintype = session_get('admintype');
	$attempts = session_get('attempts', 0);
	$usertype = session_get('usertype');

	$username = input_get('username');
	$password = input_get('password');

	$max_attempts = 5;

	$str = process_login($username, $password, $usertype, $attempts, $max_attempts);

	$str .= member($user_id, $admintype);

	return $str;
}

function member($user_id, $admintype, $counter = false)
{
	if (!$user_id) {
		return '';
	}

	$str = live_reload($counter);

	$user = user($user_id);

	$view_admintype = '';

	if ($admintype) {
		$view_admintype = <<<HTML
        <p>AdminType: {$admintype}</p>
        HTML;
	}

	$counter_script = '';

	if ($counter) {
		$counter_script = <<<HTML
		<div id="counter">0</div>
		<script>
			let counter = parseInt(localStorage.getItem('counter')) || 0;
			const counterElement = document.getElementById('counter');
		
			function updateCounter() {
				counter++;
				counterElement.textContent = counter;
				localStorage.setItem('counter', counter);
			}
		
			// Update the counter every second
			setInterval(updateCounter, 1000);
		</script>
		HTML;
	}

	// Add a counter div
	$str .= <<<HTML
        <p>User ID: {$user_id}</p>
        <p>User Type: {$user->usertype}</p>
        <p>Username: {$user->username}</p>
        {$view_admintype}
        {$counter_script}
    HTML;

	return $str;
}

function live_reload(bool $counter, int $s = 5000): string
{
	$counter_script = '';

	if ($counter) {
		$counter_script = <<<JS
		const savedCounter = localStorage.getItem('counter');
		if (savedCounter) {
			document.getElementById('counter').textContent = savedCounter;
		}
		JS;
	}

	return <<<HTML
    <script>
        setInterval(() => {
            fetch(window.location.href, { headers: { "X-Requested-With": "XMLHttpRequest" } })
                .then(response => response.text())
                .then(data => {
                    const parser = new DOMParser();
                    const newDocument = parser.parseFromString(data, "text/html");
                    const newBody = newDocument.querySelector("body").innerHTML;
                    document.querySelector("body").innerHTML = newBody;

                    // Restore the counter value after reload
                    {$counter_script}
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
		if ($max_attempts - $attempts <= 0) {
			// Display the custom error page
			error403('You are blocked from logging in for 150 minutes due to too many failed attempts.', '403');
		}

		$app = application();

		try {
			if (!empty($username)) {
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

						// Reset login attempts
						session_set('attempts', 0);

						// Redirect to the desired page
						$app->redirect(Uri::current());
					} else {
						$app->enqueueMessage('Your account has been disabled by an Admin. Contact support for more info.', 'error');
						$app->redirect(Uri::current());
					}
				} else {
					// Increment failed login attempts
					$attempts = session_set('attempts', $attempts + 1);

					$err = 'Incorrect Username or Password. Too many failed logins will block you for 150 minutes.' .
						'<br>Attempts left: ' . ($max_attempts - $attempts) . '.';

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