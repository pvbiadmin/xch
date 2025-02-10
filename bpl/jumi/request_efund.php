<?php

namespace BPL\Jumi\Request_Efund;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
// require_once 'bpl/menu.php';
require_once 'bpl/mods/query.php';
require_once 'bpl/mods/mailer.php';
require_once 'bpl/mods/btc_currency.php';
require_once 'bpl/mods/api_token_price.php';
require_once 'bpl/mods/api_coinbrain_token_price.php';
require_once 'bpl/mods/helpers.php';
require_once 'bpl/plugins/phpqrcode/qrlib.php';

use Exception;
use QRcode;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Exception\ExceptionHandler;

use RuntimeException;
// use function BPL\Menu\admin as menu_admin;
// use function BPL\Menu\member as menu_member;
// use function BPL\Menu\manager as menu_manager;

use function BPL\Mods\Database\Query\delete;
use function BPL\Mods\Database\Query\insert;

use function BPL\Mods\Mailer\main as send_mail;

//use function BPL\Mods\BTC_Currency\main as btc_currency;
use function BPL\Mods\API_Token_Price\main as token_price;
use function BPL\Mods\API\Coinbrain\TokenPrice\main as coinbrain_price_token;

use function Templates\SB_Admin\Tmpl\Master\main as master;

use function BPL\Mods\Url_SEF\sef;
use function bpl\Mods\Url_SEF\qs;

use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\application;
use function BPL\Mods\Helpers\input_get;
use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\settings;
use function BPL\Mods\Helpers\page_validate;
use function BPL\Mods\Helpers\user;
use function BPL\Mods\Helpers\time;
use const QR_ECLEVEL_L;

$content = main();

master($content);

/**
 *
 *
 * @since version
 */
