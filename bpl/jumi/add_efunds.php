<?php

namespace BPL\Jumi\Add_Efunds;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
// require_once 'bpl/menu.php';
require_once 'bpl/mods/ajax.php';
require_once 'bpl/mods/query.php';
require_once 'bpl/mods/mailer.php';
require_once 'bpl/mods/helpers.php';

use Exception;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Exception\ExceptionHandler;

// use function BPL\Menu\admin as menu_admin;

use function BPL\Mods\Ajax\check_input2;

use function Templates\SB_Admin\Tmpl\Master\main as master;

use function BPL\Mods\Database\Query\insert;
use function BPL\Mods\Database\Query\update;

use function BPL\Mods\Mailer\main as send_mail;

use function BPL\Mods\Url_SEF\sef;
use function BPL\Mods\Url_SEF\qs;

use function BPL\Mods\Helpers\page_validate_admin;
use function BPL\Mods\Helpers\user_username;
use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\session_set;
use function BPL\Mods\Helpers\application;
use function BPL\Mods\Helpers\input_get;
use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\settings;
use function BPL\Mods\Helpers\time;
use function BPL\Mods\Helpers\user;

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
	$admintype = session_get('admintype');
	// $account_type = session_get('account_type');
	$user_id = session_get('user_id');
	$username = session_get('username');
	// $final = input_get('final');

	session_set('edit', false);

	page_validate_admin($usertype, $admintype);

	// $str = menu_admin($admintype, $account_type, $user_id, $username);

	$username = input_get('username');
	$amount = input_get('amount');
	$edit = session_get('edit', false);

	if ($username !== '') {
		process_add_efunds($username, $amount, $edit);
	}

	$sa = settings('ancillaries');

	$efund_name = $sa->efund_name;

	$str = '';

	$str .= check_input2();

	$view_add_efund = view_add_efund($user_id);

	$str .= <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Add $efund_name</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">Add $efund_name to User</li>
		</ol>				
		$view_add_efund
	</div>
	HTML;

	return $str;
}

function view_add_efund($user_id): string
{
	$form_add_efund = form_add_efund($user_id);

	$notifications = notifications();

	return <<<HTML
    <div class="container-fluid px-4">        
		<div class="row justify-content-center">
			<div class="col-lg-5">
				$notifications
				<div class="card mb-4">
					$form_add_efund
				</div>
        	</div>		
		</div>
    </div>	
HTML;
}

function form_add_efund($user_id)
{
	$user = user($user_id);
	$efund = $user->payout_transfer;
	$efund_format = number_format($efund, 2);

	$sa = settings('ancillaries');

	$currency = $sa->currency;
	$efund_name = $sa->efund_name;

	$form_token = HTMLHelper::_('form.token');

	$str = <<<HTML
    <div class="card-header">
        <i class="fas fa-money-bill me-1"></i>
        $efund_name Balance: $efund_format $currency
    </div>
    <div class="card-body">
        <form method="post" onsubmit="submit.disabled = true;">
            $form_token			
			<div class="form-group">
				<div id="usernameDiv" class="help-block validation-message"></div>
				<div class="input-group mb-3">
					<span class="input-group-text"><label for="username">Username</label></span>
					<input type="text" name="username" id="username" class="form-control" 
						placeholder="Enter Recipient Username" required aria-label="Recipient Username">
					<span class="input-group-text btn btn-primary" onClick="checkInput('username')">Validate</span>
				</div>
			</div>

			<div class="input-group mb-3">
                <span class="input-group-text"><label for="amount">Amount</label></span>
                <input type="text" name="amount" id="amount" class="form-control" placeholder="Enter Amount" required aria-label="Amount">
                <span class="input-group-text">$currency</span>
            </div>

            <div class="form-group actions">
                <button type="submit" class="btn btn-primary">Add</button>                
            </div>
        </form>
    </div>
HTML;

	return $str;
}

// /**
//  *
//  * @return string
//  *
//  * @since version
//  */
// function form_add_efund__(): string
// {
// 	$sa = settings('ancillaries');

// 	$efund_name = $sa->efund_name;

