<?php

namespace BPL\Leadership_Fast_Track_Principal;

// Include necessary files for database queries and helper functions
require_once 'bpl/mods/query.php';
require_once 'bpl/mods/helpers.php';
require_once 'bpl/mods/url_sef.php';

// Import functions from other namespaces
use function BPL\Mods\Database\Query\update;
use function BPL\Mods\Database\Query\insert;

use function BPL\Mods\Url_SEF\sef;
use function BPL\Mods\Url_SEF\qs;

use function BPL\Mods\Helpers\db;
// use function BPL\Mods\Helpers\user;
use function BPL\Mods\Helpers\users;
use function BPL\Mods\Helpers\settings;

/**
 * Main function to process leadership fast track principal bonuses for all users.
 */
function main()
{
	// Iterate through each user and process their bonus
	foreach (users() as $user) {
		process_user_lftp($user);
	}
}

/**
 * Process bonuses for a single user.
 *
 * @param object $user The user object.
 */
function process_user_lftp($user)
{
	$slftp = settings('leadership_fast_track_principal');

	$account_type = $user->account_type;
	$count_directs = count(user_directs($user->id));

	// Retrieve settings for the user's account type
	$type_level = $slftp->{$account_type . '_leadership_fast_track_principal_level'};
	$required_directs = $slftp->{$account_type . '_leadership_fast_track_principal_sponsored'};
	$max_daily_income = $slftp->{$account_type . '_leadership_fast_track_principal_max_daily_income'};
	$income_max = $slftp->{$account_type . '_leadership_fast_track_principal_maximum'};

	$user_bonus_lftp = $user->bonus_leadership_fast_track_principal;
	$ulftp = user_lftp($user->id);
	$income_today = $ulftp->income_today;

	// Check if the user is eligible for the bonus
	if ($type_level > 0 && $count_directs >= $required_directs) {
		$lftp_total = lftp_total($user, $type_level);
		$lftp_add = $lftp_total - $ulftp->bonus_leadership_fast_track_principal_last;

		if ($lftp_add > 0) {
			// Apply daily income and maximum income limits
			if ($max_daily_income > 0 && ($income_today + $lftp_add) >= $max_daily_income) {
				$lftp_add = non_zero($max_daily_income - $income_today);
			}

			if ($income_max > 0 && ($user_bonus_lftp + $lftp_add) >= $income_max) {
				$lftp_add = non_zero($income_max - $user_bonus_lftp);
			}

			// Update the user's bonus and log the activity
			update_lftp($lftp_add, $lftp_total, $user->id);
			update_user($lftp_add, $user->id);
			log_activity($user, $lftp_total);
		}
	}
}

/**
 * Retrieve leadership fast track principal details for a given user ID.
 *
 * @param int $user_id The ID of the user.
 * @return object|null The leadership fast track principal details or null if not found.
 */
function user_lftp($user_id)
{
	$db = db();

	// Query the database for leadership fast track principal details
	return $db->setQuery(
		'SELECT * ' .
		'FROM network_leadership_fast_track_principal ' .
		'WHERE user_id = ' . $db->quote($user_id)
	)->loadObject();
}

/**
 * Update user's bonus and balance or payout transfer based on the withdrawal mode.
 *
 * @param float $bonus The bonus amount to add.
 * @param int $user_id The ID of the user.
 */
function update_user($bonus, $user_id)
{
	// Prepare fields to update
	$field_user = ['bonus_leadership_fast_track_principal = bonus_leadership_fast_track_principal + ' . $bonus];

	// Check withdrawal mode and update balance or payout transfer accordingly
	if (settings('ancillaries')->withdrawal_mode === 'standard') {
		$field_user[] = 'balance = balance + ' . $bonus;
	} else {
		$field_user[] = 'payout_transfer = payout_transfer + ' . $bonus;
	}

	// Update the user's record in the database
	update(
		'network_users',
		$field_user,
		['id = ' . db()->quote($user_id)]
	);
}

/**
 * Calculate the total bonus for a user based on their level.
 *
 * @param object $user The user object.
 * @param int $type_level The user's level.
 * @return float The total bonus.
 */
function lftp_total($user, $type_level)
{
	$total = 0;

	// Calculate the bonus for each level up to the user's level
	for ($level = 1; $level <= $type_level; $level++) {
		$total += calculate_level_bonus($user, $level);
	}

	return $total;
}

/**
 * Calculate the bonus for a specific level.
 *
 * @param object $user The user object.
 * @param int $level The level to calculate the bonus for.
 * @return float The bonus for the level.
 */
function calculate_level_bonus($user, $level)
{
	$users = get_level_users($user, $level);
	return bonus_lftp($level, $users);
}