function main()
{
	$account_type = session_get('account_type');
	$user_id = session_get('user_id');
	$amount = input_get('amount');
	$method = input_get('method');
	$cid = input_get('cid');

	page_validate();

	$str = '';

	$app = application();

	$sa = settings('ancillaries');

	$currency = $sa->currency;
	$efund_name = $sa->efund_name;

	$user = user($user_id);

	$account_type = $user->account_type;

	$arr_payment_method = arr_payment_method($user);

	if (empty($arr_payment_method)) {
		$app->enqueueMessage('Please Fill Up Your Payment Method.', 'error');
		$app->redirect(Uri::root(true) . '/' . sef(60) . qs() . 'uid=' . $user_id);
	}

	if (
		($currency === 'PHP')
		&& !array_key_exists('gcash', $arr_payment_method)
		&& !array_key_exists('maya', $arr_payment_method)
		&& !array_key_exists('bank', $arr_payment_method)
	) {
		$app->enqueueMessage('Please Fill Up Your Gcash, Maya, or Bank Details.', 'error');
		$app->redirect(Uri::root(true) . '/' . sef(60) . qs() . 'uid=' . $user_id);
	}

	if ($currency === 'USD' && !array_key_exists('bank', $arr_payment_method)) {
		$app->enqueueMessage('Please Fill Up Your Bank Details.', 'error');
		$app->redirect(Uri::root(true) . '/' . sef(60) . qs() . 'uid=' . $user_id);
	}


	$min_request = $sa->{$account_type . '_min_request_usd'};
	$min_request_format = number_format($min_request, 2);

	$max_request = $sa->{$account_type . '_max_request_usd'};
	$max_request_format = number_format($max_request, 2);

	if ($user->account_type !== 'starter') {
		if ($max_request > 0 && ((double) $user->requested_today + (double) $amount) > $max_request) {
			$app->enqueueMessage('Exceeded Maximum Request for today!', 'error');
			$app->redirect(Uri::root(true) . '/' . sef(73) . qs() . 'uid=' . $user_id);
		}
	}

	if ($cid !== '') {
		process_delete_request($cid);
	}

	if ($amount !== '') {
		process_request($user_id, $amount, $method);
	}

	$view_request_efund = view_request_efund($user_id);

	$notes = <<<HTML
		Enter the requested amount in the box then select your prepared payment method, minimum request is $min_request_format $currency up to $max_request_format $currency maximum request, then press the submit button. On the table below, press the button under "Method" and follow the instructions provided.
	HTML;

	$str .= <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Request $efund_name</h1>
		<!-- <ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">$notes</li>
		</ol> -->				
		$view_request_efund
	</div>
	HTML;

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
function view_request_efund($user_id): string
{
	$view_form = view_form($user_id);

	$notifications = notifications();
	$view_pending_requests = view_pending_requests($user_id);

	return <<<HTML
    <div class="container-fluid px-4">        
		<div class="row justify-content-center">
			<div class="col-lg-5">
				$notifications
				<div class="card mb-4">
					$view_form
				</div>
        	</div>		
		</div>
        $view_pending_requests
    </div>	
HTML;
}

function view_pending_requests($user_id): string
{
	$sa = settings('ancillaries');

	$efund_name = $sa->efund_name;

	$table_pending_requests = table_pending_requests($user_id);

	return <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-hourglass me-1"></i>
				Pending $efund_name Requests
			</div>
			<div class="card-body">
				<table id="datatablesSimple">
					$table_pending_requests
				</table>
			</div>
		</div>
	HTML;
}

function table_pending_requests($user_id)
{
	$sa = settings('ancillaries');
	$currency = $sa->currency;

	$row_pending_requests = row_pending_requests($user_id);

	$str = <<<HTML
		<thead>
			<tr>
				<th>Date</th>
				<th>Amount ($currency)</th>
				<th>Value ($currency)</th>				
				<th>Payment</th>
				<th>Cancel</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Date</th>
				<th>Amount ($currency)</th>
				<th>Value ($currency)</th>				
				<th>Payment</th>
				<th>Cancel</th>
			</tr>
		</tfoot>
		<tbody>
			$row_pending_requests						
		</tbody>
	HTML;

	return $str;
}

function row_pending_requests($user_id): string
{
	$user_id = session_get('user_id');
	$pending = user_efund_request($user_id);
	$efund_name = settings('ancillaries')->efund_name;

	$str = '';

	if (empty($pending)) {
		// $str .= 'No pending ' . $efund_name . ' requests yet.';
	} else {
		foreach ($pending as $tmp) {
			$user_admin = user(1);
			$admin_arr_payment = arr_payment_method($user_admin);
			$admin_payment_address = $admin_arr_payment[$tmp->method] ?? '';
			$payment_method = strtoupper($tmp->method);

			// if (isset($admin_arr_payment[$tmp->method]) && is_array($admin_arr_payment[$tmp->method])) {
			// 	foreach ($admin_arr_payment[$tmp->method] as $k => $v) {
			// 		$payment_method = strtoupper($k);
			// 		$admin_payment_address = $v;
			// 		break;
			// 	}
			// }

			// foreach ($admin_arr_payment as $k => $v) {
			// if (!is_array($v)) {
			// 	// Handle simple key-value pairs
			// 	$str .= '<div class="input-group mb-2">';
			// 	$str .= '<div class="input-group-text">' . ucwords(htmlspecialchars($k)) . '</div>';
			// 	$str .= '<input type="text" class="form-control" value="' . htmlspecialchars($v) . '" readonly>';
			// 	$str .= '</div>';
			// } else {
			// Handle nested arrays
			// $str .= '<div class="input-group mb-2">';
			// $str .= '<div class="input-group-text">' . ucwords(htmlspecialchars($k)) . '</div>';

			// Create a concatenated string of all nested values
			$nested_values = [];
			foreach ($admin_payment_address as $x => $y) {
				$nested_values[] = ucwords(htmlspecialchars($x)) . ': ' . htmlspecialchars($y);
			}

			$admin_payment_details = '<input type="text" class="form-control" value="' . implode(' | ', $nested_values) . '" readonly>';
			// $str .= '</div>';
			// }
			// }

			$currency = in_array($tmp->method, ['bank', 'gcash', 'maya']) ? 'PHP' : $tmp->method;

			$str .= '<tr>';
			$str .= '<td>' . date('M j, Y - g:i A', $tmp->date_requested) . '</td>';
			$str .= '<td>' . number_format($tmp->amount, 2) . ' ' . /* $efund_name . */ '</td>';
			$str .= '<td>' . number_format($tmp->price, 2) . ' ' . /* strtoupper($currency) . */ '</td>';

			// $str .= '<td><input type="button" class="uk-button uk-button-primary" value="' .
			// 	strtoupper($payment_method) . '" data-uk-modal="{target:\'#modal-' . $tmp->request_id . '\'}"></td>';

			$str .= '<td><button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-' . $tmp->request_id . '">' .
				strtoupper($payment_method) . '</button></td>';

			// $str .= '<div id="modal-' . $tmp->request_id . '" class="uk-modal" aria-hidden="true" style="display: none; overflow-y: scroll; margin-top: 150px">';
			// $str .= '<div class="uk-modal-dialog" style="text-align: center">';
			// $str .= '<button type="button" class="uk-modal-close uk-close"></button>';

			$contact_info = arr_contact_info($user_admin);
			$messenger = $contact_info['messenger'] ?? '';
			$contact = $messenger ? '<p><b>Admin Messenger URL:</b> ' . $messenger . '</p>' : '';
			$contact .= isset($user_admin->email) ? '<p><b>Admin Email Address:</b> ' . $user_admin->email . '</p>' : '';

			// if (!in_array($tmp->method, ['bank', 'gcash', 'maya'])) {
			// 	$str .= '<img src="images/trust-wallet.svg" alt="" width="150px">';
			// 	$str .= '<p style="color: red;">After successful transaction, please screenshot the transaction and send it to the email below. The transaction will be processed within 24 hours, and you will see the ' . $efund_name . ' in your dashboard wallet. For any concerns, you can email us anytime.<br><br> -- "Support Team".</p>';
			// 	$str .= $contact;
			// 	$str .= '<img src="' . qr_code_generate($admin_payment_address) . '" alt="QR Code Trust Wallet" style="width:250px;">';
			// 	$str .= '<p>Please pay <b>' . number_format($tmp->price, 8) . '</b> ' . strtoupper($currency) . ' to the following Wallet Address:</p>';
			// 	$str .= '<p><b>' . $admin_payment_address . '</b></p>';
			// } else {

			// }

			$str .= '<div class="modal fade" id="modal-' . $tmp->request_id . '" tabindex="-1" aria-labelledby="modal-' . $tmp->request_id . 'Label" aria-hidden="true">
					<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
						<h1 class="modal-title fs-5" id="modal-' . $tmp->request_id . 'Label">Merchant Information</h1>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">';

			if ($tmp->method === 'gcash') {
				$str .= '<p>Pay <b>' . number_format($tmp->price, 2) . '</b> ' . strtoupper($currency) . ' to the following Gcash Account:</p>';
				$str .= /* '<p><b>' . $admin_payment_address . '</b></p>' */ $admin_payment_details;
			} elseif ($tmp->method === 'maya') {
				$str .= '<p>Pay <b>' . number_format($tmp->price, 2) . '</b> ' . strtoupper($currency) . ' to the following ' . /* strtoupper($payment_method) . */ ' Maya Account:</p>';
				$str .= /* '<p><b>' . $admin_payment_address . '</b></p>' */ $admin_payment_details;
			} elseif ($tmp->method === 'bank') {
				$str .= '<p>Pay <b>' . number_format($tmp->price, 2) . '</b> ' . strtoupper($currency) . ' to the following ' . /* strtoupper($payment_method) . */ ' Bank Account:</p>';
				$str .= /* '<p><b>' . $admin_payment_address . '</b></p>' */ $admin_payment_details;
			}

			$str .= '<p>Please send the screenshot of your payment to the following contact:</p>';

			$str .= $contact;

			$str .= '</div>
						<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>						
						</div>
					</div>
					</div>
				</div>';

			// $str .= '</div></div></td>';

			// $str .= '<td><input type="button" class="uk-button uk-button-primary" value="Cancel" data-uk-modal="{target:\'#modal-cancel-' . $tmp->request_id . '\'}"></td>';

			$str .= '<td><button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-cancel-' . $tmp->request_id . '">Cancel</button></td>';

			$str .= '<div class="modal fade" id="modal-cancel-' . $tmp->request_id . '" tabindex="-1" aria-labelledby="modal-cancel-' . $tmp->request_id . 'Label" aria-hidden="true">
					<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
						<h1 class="modal-title fs-5" id="modal-cancel-' . $tmp->request_id . 'Label">Cancel Request</h1>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
						</div>
						<div class="modal-body">';

			$str .= '<p>Date Requested: ' . date('M j, Y - g:i A', $tmp->date_requested) . '</p>';
			$str .= '<p>Amount: ' . number_format($tmp->amount, 2) . '</p>';
			$str .= '<p>Price: ' . number_format($tmp->price, 2) . ' ' . strtoupper($currency) . '</p>';

			$str .= '</div>
						<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						<a href="' . sef(73) . qs() . 'cid=' . $tmp->request_id . '" type="button" class="btn btn-primary">Confirm</a>
						</div>
					</div>
					</div>
				</div>';

			// $str .= '<div id="modal-cancel-' . $tmp->request_id . '" class="uk-modal" aria-hidden="true" style="display: none; overflow-y: scroll; margin-top: 120px">';
			// $str .= '<div class="uk-modal-dialog" style="text-align: center">';
			// $str .= '<button type="button" class="uk-modal-close uk-close"></button>';
			// $str .= '<p><strong>Are you sure you want to cancel this request?</strong></p>';
			// $str .= '<div class="uk-panel uk-panel-box" style="text-align: left">';
			// $str .= '<h3 class="uk-panel-title"><strong>Date Requested:</strong> ' . date('M j, Y - g:i A', $tmp->date_requested) . '</h3>';
			// $str .= '<h3 class="uk-panel-title"><strong>Amount:</strong> ' . number_format($tmp->amount, 2) . '</h3>';
			// $str .= '<h3 class="uk-panel-title"><strong>Final: </strong> ' . number_format($tmp->price, 2) . ' ' . strtoupper($currency) . '</h3>';
			// $str .= '</div>';
			// $str .= '<div class="uk-modal-footer" style="text-align: right">';
			// $str .= '<input type="button" class="uk-modal-close uk-button uk-button-primary" value="Close">';
			// $str .= '<a href="' . sef(73) . qs() . 'cid=' . $tmp->request_id . '" type="button" class="uk-button uk-button-primary">Confirm</a>';
			// $str .= '</div></div></div></tr>';
		}

		// $str .= '</tbody></table>';
	}

	return $str;
}

function modal_buy_token($admin_payment_address): string
{
	$str = '<div id="modal-buy-token" class="uk-modal" aria-hidden="true" style="display: none; overflow-y: scroll; margin-top: 150px">';

	$str .= '<div class="uk-modal-dialog" style="text-align: center">
                <button type="button" class="uk-modal-close uk-close"></button>               
                <div class="uk-width-1-1 uk-form uk-grid-margin" style="text-align: center">                    
                    <legend>Buy Token in This Wallet Address</legend>
                    <p style="font-weight: bolder; font-size: large">' . $admin_payment_address . '</p>                   
                </div>';

	$str .= '<img src="' . qr_code_generate($admin_payment_address) .
		'" alt="QR Code Trust Wallet" style="width:250px;">';

	$str .= '</div>';

	//	$str .= '<div class="uk-modal-dialog" style="text-align: center">';
//	$str .= '<button type="button" class="uk-modal-close uk-close"></button>';

	//	$str .= '<div class="uk-form-row">
//                                <input type="text" value="' . $admin_payment_address . '" class="uk-form-large uk-form-width-small">
//                                <button class="uk-button uk-button-large" type="reset">Large</button>
//                            </div>';

	$str .= '</div>
	        </div>';

	return $str;
}

function view_form($user_id)
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
            <div class="input-group mb-3">
                <span class="input-group-text"><label for="amount">Amount</label></span>
                <input type="text" name="amount" id="amount" class="form-control" placeholder="Enter Amount" required aria-label="Amount">
                <span class="input-group-text">$currency</span>
            </div>

			<div class="input-group mb-3">
				<select class="form-select" name="method" id="method">
					<option selected value="">Choose...</option>
					<option value="gcash">Gcash</option>
					<option value="maya">Maya</option>
					<option value="bank">Bank</option>
				</select>
				<label class="input-group-text" for="method">Method</label>
			</div>

            <div class="form-group actions">
                <button type="submit" class="btn btn-primary">Buy</button>                
            </div>
        </form>
    </div>
HTML;

	return $str;
}

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

