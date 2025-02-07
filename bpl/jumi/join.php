<?php

namespace BPL\Jumi\Join;

require_once 'templates/sb_admin/tmpl/register.tmpl.php';
require_once 'bpl/mods/query.php';
require_once 'bpl/mods/helpers.php';

use Exception;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Session\Session;

use function Templates\SB_Admin\Tmpl\Registration\main as view_registration;

use function BPL\Mods\Database\Query\insert;

use function BPL\Mods\Url_SEF\sef;
use function BPL\Mods\Url_SEF\qs;

use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\user;
use function BPL\Mods\Helpers\settings;
use function BPL\Mods\Helpers\application;
use function BPL\Mods\Helpers\session_set;
use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\input_get;
use function BPL\Mods\Helpers\time;

main();

/**
 *
 *
 * @since version
 */
function main()
{
	session_set('edit', false);

	if (input_get('username') === '') {
		view_registration();
	} else {
		process_form();
	}
}

function validate_input(
	$username,
	$password1,
	$password2,
	$code,
	$sponsor,
	$admintype,
	$edit
) {
	$settings_plans = settings('plans');
	$settings_ancillaries = settings('ancillaries');

	$payment_mode = $settings_ancillaries->payment_mode;

	if ($edit && $admintype === 'Super') {
		$date = input_get('date', '', 'RAW');
	}

	$app = application();

	if ($username === '') {
		$app->enqueueMessage('Please specify your Username.', 'error');
		$app->redirect(Uri::current());
	}

	if ($password1 === '') {
		$app->enqueueMessage('Please specify your Password.', 'error');
		$app->redirect(Uri::current());
	}

	if ($password2 === '') {
		$app->enqueueMessage('Please specify your Password confirmation.', 'error');
		$app->redirect(Uri::current());
	}

	if ($edit && !isset($date)) {
		$app->enqueueMessage('Please specify your Registration Date.', 'error');
		$app->redirect(Uri::current());
	}

	if (count(user_username_unblock($username))) {
		$app->enqueueMessage('Username already taken.', 'error');
		$app->redirect(Uri::current());
	}

	if ($payment_mode === 'CODE' && $code === '') {
		$app->enqueueMessage('Please specify your Registration Code.', 'error');
		$app->redirect(Uri::current());
	}

	if ($password1 !== $password2) {
		$app->enqueueMessage('Your Passwords do not match!', 'error');
		$app->redirect(Uri::current());
	}

	if ($settings_plans->direct_referral) {
		$user_sponsor = user_username($sponsor);

		if ($sponsor === '') {
			$app->enqueueMessage('Please specify your Sponsor Username.', 'error');
			$app->redirect(Uri::current());
		}

		if (empty($user_sponsor) || (empty(user_username_active($sponsor)))) {
			$app->enqueueMessage('Invalid Sponsor Username!', 'error');
			$app->redirect(Uri::current());
		}
	}
}

/**
 * @since version
 */
function process_form()
{
	$db = db();

	// die('hahaha');

	// echo display_loader();

	$admintype = session_get('admintype');
	$edit = session_get('edit');

	$username = input_get('username');
	$password1 = input_get('password1');
	$password2 = input_get('password2');
	$code = input_get('code');
	$sponsor = input_get('sponsor');
	$email = input_get('email', '', 'RAW');

	$app = application();

	// Validate CSRF token
	if (!Session::checkToken()) {
		$app->enqueueMessage('Invalid Transaction!', 'error');
		$app->redirect(Uri::current());
	}

	$email = substr($email, 0, 60);

	session_set_date($admintype, $edit);

	session_set('s_username', $username);
	session_set('s_email', $email);
	session_set('s_password', $password1);
	//	session_set('s_sponsor', $sponsor);

	validate_input(
		$username,
		$password1,
		$password2,
		$code,
		$sponsor,
		$admintype,
		$edit
	);

	$insert_user = insert_user($username, $password1, $sponsor, $email, $admintype, $edit);

	if ($insert_user) {
		$insert_id = $db->insertid();

		$type = 'basic';

		process_leadership_fast_track_principal($insert_id, $type);

		$app->enqueueMessage($username . ' has joined successfully!', 'success');
		$app->redirect(Uri::current());
	} else {
		$app->enqueueMessage('An error occurred during registration. Please try again.', 'error');
		$app->redirect(Uri::current());
	}
}