/**
 * Calculate the leadership fast track principal bonus for a specific level.
 *
 * @param int $level The level to calculate the bonus for.
 * @param array $users The users to calculate the bonus for.
 * @return float The total bonus amount.
 */
function bonus_lftp($level, $users)
{
	$bonus = 0;

	if (!empty($users)) {
		foreach ($users as $user) {
			$account_type = $user->account_type;

			$slftp = settings('leadership_fast_track_principal');

			// Calculate the share and share cut based on the account type and level
			$share = $slftp->{$account_type . '_leadership_fast_track_principal_share_' . $level} / 100;
			$share_cut = $slftp->{$account_type . '_leadership_fast_track_principal_share_cut_' . $level} / 100;

			$fast_track_principal = $user->fast_track_principal;

			// Calculate the bonus for the user
			$bonus += $fast_track_principal * $share * $share_cut;
		}
	}

	return $bonus;
}

/**
 * Calculate the total number of members across all levels.
 *
 * @param object $user The user object.
 * @return int The total number of members.
 */
function members_total($user): int
{
	$total = 0;

	// Calculate the total number of members across all levels
	for ($level = 1; $level <= 10; $level++) {
		$total += count(get_level_users($user, $level));
	}

	return $total;
}

/**
 * Get users at a specific level.
 *
 * @param object $user The user object.
 * @param int $level The level to get users for.
 * @return array The users at the specified level.
 */
function get_level_users($user, $level)
{
	$users = [$user];

	// Get direct referrals for each level
	for ($i = 1; $i <= $level; $i++) {
		$users = level_directs($users);
	}

	return $users;
}

/**
 * Get direct referrals for a list of users.
 *
 * @param array $lvl_1 The list of users.
 * @return array The list of direct referrals.
 */
function level_directs(array $lvl_1 = []): array
{
	$lvl_directs = [];

	if (!empty($lvl_1)) {
		foreach ($lvl_1 as $s1) {
			$directs = user_directs($s1->id);

			if (!empty($directs)) {
				foreach ($directs as $direct) {
					$lvl_directs[] = $direct;
				}
			}
		}
	}

	return $lvl_directs;
}

/**
 * Log an activity for a user earning a bonus.
 *
 * @param object $user The user object.
 * @param float $bonus The bonus amount earned.
 */
function log_activity($user, $bonus)
{
	$db = db();

	// Insert a new activity log entry
	insert(
		'network_activity',
		[
			'user_id',
			'sponsor_id',
			'activity',
			'activity_date'
		],
		[
			$db->quote($user->id),
			$db->quote($user->sponsor_id),
			$db->quote(
				'<b>' . settings('plans')->leadership_fast_track_principal_name .
				' Bonus: </b> <a href="' . sef(44) . qs() . 'uid=' . $user->id . '">' .
				$user->username . '</a> has earned ' . number_format($bonus, 2) .
				' ' . settings('ancillaries')->currency
			),
			($db->quote(time()))
		]
	);
}

/**
 * Update the leadership fast track principal details for a user.
 *
 * @param float $lftp_add The amount to add to the bonus.
 * @param float $lftp The total bonus amount.
 * @param int $user_id The ID of the user.
 */
function update_lftp($lftp_add, $lftp, $user_id)
{
	$db = db();

	// Update the leadership fast track principal details in the database
	update(
		'network_leadership_fast_track_principal',
		[
			'bonus_leadership_fast_track_principal = bonus_leadership_fast_track_principal + ' . $lftp_add,
			'bonus_leadership_fast_track_principal_now = bonus_leadership_fast_track_principal_now + ' . $lftp_add,
			'bonus_leadership_fast_track_principal_last = ' . $db->quote($lftp),
			'income_today = income_today + ' . $lftp_add
		],
		['user_id = ' . $db->quote($user_id)]
	);
}

/**
 * Retrieve direct referrals for a given sponsor ID.
 *
 * @param int $sponsor_id The ID of the sponsor.
 * @return array An array of user objects representing direct referrals.
 */
function user_directs($sponsor_id): array
{
	$db = db();

	// Query the database for direct referrals excluding 'starter' account types
	return $db->setQuery(
		'SELECT * ' .
		'FROM network_users ' .
		'WHERE account_type <> ' . $db->quote('starter') .
		'AND sponsor_id = ' . $db->quote($sponsor_id)
	)->loadObjectList();
}

/**
 * Ensure the value is non-negative.
 *
 * @param float $value The value to check.
 * @return float The non-negative value.
 */
function non_zero($value)
{
	return $value < 0 ? 0 : $value;
}