function process_delete_request($cid)
{
	$db = db();

	try {
		$db->transactionStart();

		delete(
			'network_efund_request',
			['request_id = ' . db()->quote($cid)]
		);

		$db->transactionCommit();
	} catch (Exception $e) {
		$db->transactionRollback();

		ExceptionHandler::render($e);
	}

	$app = application();

	$app->enqueueMessage('Request Cancelled!', 'info');
	$app->redirect(Uri::current());
}

/**
 * @param $user_id
 * @param $amount
 *
 * @param $method
 *
 * @since version
 */
function validate_input($user_id, $amount, $method)
{
	$app = application();

	$sa = settings('ancillaries');

	$user = user($user_id);

	$account_type = $user->account_type;

	$minimum_request = $sa->{$account_type . '_min_request_usd'};

	if ($account_type !== 'starter' && $amount < $minimum_request) {
		$app->enqueueMessage('Minimum Amount is ' . $minimum_request . '.', 'error');
		$app->redirect(Uri::current());
	}

	$arr_payment_method = arr_payment_method($user);

	if ($method === '') {
		$app->enqueueMessage("Please Fillup Payment Method!", 'error');
		$app->redirect(Uri::current());
	}

	if ($method !== '' &&/* empty($arr_payment_method) ||  */ empty($arr_payment_method[$method])) {
		$app->enqueueMessage('Your Wallet Address for ' . strtoupper($method) . ' is Required.', 'error');
		$app->redirect(Uri::root(true) . '/' . sef(60) . qs() . 'uid=' . $user_id);
	}

	if ($amount <= 0) {
		$app->enqueueMessage('Please enter valid amount!', 'error');
		$app->redirect(Uri::current());
	}
}