function process_leadership_fast_track_principal($insert_id, $code_type)
{
	$sp = settings('plans');
	$slftp = settings('leadership_fast_track_principal');
	$se = settings('entry');

	$username = input_get('username');
	$sponsor = input_get('sponsor');

	$edit = session_get('edit');

	$lftp_level = $slftp->{$code_type . '_leadership_fast_track_principal_level'};

	$sponsor_id = '';

	$user_sponsor = user_username($sponsor);

	if (!empty($user_sponsor)) {
		$sponsor_id = $user_sponsor[0]->id;
	}

	$date = time();

	$db = db();

	if (
		$lftp_level &&
		$sp->leadership_fast_track_principal
	) {
		insert(
			'network_leadership_fast_track_principal',
			['id', 'user_id'],
			[$db->quote($insert_id), $db->quote($insert_id)]
		);

		$activity = '<b>' . ucwords($sp->leadership_fast_track_principal_name) . ' Entry: </b> <a href="' .
			sef(44) . qs() . 'uid=' . $insert_id . '">' . $username . '</a> has entered into ' .
			ucwords($sp->leadership_fast_track_principal_name) . ' upon ' .
			ucfirst($se->{$code_type . '_package_name'}) . ' Sign Up.';

		insert(
			'network_activity',
			[
				'user_id',
				'sponsor_id',
				'activity',
				'activity_date'
			],
			[
				$db->quote($insert_id),
				$db->quote($sponsor_id),
				$db->quote($activity),
				($edit === true && (int) $date !== 0 ? $db->quote($date) : $db->quote(time()))
			]
		);
	}
}

/**
 * @param $username
 * @param $password
 * @param $sponsor
 * @param $email
 * @param $admintype
 * @param $edit
 *
 * @return false|mixed
 *
 * @since version
 */
function insert_user($username, $password, $sponsor, $email, $admintype, $edit)
{
	$db = db();

	$sp = settings('plans');
	$sa = settings('ancillaries');

	$payment_mode = $sa->payment_mode;

	$email = substr($email, 0, 60);

	$date = input_get_date($admintype, $edit);

	// sponsor
	$sponsor_id = '1';

	if ($sp->direct_referral_fast_track_principal) {
		$user_sponsor = user_username($sponsor);

		if (!empty($user_sponsor)) {
			$sponsor_id = $user_sponsor[0]->id;
		}
	}

	$columns_user_insert = [
		'username',
		'password',
		'sponsor_id',
		'date_registered',
		'date_activated',
		'email'
	];

	$date_registered = ($edit && isset($date) ? $db->quote($date) : $db->quote(time()));

	if ($payment_mode === 'CODE') {
		$date_activated = $date_registered;
	} else {
		$date_activated = ($edit && (int) $date !== 0 ? $db->quote($date) : $db->quote(0));
	}

	$values_user_insert = [
		$db->quote($username),
		$db->quote(md5($password)),
		$db->quote($sponsor_id),
		$date_registered,
		$date_activated,
		$db->quote($email)
	];

	if ($payment_mode === 'ECASH') {
		$columns_user_insert[] = 'account_type';
		$values_user_insert[] = $db->quote('basic');
	}

	return insert(
		'network_users',
		$columns_user_insert,
		$values_user_insert
	);
}

/**
 * @param $code
 *
 * @return string
 *
 * @since version
 */
function code_type($code): string
{
	$code_type = 'starter';

	$code_user = code_user($code);

	if ($code_user) {
		$code_type = $code_user[0]->type;
	}

	return $code_type;
}

/**
 * @param $code
 *
 * @return array|mixed
 *
 * @since version
 */
function code_user($code)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_codes ' .
		'WHERE code = ' . $db->quote($code)/* .
' AND user_id = ' . $db->quote($insert_id)*/
	)->loadObjectList();
}

/**
 * @param $insert_id
 * @param $code_type
 * @param $username
 * @param $sponsor
 *
 *
 * @since version
 */