// 	return '<h1>Add ' . $efund_name . '</h1>
// 	    <p>Enter member\'s Username and amount to add.</p>
// 	    <form method="post" onsubmit="submit.disabled = true; return true;">
// 	        <input type="hidden" name="final" value="1">
// 	        <table class="category table table-striped table-bordered table-hover">
// 	            <tr>
// 	                <td><label for="username">Username: *</label></td>
// 	                <td><input type="text" name="username" id="username" 
// 	                		size="40" required="required" style="float:left">
//                        	<a href="javascript:void(0)" onClick="checkInput(\'username\')"
//                              class="uk-button uk-button-primary" style="float:left">Check</a>
// 	                    <div style="width:200px; height:20px; font-weight:bold; float:left; padding:7px 0 0 10px;"
// 	                         id="usernameDiv"></div>
// 	                </td>
// 	            </tr>
// 	            <tr>
// 	                <td><label for="amount">Amount (' . settings('ancillaries')->currency . '): *</label></td>
// 	                <td>
// 	                    <input type="text" name="amount" id="amount" style="float:left">
// 	                    <input type="submit" name="submit" value="Add ' . $efund_name .
// 		'" class="uk-button uk-button-primary">	                 
// 	                </td>
// 	            </tr>
// 	        </table>
// 	    </form>';
// }

function notifications(): string
{
	$app = application();

	// Display Joomla messages as dismissible alerts
	$messages = $app->getMessageQueue(true);
	$notification_str = fade_effect(); // Initialize the notification string

	if (!empty($messages)) {
		foreach ($messages as $message) {
			// Map Joomla message types to Bootstrap alert classes
			$alert_class = '';
			switch ($message['type']) {
				case 'error':
					$alert_class = 'danger'; // Bootstrap uses 'danger' instead of 'error'
					break;
				case 'warning':
					$alert_class = 'warning';
					break;
				case 'notice':
					$alert_class = 'info'; // Joomla 'notice' maps to Bootstrap 'info'
					break;
				case 'message':
				default:
					$alert_class = 'success'; // Joomla 'message' maps to Bootstrap 'success'
					break;
			}

			$notification_str .= <<<HTML
            <div class="alert alert-{$alert_class} alert-dismissible fade show mt-5" role="alert">
                {$message['message']}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
HTML;
		}
	}

	return $notification_str;
}

function fade_effect(int $duration = 10000)
{
	return <<<HTML
    <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Select all alert elements
      const alerts = document.querySelectorAll('.alert');

      // Loop through each alert and set a timeout to dismiss it
      alerts.forEach(function (alert) {
        setTimeout(function () {
          // Use Bootstrap's alert method to close the alert
          bootstrap.Alert.getOrCreateInstance(alert).close();
        }, $duration);
      });
    });
  </script>
HTML;
}

/**
 * @param $username
 * @param $amount
 *
 * @param $edit
 *
 * @since version
 */
function process_add_efunds($username, $amount, $edit)
{
	$sa = settings('ancillaries');

	$efund_name = $sa->efund_name;
	$currency = $sa->currency;

	$amount_format = number_format($amount, 2);

	$db = db();

	validate_input($username, $amount, $edit);

	$transfer_to = user_username(input_get('username'));

	$message = '<strong>Recipient</strong>' .
		'Username: ' . $transfer_to->username . '<br>' .
		'Full Name: ' . $transfer_to->fullname . '<br>' .
		'Email: ' . $transfer_to->email . '<br>' .
		'Contact: ' . $transfer_to->contact . '<br><br>' .
		'<strong>' . $efund_name . ' Added</strong><br>' . $amount_format . '<br>';

	try {
		$db->transactionStart();

		update_users($username, $amount);

		insert_efund_add($username, $amount, $edit);

		logs($username, $amount, $edit);

		send_mail($message, $efund_name . ' Added Successfully!', [$transfer_to->email]);

		$db->transactionCommit();
	} catch (Exception $e) {
		$db->transactionRollback();

		ExceptionHandler::render($e);
	}

	// application()->redirect(Uri::root(true) . '/' . sef(4), 
	// $amount_format . ' ' . $currency . ' added to ' . $username . '.', 'notice');

	$app = application();

	$app->enqueueMessage($amount_format . ' ' . $currency . ' added to ' . $username . '.', 'success');
	$app->redirect(Uri::current());
}

/**
 * @param $username
 * @param $amount
 *
 * @param $edit
 *
 * @since version
 */