/**
 * @param $user_id
 * @param $amount
 * @param $price
 * @param $method
 *
 * @return false|mixed
 * @since version
 */
function insert_request($user_id, $amount, $price, $method)
{
	$db = db();

	return insert(
		'network_efund_request',
		[
			'user_id',
			'amount',
			'price',
			'method',
			'date_requested'
		],
		[
			$db->quote($user_id),
			$db->quote($amount),
			$db->quote($price),
			$db->quote($method),
			$db->quote(time())
		]
	);
}

function arr_payment_method($user): array
{
	$payment_method = empty($user->payment_method) ? '{}' : $user->payment_method;

	return json_decode($payment_method, true);
}

function php_price_usd()
{
	$url = 'https://api.coingecko.com/api/v3/simple/price?ids=tether&vs_currencies=php';

	$data = [];

	try {
		$json = /*!in_array('curl', get_loaded_extensions()) || is_localhost() ?
		   */
			@file_get_contents($url)/* : file_get_contents_curl($url)*/
		;

		$data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
	} catch (Exception $e) {

	}

	return $data;
}

function price_token_method($value, $method)
{
	if (in_array($method, ['bank', 'gcash', 'maya'])) {
		$php_price_usd = php_price_usd();

		$price_php = 0;

		if ($php_price_usd && isset($php_price_usd['tether']['php'])) {
			//            $ask = $php_price_usd['market']['ask'];
//            $bid = $php_price_usd['market']['bid'];
//
//            $price_php = ($ask + $bid) / 2;

			$price_php = $php_price_usd['tether']['php'];
		}

		$price_res = $price_php; // PHP
	} else {
		$currency = strtoupper($method);

		if (in_array($currency, ['B2P', 'AET', 'TPAY', /*'BTC3', 'BTCB', 'BTCW', 'GOLD', 'PAC', 'P2P',*/ 'PESO'])) {
			$price_res = 1 / price_coinbrain($currency);
		} else {
			$price_method = token_price($currency)['price'];
			$price_base = token_price('USDT')['price'];

			$price_res = $price_base / $price_method;
		}
	}

	return $price_res * $value;
}