function log_registration_activity($insert_id, $code_type, $username, $sponsor)
{
	$db = db();

	// sponsor
	$sponsor_id = '';
	$sponsor_name = '';

	$user_sponsor = user_username($sponsor);

	if (!empty($user_sponsor)) {
		$sponsor_id = $user_sponsor[0]->id;
		$sponsor_name = $user_sponsor[0]->username;
	}

	$code_type_mod = settings('entry')->{$code_type . '_package_name'};

	$activity = '<b>Sign up: </b><a href="' . sef(44) . qs() . 'uid=' . $insert_id . '">' .
		$username . ' (' . ucfirst($code_type_mod) . ')' . '</a>, sponsored by <a href="' .
		sef(44) . qs() . 'uid=' . $sponsor_id . '">' . $sponsor_name . '</a>.';

	$field = [
		'user_id',
		'sponsor_id',
		'activity',
		'activity_date'
	];

	$value = [
		$db->quote($insert_id),
		$db->quote($sponsor_id),
		$db->quote($activity),
		$db->quote(time())
	];

	// activity
	insert('network_activity', $field, $value);
}

/**
 * @param $insert_id
 * @param $code_type
 * @param $username
 * @param $sponsor
 *
 *
 * @since version
 */
function log_registration_transactions($insert_id, $code_type, $username, $sponsor)
{
	$db = db();

	$entry = settings('entry')->{$code_type . '_entry'};

	// sponsor
	$sponsor_id = '';
	$sponsor_name = '';

	$user_sponsor = user_username($sponsor);

	if (!empty($user_sponsor)) {
		$sponsor_id = $user_sponsor[0]->id;
		$sponsor_name = $user_sponsor[0]->username;
	}

	$details = '<b>Sign up: ' . number_format($entry, 2) . settings('ancillaries')->currency .
		' paid by </b><a href="' . sef(44) . qs() . 'uid=' . $insert_id . '">' . $username .
		' (' . ucfirst(settings('entry')->{$code_type . '_package_name'}) . ')' .
		'</a>, sponsored by <a href="' . sef(44) . qs() . 'uid=' . $sponsor_id . '">' . $sponsor_name . '</a>.';

	// transactions
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
			$db->quote($insert_id),
			$db->quote('Sign Up'),
			$db->quote($details),
			$db->quote($entry),
			$db->quote(0),
			$db->quote(time())
		]
	);
}

/**
 *
 * @return string
 *
 * @since version
 */
function sponsor(): string
{
	$sid = input_get('s');
	$user_id = session_get('user_id');

	$sponsor = '';

	if ($sid !== '') {
		$sponsor = $sid;
	} elseif ($user_id !== '') {
		$sponsor = user($user_id)->username ?? '';
	}

	return $sponsor;
}

/**
 * @param $username
 *
 * @return array|mixed
 *
 * @since version
 */
function user_username($username)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_users ' .
		'WHERE username = ' . $db->quote($username)
	)->loadObjectList();
}

/**
 * @param $username
 *
 * @return array|mixed
 *
 * @since version
 */
function user_username_active($username)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_users ' .
		'WHERE username = ' . $db->quote($username) .
		' AND account_type <> ' . $db->quote('starter')
	)->loadObjectList();
}

/**
 * @param $username
 *
 * @return array|mixed
 *
 * @since version
 */
function user_username_unblock($username)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_users ' .
		'WHERE username = ' . $db->quote($username) .
		' AND block = ' . $db->quote(0)
	)->loadObjectList();
}

/**
 *
 * @param $admintype
 * @param $edit
 *
 * @return string
 *
 * @since version
 */
function input_get_date($admintype, $edit): string
{
	$date = time();

	if ($edit && $admintype === 'Super') {
		$date = input_get('date', 0, 'RAW');
	}

	return $date;
}

/**
 * @param $admintype
 * @param $edit
 *
 *
 * @since version
 */
function session_set_date($admintype, $edit)
{
	$date = input_get_date($admintype, $edit);

	if ($edit && $admintype === 'Super' && (int) $date !== 0) {
		session_set('s_date', $date);
	}
}