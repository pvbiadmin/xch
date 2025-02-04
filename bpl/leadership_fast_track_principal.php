<?php

namespace BPL\Leadership_Fast_Track_Principal;

require_once 'bpl/mods/query.php';
require_once 'bpl/mods/helpers.php';

use function BPL\Mods\Database\Query\update;
use function BPL\Mods\Database\Query\insert;
use function BPL\Mods\Url_SEF\sef;
use function BPL\Mods\Url_SEF\qs;
use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\user;
use function BPL\Mods\Helpers\users;
use function BPL\Mods\Helpers\settings;

/**
 * Main function to process leadership fast track principal bonuses.
 */
function main()
{
	foreach (users() as $user) {
		process_user_bonus($user);
	}
}

function user_directs($sponsor_id): array
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_users ' .
		'WHERE account_type <> ' . $db->quote('starter') .
		'AND sponsor_id = ' . $db->quote($sponsor_id)
	)->loadObjectList();
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

function update_user($bonus, $user_id)
{
	$field_user = ['bonus_leadership_fast_track_principal = bonus_leadership_fast_track_principal + ' . $bonus];

	if (settings('ancillaries')->withdrawal_mode === 'standard') {
		$field_user[] = 'balance = balance + ' . $bonus;
	} else {
		$field_user[] = 'payout_transfer = payout_transfer + ' . $bonus;
	}

	update(
		'network_users',
		$field_user,
		['id = ' . db()->quote($user_id)]
	);
}

function non_zero($value)
{
	return $value < 0 ? 0 : $value;
}

function update_leadership_fast_track_principal($lftp_add, $lftp, $user_id)
{
	$db = db();

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

function log_activity($user, $bonus)
{
	$db = db();

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

function bonus_leadership_fast_track_principal($level, $users)
{
	$bonus = 0;

	if (!empty($users)) {
		foreach ($users as $user) {
			$account_type = $user->account_type;

			$slftp = settings('leadership_fast_track_principal');

			$share = $slftp->{$account_type . '_leadership_fast_track_principal_share_' . $level} / 100;
			$share_cut = $slftp->{$account_type . '_leadership_fast_track_principal_share_cut_' . $level} / 100;

			$fast_track_principal = $user->fast_track_principal;

			$bonus += $fast_track_principal * $share * $share_cut;
		}
	}

	return $bonus;
}

/**
 * Process bonuses for a single user.
 *
 * @param object $user The user object.
 */
function process_user_bonus($user)
{
	$slftp = settings('leadership_fast_track_principal');

	$account_type = $user->account_type;
	$count_directs = count(user_directs($user->id));

	$type_level = $slftp->{$account_type . '_leadership_fast_track_principal_level'};
	$required_directs = $slftp->{$account_type . '_leadership_fast_track_principalsponsored'};
	$max_daily_income = $slftp->{$account_type . '_leadership_fast_track_principal_max_daily_income'};
	$income_max = $slftp->{$account_type . '_leadership_fast_track_principal_maximum'};

	$user_bonus_lftp = $user->bonus_leadership_fast_track_principal;
	$ulftp = user_leadership_fast_track_principal($user->id);
	$income_today = $ulftp->income_today;

	if ($type_level > 0 && $count_directs >= $required_directs) {
		$lftp_total = calculate_bonus_total($user, $type_level);
		$lftp_add = $lftp_total - $ulftp->bonus_leadership_fast_track_principal_last;

		if ($lftp_add > 0) {
			if ($max_daily_income > 0 && ($income_today + $lftp_add) >= $max_daily_income) {
				$lftp_add = non_zero($max_daily_income - $income_today);
			}

			if ($income_max > 0 && ($user_bonus_lftp + $lftp_add) >= $income_max) {
				$lftp_add = non_zero($income_max - $user_bonus_lftp);
			}

			update_leadership_fast_track_principal($lftp_add, $lftp_total, $user->id);
			update_user($lftp_add, $user->id);
			log_activity($user, $lftp_total);
		}
	}
}

/**
 * Calculate the total bonus for a user based on their level.
 *
 * @param object $user The user object.
 * @param int $type_level The user's level.
 * @return float The total bonus.
 */
function calculate_bonus_total($user, $type_level)
{
	$total = 0;

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
	return bonus_leadership_fast_track_principal($level, $users);
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

	for ($i = 1; $i <= $level; $i++) {
		$users = level_directs($users);
	}

	return $users;
}

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
 * Generate the view for a user.
 *
 * @param int $user_id The user ID.
 * @return string The HTML view.
 */
function view($user_id): string
{
	$user = user($user_id);
	$slftp = settings('leadership_fast_track_principal');

	$account_type = $user->account_type;
	$required_directs = $slftp->{$account_type . '_leadership_fast_track_principal_sponsored'};
	$level = $slftp->{$account_type . '_leadership_fast_track_principal_level'};

	$status = count(user_directs($user->id)) >= $required_directs ? '' : ' (inactive)';

	$str = '<h3>List ' . settings('plans')->leadership_fast_track_principal_name . '</h3>
        <table class="category table table-striped table-bordered table-hover">
            <thead>
            <tr>
                <th><div style="text-align: center"><h4>Level</h4></div></th>
                <th><div style="text-align: center"><h4>Accounts</h4></div></th>
                <th><div style="text-align: center"><h4>Profit</h4></div></th>
                <th><div style="text-align: center"><h4>Fixed Rate (%)</h4></div></th>
            </tr>
            </thead>
            <tbody>';

	for ($i = 1; $i <= $level; $i++) {
		$str .= view_row($i, $user);
	}

	$str .= '<tr>
                <td><div style="text-align: center"><strong>Total' . $status . '</strong></div></td>
                <td><div style="text-align: center">' . members_total($user) . '</div></td>
                <td><div style="text-align: center">' . number_format(calculate_bonus_total($user, $level), 8) . '</div></td>
                <td><div style="text-align: center">N/A</div></td>
            </tr>
            </tbody>
        </table>';

	return $str;
}

/**
 * Generate a table row for a specific level.
 *
 * @param int $level The level.
 * @param object $user The user object.
 * @return string The HTML row.
 */
function view_row($level, $user): string
{
	$slftp = settings('leadership_fast_track_principal');
	$members = count(get_level_users($user, $level));
	$bonus = calculate_level_bonus($user, $level);

	$share = $slftp->{$user->account_type . '_leadership_fast_track_principal_share_' . $level};
	$share_cut = $slftp->{$user->account_type . '_leadership_fast_track_principal_share_cut_' . $level};
	$percentage = $share * $share_cut / 100;

	return '<tr>
                <td><div style="text-align: center"><strong>' . $level . '</strong></div></td>
                <td><div style="text-align: center">' . $members . '</div></td>
                <td><div style="text-align: center">' . number_format($bonus, 8) . '</div></td>
                <td><div style="text-align: center">' . number_format($percentage, 2) . '</div></td>
            </tr>';
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

	for ($level = 1; $level <= 10; $level++) {
		$total += count(get_level_users($user, $level));
	}

	return $total;
}