/**
 * @param $user_id
 * @param $amount
 *
 * @param $method
 *
 * @since version
 */
function process_request($user_id, $amount, $method)
{
	$db = db();

	$app = application();

	validate_input($user_id, $amount, $method);

	$user = user($user_id);

	$price_total = /* price_token_method($amount, $method) */ $amount;

	$currency = in_array($method, ['bank', 'gcash', 'maya']) ? 'PHP' : $method;

	// mail admin and user
	$message = 'Username: ' . $user->username . '<br>
			Full Name: ' . $user->fullname . '<br>
			Email: ' . $user->email . '<br>
			Contact: ' . $user->contact . '<br>
			Amount Requested: ' . $amount . ' ' .
		'<br>
			Price: ' . $price_total . ' ' . strtoupper($currency) .
		'<br>
			Payment Method: ' . strtoupper($method) . '<br>';

	try {
		$db->transactionStart();

		insert_request($user_id, $amount, $price_total, $method);
		send_mail($message, 'E-Fund Request', [$user->email]);

		$db->transactionCommit();
	} catch (Exception $e) {
		$db->transactionRollback();
		ExceptionHandler::render($e);
	}

	$app->enqueueMessage('Click the method and complete your transaction.', 'success');
	$app->redirect(Uri::current());
}