function validate_input($username, $amount, $edit)
{
	$app = application();

	if ($amount === '' || !is_numeric($amount) || $username === '') {
		// $app->redirect(Uri::root(true) . '/' .
		// 	sef(4), 'Invalid Transaction!', 'error');

		$app->enqueueMessage('Invalid Transaction!', 'error');
		$app->redirect(Uri::current());
	}

	if ($amount < 0) {
		// $app->redirect(Uri::root(true) . '/' .
		// 	sef(4), 'Invalid Amount!', 'error');

		$app->enqueueMessage('Invalid Amount!', 'error');
		$app->redirect(Uri::current());
	}

	$transfer_to = user_username($username);

	if ($transfer_to->id === '') {
		// $app->redirect(
		// 	Uri::root(true) . '/' . sef(4),
		// 	'User to transfer to does not exist.',
		// 	'error'
		// );

		$app->enqueueMessage('User to transfer to does not exist.', 'error');
		$app->redirect(Uri::current());
	}

	if ($edit) {
		$date = input_get('date', '0', 'RAW');

		if ($date === '0') {
			// $app->redirect(
			// 	Uri::root(true) . '/' . sef(4),
			// 	'Please specify the Current Date!',
			// 	'error'
			// );

			$app->enqueueMessage('Please specify the Current Date!', 'error');
			$app->redirect(Uri::current());
		}
	}
}

/**
 * @param $username
 *
 * @param $amount
 *
 * @since version
 */
function update_users($username, $amount)
{
	update(
		'network_users',
		['payout_transfer = payout_transfer + ' . $amount],
		['id = ' . db()->quote(user_username($username)->id)]
	);
}

/**
 * @param $username
 * @param $amount
 * @param $edit
 *
 *
 * @since version
 */
function insert_efund_add($username, $amount, $edit)
{
	$db = db();

	insert(
		'network_efund_add',
		[
			'user_id',
			'date',
			'amount'
		],
		[
			$db->quote(user_username($username)->id),
			$db->quote(date_get($edit)),
			$db->quote($amount)
		]
	);
}

/**
 * @param $username
 * @param $amount
 * @param $edit
 *
 *
 * @since version
 */
function logs($username, $amount, $edit)
{
	logs_transactions($username, $amount, $edit);
	logs_activity($username, $amount, $edit);
}

/**
 * @param $username
 * @param $amount
 * @param $edit
 *
 *
 * @since version
 */
function logs_transactions($username, $amount, $edit)
{
	$sa = settings('ancillaries');

	$db = db();

	$transfer_to = user_username($username);

	$date = time();

	if ($edit) {
		$date = input_get('date', '0', 'RAW');
	}

	$details = number_format($amount, 2) . ' ' . settings('ancillaries')->currency .
		' added to <a href="' . sef(44) . qs() . 'uid=' . $transfer_to->id . '">' . $username . '</a>.';

	$balance = (double) $transfer_to->payout_transfer + (double) $amount;

	insert(
		'network_transactions',
		[
			'user_id',
			'transaction',
			'details',
			'value',
			'balance',
			'transaction_date'
		],
		[
			$db->quote($transfer_to->id),
			$db->quote('Admin Added ' . $sa->efund_name),
			$db->quote($details),
			$db->quote($amount),
			$db->quote($balance),
			$db->quote($date)
		]
	);
}

/**
 * @param $username
 * @param $amount
 * @param $edit
 *
 *
 * @since version
 */
function logs_activity($username, $amount, $edit)
{
	$sa = settings('ancillaries');

	$db = db();

	$transfer_to = user_username($username);

	$activity = '<b>' . $sa->efund_name . ' Added: </b> Admin transferred ' . number_format($amount, 2) .
		' ' . settings('ancillaries')->currency . ' to <a href="' . sef(44) . qs() . 'uid=' .
		$transfer_to->id . '">' . $transfer_to->username . '</a>.';

	insert(
		'network_activity',
		[
			'user_id',
			'sponsor_id',
			'upline_id',
			'activity',
			'activity_date'
		],
		[
			$db->quote($transfer_to->id),
			$db->quote($transfer_to->sponsor_id),
			1,
			$db->quote($activity),
			$db->quote(date_get($edit))
		]
	);
}

/**
 * @param $edit
 *
 * @return string
 *
 * @since version
 */
function date_get($edit): string
{
	$date = time();

	if ($edit) {
		$date = input_get('date', '0', 'RAW');
	}

	return $date;
}