function view_method_select($user_id): string
{
	$user = user($user_id);

	$pmu = arr_payment_method($user);

	$str = '<select name="method" id="method" style="float:left">';
	$str .= '<option value="none" selected>Currency Payment Method</option>';

	if (!empty($pmu)) {
		foreach ($pmu as $k => $v) {
			// if ($k === 'peso') {
			// 	$str .= '<option value="' . $k . '">' . strtoupper($k) . '</option>';
			// }

			// if ($k === 'busd') {
			// 	$str .= '<option value="' . $k . '">' . strtoupper($k) . '</option>';
			// }

			// if ($k === 'gold') {
			// 	$str .= '<option value="' . $k . '">' . strtoupper($k) . '</option>';
			// }

			// if ($k === 'usdt') {
			// 	$str .= '<option value="' . $k . '">' . strtoupper($k) . '</option>';
			// }

			if ($k === 'bnb') {
				$str .= '<option value="' . $k . '">' . strtoupper($k) . '</option>';
			}

			// if ($k === 'btcb') {
			// 	$str .= '<option value="' . $k . '">' . strtoupper($k) . '</option>';
			// }

			// if ($k === 'btcw') {
			// 	$str .= '<option value="' . $k . '">' . strtoupper($k) . '</option>';
			// }

			// if ($k === 'pac') {
			// 	$str .= '<option value="' . $k . '">' . strtoupper($k) . '</option>';
			// }

			// if ($k === 'shib') {
			// 	$str .= '<option value="' . $k . '">' . strtoupper($k) . '</option>';
			// }

			// if ($k === 'doge') {
			// 	$str .= '<option value="' . $k . '">' . strtoupper($k) . '</option>';
			// }

			// if ($k === 'trx') {
			// 	$str .= '<option value="' . $k . '">' . strtoupper($k) . '</option>';
			// }

			// if ($k === 'usdc') {
			// 	$str .= '<option value="' . $k . '">' . strtoupper($k) . '</option>';
			// }

			//			if ($k === 'gcash')
//			{
//				$str .= '<option value="' . $k . '">' . strtoupper($k) . '</option>';
//			}

			// if ($k === 'bank') {
			// 	$str .= '<option value="' . $k . '">' . strtoupper($k) . '</option>';
			// }

			// if ($k === 'b2p') {
			// 	$str .= '<option value="' . $k . '">' . strtoupper($k) . '</option>';
			// }

			// if ($k === 'aet') {
			// 	$str .= '<option value="' . $k . '">' . strtoupper($k) . '</option>';
			// }

			// if ($k === 'tpay') {
			// 	$str .= '<option value="' . $k . '">' . strtoupper($k) . '</option>';
			// }

			//			if ($k == /*'btc3'*/'p2p')
//			{
//				$str .= '<option value="' . $k . '">' . strtoupper($k) . '</option>';
//			}
		}
	}

	$str .= '</select>';

	return $str/*'<input type="hidden" name="method" value="busd">'*/ ;
}

/**
 * @param $address
 *
 * @return string
 *
 * @since version
 */
//function qr_code_generate($address): string
//{
////	$cht  = "qr";
////	$chs  = "300x300";
////	$chl  = $address;
////	$choe = "UTF-8";
////
////	return 'https://chart.googleapis.com/chart?cht=' . $cht . '&chs=' . $chs . '&chl=' . $chl . '&choe=' . $choe;
//
//    $size = '300x300';
//    return "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($address) . "&size={$size}";
//
////    return 'https://qrcode.tec-it.com/API/QRCode?data=' . $address . '&backcolor=%23ffffff&istransparent=true';
//}

function qr_code_generate($address): string
{
	// Get the system's temporary directory
	$tempDir = sys_get_temp_dir();

	// Ensure the temporary directory is writable
	if (!is_writable($tempDir)) {
		throw new RuntimeException("Temporary directory is not writable.");
	}

	// Create a temporary image path
	$tempFile = tempnam($tempDir, 'qr');

	if ($tempFile === false) {
		throw new RuntimeException("Unable to create temporary file.");
	}

	// Generate the QR code and output it as an image
	QRcode::png($address, $tempFile, QR_ECLEVEL_L, 10);

	// Read the image file and encode it in base64
	$imageData = file_get_contents($tempFile);
	if ($imageData === false) {
		throw new RuntimeException("Unable to read temporary file.");
	}
	$imageData = base64_encode($imageData);

	// Remove the temporary file
	unlink($tempFile);

	// Return the image data as a base64-encoded string
	return 'data:image/png;base64,' . $imageData;
}

function arr_contact_info($user)
{
	$contact_info = empty($user->contact) ? '{}' : $user->contact;

	return json_decode($contact_info, true);
}

/**
 * @param $user_id
 *
 * @return array|mixed
 *
 * @since version
 */
function user_efund_request($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_efund_request ' .
		'WHERE user_id = ' . $db->quote($user_id) .
		' AND date_confirmed = ' . $db->quote(0)
	)->loadObjectList();
}

function price_coinbrain($token = 'BTC3')
{
	switch ($token) {
		case 'B2P':
			$contract = '0xF8AB9fF465C612D5bE6A56716AdF95c52f8Bc72d';
			break;
		case 'BTC3':
			$contract = '0xbea17f143f2535f424c9d7acd5fbff75a9c8ab62';
			break;
		case 'BTCB':
			$contract = '0x7130d2A12B9BCbFAe4f2634d864A1Ee1Ce3Ead9c';
			break;
		case 'BTCW':
			$contract = '0xfc4f8cDC508077e7a60942e812A9C9f1f05020c5';
			break;
		case 'GOLD':
			$contract = '0x4A0bfC65fEb6F477E3944906Fb09652d2d8b5f0d';
			break;
		case 'PAC':
			$contract = '0x565C9e3A95E9d3Df4afa4023204F758C27E38E6a';
			break;
		case 'P2P':
			$contract = '0x07A9e44534BabeBBd25d2825C9465b0a82f26813';
			break;
		case 'PESO':
			$contract = '0xBdFfE2Cd5B9B4D93B3ec462e3FE95BE63efa8BC0';
			break;
		case 'AET':
			$contract = '0xbc26fCCe32AeE5b0D470Ca993fb54aB7Ab173a1E';
			break;
		case 'TPAY':
			$contract = '0xd405200D9c8F8Be88732e8c821341B3AeD6724b7';
			break;
		default:
			$contract = '0xac642e017764c4759efeb1c9ea0782cf5d1a81d1';
	}

	$data = [
		56 => [$contract]
	];

	$price = settings('ancillaries')->currency === 'PHP' ? 0.00012 : 0.0000024;

	$results = json_decode(
		coinbrain_price_token('https://api.coinbrain.com/public/coin-info', $data)
	);

	if (!empty($results)) {
		$results = (array) $results[0];
		$price = $results['priceUsd'];
	}

	return $price;
}