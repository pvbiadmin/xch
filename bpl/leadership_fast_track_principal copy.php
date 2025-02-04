<?php

namespace BPL\Leadership_Fast_Track_Principal_Copy;

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
 *
 *
 * @since version
 */
function main()
{
	$slftp = settings('leadership_fast_track_principal');

	foreach (users() as $user) {
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
			$lftp_total = bonus_total($user);

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
}

/**
 * @param $value
 *
 * @return int|mixed
 *
 * @since version
 */
function non_zero($value)
{
	return $value < 0 ? 0 : $value;
}

/**
 * @param $user_id
 *
 * @return string
 *
 * @since version
 */
function view($user_id): string
{
	$user = user($user_id);

	$account_type = $user->account_type;

	$slftp = settings('leadership_fast_track_principal');

	$required_directs = $slftp->{$account_type . '_leadership_fast_track_principal_sponsored'};
	$level = $slftp->{$account_type . '_leadership_fast_track_principal_level'};

	$status = count(user_directs($user->id)) >= $required_directs ? '' : ' (inactive)';

	// $currency = settings('ancillaries')->currency;

	$str = '<h3>List ' . settings('plans')->leadership_fast_track_principal_name . '</h3>
        <table class="category table table-striped table-bordered table-hover">
            <thead>
            <tr>
                <th>
                    <div style="text-align: center"><h4>Level</h4></div>
                </th>
                <th>
                    <div style="text-align: center"><h4>Accounts</h4></div>
                </th>
                <th>
                    <div style="text-align: center"><h4>Profit</h4></div>
                </th>
                <th>
                    <div style="text-align: center"><h4>Fixed Rate (%)</h4></div>
                </th>
            </tr>
            </thead>
            <tbody>';

	switch ((int) $level) {
		case 1:
			$str .= view_row(1, $user);

			break;
		case 2:
			$str .= view_row(1, $user);
			$str .= view_row(2, $user);

			break;
		case 3:
			$str .= view_row(1, $user);
			$str .= view_row(2, $user);
			$str .= view_row(3, $user);

			break;
		case 4:
			$str .= view_row(1, $user);
			$str .= view_row(2, $user);
			$str .= view_row(3, $user);
			$str .= view_row(4, $user);

			break;
		case 5:
			$str .= view_row(1, $user);
			$str .= view_row(2, $user);
			$str .= view_row(3, $user);
			$str .= view_row(4, $user);
			$str .= view_row(5, $user);

			break;
		case 6:
			$str .= view_row(1, $user);
			$str .= view_row(2, $user);
			$str .= view_row(3, $user);
			$str .= view_row(4, $user);
			$str .= view_row(5, $user);
			$str .= view_row(6, $user);

			break;
		case 7:
			$str .= get_str_row7($user);

			break;
		case 8:
			$str .= get_str_row7($user);
			$str .= view_row(8, $user);

			break;
		case 9:
			$str .= get_str_row7($user);
			$str .= view_row(8, $user);
			$str .= view_row(9, $user);

			break;
		case 10:
			$str .= get_str_row7($user);
			$str .= view_row(8, $user);
			$str .= view_row(9, $user);
			$str .= view_row(10, $user);

			break;
	}

	// $ulp = user_leadership_fast_track_principal($user_id);

	// $flushout_local = $ulp->flushout_local;
	// $flushout_global = $ulp->flushout_global;

	$str .= '<tr>
                <td>
                    <div style="text-align: center"><strong>Total' . $status . '</strong></div>
                </td>
                <td>
                    <div style="text-align: center">' . (members_total($user)) . '</div>
                </td>
                <td>
                    <div style="text-align: center">' .
		number_format((bonus_total($user)/* - $flushout_local - $flushout_global*/), 8) . '</div>
                </td>              
                <td>
                    <div style="text-align: center">N/A</div>
                </td>
            </tr>
            </tbody>
        </table>';

	return $str;
}

function insert_leadership_fast_track_principal($insert_id, $code_type, $username, $sponsor_id, $date, string $prov = 'code')
{
	if (empty(user_leadership_fast_track_principal($insert_id))) {
		insert(
			'network_leadership_fast_track_principal',
			['user_id'],
			[db()->quote($insert_id)]
		);

		logs_leadership_fast_track_principal($insert_id, $code_type, $username, $sponsor_id, $date, $prov);
	}
}

function logs_leadership_fast_track_principal($insert_id, $code_type, $username, $sponsor, $date, $prov)
{
	$db = db();

	$settings_plans = settings('plans');

	$sponsor_id = '';

	$user_sponsor = user_username($sponsor);

	if (!empty($user_sponsor)) {
		$sponsor_id = $user_sponsor[0]->id;
	}

	$activity = '<b>' . ucwords($settings_plans->leadership_fast_track_principal_name) . ' Entry: </b> <a href="' .
		sef(44) . qs() . 'uid=' . $insert_id . '">' . $username . '</a> has entered into ' .
		ucwords($settings_plans->leadership_fast_track_principal_name) . ' upon ' .
		ucfirst(settings('entry')->{$code_type . '_package_name'}) . source($prov) . '.';

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
			$db->quote($date)
		]
	);
}

function source($prov): string
{
	$source = ' Sign Up';

	if ($prov === 'activate') {
		$source = ' Activation';
	} elseif ($prov === 'upgrade') {
		$source = ' Upgrade';
	}

	return $source;
}

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
 * @param $level
 * @param $user
 *
 * @return string
 *
 * @since version
 */
function view_row($level, $user): string
{
	$slftp = settings('leadership_fast_track_principal');

	switch ((int) $level) {
		case 1:
			$members = members_lvl1($user);
			$bonus = leadership_fast_track_principal_lvl1($user);
			break;
		case 2:
			$members = members_lvl2($user);
			$bonus = leadership_fast_track_principal_lvl2($user);
			break;
		case 3:
			$members = members_lvl3($user);
			$bonus = leadership_fast_track_principal_lvl3($user);
			break;
		case 4:
			$members = members_lvl4($user);
			$bonus = leadership_fast_track_principal_lvl4($user);
			break;
		case 5:
			$members = members_lvl5($user);
			$bonus = leadership_fast_track_principal_lvl5($user);
			break;
		case 6:
			$members = members_lvl6($user);
			$bonus = leadership_fast_track_principal_lvl6($user);
			break;
		case 7:
			$members = members_lvl7($user);
			$bonus = leadership_fast_track_principal_lvl7($user);
			break;
		case 8:
			$members = members_lvl8($user);
			$bonus = leadership_fast_track_principal_lvl8($user);
			break;
		case 9:
			$members = members_lvl9($user);
			$bonus = leadership_fast_track_principal_lvl9($user);
			break;
		case 10:
			$members = members_lvl10($user);
			$bonus = leadership_fast_track_principal_lvl10($user);
			break;
		default:
			$members = 0;
			$bonus = 0;
			break;
	}

	$str = '<tr>';

	$str .= '<td>
                        <div style="text-align: center" ' .
		/* ($level === 1 ? 'style="color: red"' : '') . */ '>
                            <strong>' . /* ((int) $level !== 1 ? $level : '') .
($level === 1 ? ' (Direct)' : '') */ $level . '</strong>
                        </div>
                    </td>';

	$str .= '<td>
                        <div style="text-align: center" ' .
		($level === 1 ? 'style="color: red"' : '') . '>' .
		($level === 1 ? ('(' . $members . ')') : $members) . '</div>
                    </td>';

	$str .= '<td>
                        <div style="text-align: center" ' .
		((int) $level === 1 ? 'style="color: red"' : '') . '>' .
		((int) $level === 1 ? ('(' . number_format($bonus, 8) . ')') :
			number_format($bonus, 8)) . '</div>
                    </td>';

	$share = $slftp->{$user->account_type . '_leadership_fast_track_principal_share_' . $level};
	$share_cut = $slftp->{$user->account_type . '_leadership_fast_track_principal_share_cut_' . $level};

	$percentage = $share * $share_cut / 100;

	$str .= '<td>
                        <div style="text-align: center" ' .
		((int) $level === 1 ? 'style="color: red"' : '') . '>' .
		((int) $level === 1 ? ('(' . number_format($percentage, 2) . ')') :
			number_format($percentage, 2)) . '</div>
                    </td>';

	$str .= '</tr>';

	return $str;
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

/**
 * @param $user
 *
 * @return string
 *
 * @since version
 */
function get_str_row7($user): string
{
	$str = view_row(1, $user);
	$str .= view_row(2, $user);
	$str .= view_row(3, $user);
	$str .= view_row(4, $user);
	$str .= view_row(5, $user);
	$str .= view_row(6, $user);
	$str .= view_row(7, $user);

	return $str;
}

/**
 * @param $user
 *
 * @return float|int
 *
 * @since version
 */
function bonus_total($user)
{
	$slftp = settings('leadership_fast_track_principal');

	$account_type = $user->account_type;

	$required_directs = $slftp->{$account_type . '_leadership_fast_track_principal_sponsored'};
	$type_level = $slftp->{$account_type . '_leadership_fast_track_principal_level'};

	$total = 0;

	if (count(user_directs($user->id)) >= $required_directs) {
		switch ($type_level) {
			case 1:
				$total = leadership_fast_track_principal_lvl1($user);

				break;
			case 2:
				$total = (
					leadership_fast_track_principal_lvl1($user) +
					leadership_fast_track_principal_lvl2($user));

				break;
			case 3:
				$total = (
					leadership_fast_track_principal_lvl1($user) +
					leadership_fast_track_principal_lvl2($user) +
					leadership_fast_track_principal_lvl3($user));

				break;
			case 4:
				$total = (
					leadership_fast_track_principal_lvl1($user) +
					leadership_fast_track_principal_lvl2($user) +
					leadership_fast_track_principal_lvl3($user) +
					leadership_fast_track_principal_lvl4($user));

				break;
			case 5:
				$total = (
					leadership_fast_track_principal_lvl1($user) +
					leadership_fast_track_principal_lvl2($user) +
					leadership_fast_track_principal_lvl3($user) +
					leadership_fast_track_principal_lvl4($user) +
					leadership_fast_track_principal_lvl5($user));

				break;
			case 6:
				$total = (
					leadership_fast_track_principal_lvl1($user) +
					leadership_fast_track_principal_lvl2($user) +
					leadership_fast_track_principal_lvl3($user) +
					leadership_fast_track_principal_lvl4($user) +
					leadership_fast_track_principal_lvl5($user) +
					leadership_fast_track_principal_lvl6($user));

				break;
			case 7:
				$total = (
					leadership_fast_track_principal_lvl1($user) +
					leadership_fast_track_principal_lvl2($user) +
					leadership_fast_track_principal_lvl3($user) +
					leadership_fast_track_principal_lvl4($user) +
					leadership_fast_track_principal_lvl5($user) +
					leadership_fast_track_principal_lvl6($user) +
					leadership_fast_track_principal_lvl7($user));

				break;
			case 8:
				$total = (
					leadership_fast_track_principal_lvl1($user) +
					leadership_fast_track_principal_lvl2($user) +
					leadership_fast_track_principal_lvl3($user) +
					leadership_fast_track_principal_lvl4($user) +
					leadership_fast_track_principal_lvl5($user) +
					leadership_fast_track_principal_lvl6($user) +
					leadership_fast_track_principal_lvl7($user) +
					leadership_fast_track_principal_lvl8($user));

				break;
			case 9:
				$total = (
					leadership_fast_track_principal_lvl1($user) +
					leadership_fast_track_principal_lvl2($user) +
					leadership_fast_track_principal_lvl3($user) +
					leadership_fast_track_principal_lvl4($user) +
					leadership_fast_track_principal_lvl5($user) +
					leadership_fast_track_principal_lvl6($user) +
					leadership_fast_track_principal_lvl7($user) +
					leadership_fast_track_principal_lvl8($user) +
					leadership_fast_track_principal_lvl9($user));

				break;
			case 10:
				$total = (
					leadership_fast_track_principal_lvl1($user) +
					leadership_fast_track_principal_lvl2($user) +
					leadership_fast_track_principal_lvl3($user) +
					leadership_fast_track_principal_lvl4($user) +
					leadership_fast_track_principal_lvl5($user) +
					leadership_fast_track_principal_lvl6($user) +
					leadership_fast_track_principal_lvl7($user) +
					leadership_fast_track_principal_lvl8($user) +
					leadership_fast_track_principal_lvl9($user) +
					leadership_fast_track_principal_lvl10($user));

				break;
		}
	}

	return $total;
}

/**
 * @param $level
 * @param $users
 *
 * @return float|int
 *
 * @since version
 */
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
 * @param $user
 *
 * @return float|int
 *
 * @since version
 */
function leadership_fast_track_principal_lvl1($user)
{
	return bonus_leadership_fast_track_principal(1, level_directs([$user]));
}

function leadership_fast_track_principal_lvl2($user)
{
	return bonus_leadership_fast_track_principal(2, level_directs(level_directs([$user])));
}

/**
 * @param $user
 *
 * @return float|int
 *
 * @since version
 */
function leadership_fast_track_principal_lvl3($user)
{
	$level_1 = level_directs([$user]);
	$level_2 = level_directs($level_1);

	return bonus_leadership_fast_track_principal(3, level_directs($level_2));
}

/**
 * @param $user
 *
 * @return float|int
 *
 * @since version
 */
function leadership_fast_track_principal_lvl4($user)
{
	$level_1 = level_directs([$user]);
	$level_2 = level_directs($level_1);
	$level_3 = level_directs($level_2);

	return bonus_leadership_fast_track_principal(4, level_directs($level_3));
}

/**
 * @param $user
 *
 * @return float|int
 *
 * @since version
 */
function leadership_fast_track_principal_lvl5($user)
{
	$level_1 = level_directs([$user]);
	$level_2 = level_directs($level_1);
	$level_3 = level_directs($level_2);
	$level_4 = level_directs($level_3);

	return bonus_leadership_fast_track_principal(5, level_directs($level_4));
}

/**
 * @param $user
 *
 * @return float|int
 *
 * @since version
 */
function leadership_fast_track_principal_lvl6($user)
{
	$level_1 = level_directs([$user]);
	$level_2 = level_directs($level_1);
	$level_3 = level_directs($level_2);
	$level_4 = level_directs($level_3);
	$level_5 = level_directs($level_4);

	return bonus_leadership_fast_track_principal(6, level_directs($level_5));
}

/**
 * @param $user
 *
 * @return float|int
 *
 * @since version
 */
function leadership_fast_track_principal_lvl7($user)
{
	$level_1 = level_directs([$user]);
	$level_2 = level_directs($level_1);
	$level_3 = level_directs($level_2);
	$level_4 = level_directs($level_3);
	$level_5 = level_directs($level_4);
	$level_6 = level_directs($level_5);

	return bonus_leadership_fast_track_principal(7, level_directs($level_6));
}

/**
 * @param $user
 *
 * @return float|int
 *
 * @since version
 */
function leadership_fast_track_principal_lvl8($user)
{
	return bonus_leadership_fast_track_principal(8, get_level7($user));
}

/**
 * @param $user
 *
 * @return float|int
 *
 * @since version
 */
function leadership_fast_track_principal_lvl9($user)
{
	$level_8 = get_level7($user);

	return bonus_leadership_fast_track_principal(9, level_directs($level_8));
}

/**
 * @param $user
 *
 * @return float|int
 *
 * @since version
 */
function leadership_fast_track_principal_lvl10($user)
{
	$level_8 = get_level7($user);
	$level_9 = level_directs($level_8);

	return bonus_leadership_fast_track_principal(10, level_directs($level_9));
}

/**
 * @param $user
 *
 * @return int
 *
 * @since version
 */
function members_lvl1($user): int
{
	return count(level_directs([$user]));
}

/**
 * @param $user
 *
 * @return int
 *
 * @since version
 */
function members_lvl2($user): int
{
	return count(level_directs(level_directs([$user])));
}

/**
 * @param $user
 *
 * @return int
 *
 * @since version
 */
function members_lvl3($user): int
{
	$level_1 = level_directs([$user]);
	$level_2 = level_directs($level_1);

	return count(level_directs($level_2));
}

/**
 * @param $user
 *
 * @return int
 *
 * @since version
 */
function members_lvl4($user): int
{
	$level_1 = level_directs([$user]);
	$level_2 = level_directs($level_1);
	$level_3 = level_directs($level_2);

	return count(level_directs($level_3));
}

/**
 * @param $user
 *
 * @return int
 *
 * @since version
 */
function members_lvl5($user): int
{
	$level_1 = level_directs([$user]);
	$level_2 = level_directs($level_1);
	$level_3 = level_directs($level_2);
	$level_4 = level_directs($level_3);

	return count(level_directs($level_4));
}

/**
 * @param $user
 *
 * @return int
 *
 * @since version
 */
function members_lvl6($user): int
{
	$level_1 = level_directs([$user]);
	$level_2 = level_directs($level_1);
	$level_3 = level_directs($level_2);
	$level_4 = level_directs($level_3);
	$level_5 = level_directs($level_4);

	return count(level_directs($level_5));
}

/**
 * @param $user
 *
 * @return int
 *
 * @since version
 */
function members_lvl7($user): int
{
	$level_1 = level_directs([$user]);
	$level_2 = level_directs($level_1);
	$level_3 = level_directs($level_2);
	$level_4 = level_directs($level_3);
	$level_5 = level_directs($level_4);
	$level_6 = level_directs($level_5);

	return count(level_directs($level_6));
}

/**
 * @param $user
 *
 * @return int
 *
 * @since version
 */
function members_lvl8($user): int
{
	return count(get_level7($user));
}

/**
 * @param $user
 *
 * @return int
 *
 * @since version
 */
function members_lvl9($user): int
{
	$level_8 = get_level7($user);

	return count(level_directs($level_8));
}

/**
 * @param $user
 *
 * @return int
 *
 * @since version
 */
function members_lvl10($user): int
{
	$level_8 = get_level7($user);
	$level_9 = level_directs($level_8);

	return count(level_directs($level_9));
}

/**
 * @param $user
 *
 * @return int
 *
 * @since version
 */
function members_total($user): int
{
	return (members_lvl1($user) +
		members_lvl2($user) +
		members_lvl3($user) +
		members_lvl4($user) +
		members_lvl5($user) +
		members_lvl6($user) +
		members_lvl7($user) +
		members_lvl8($user) +
		members_lvl9($user) +
		members_lvl10($user));
}

/**
 * @param $user
 *
 * @return array
 *
 * @since version
 */
function get_level7($user): array
{
	$level_1 = level_directs([$user]);
	$level_2 = level_directs($level_1);
	$level_3 = level_directs($level_2);
	$level_4 = level_directs($level_3);
	$level_5 = level_directs($level_4);
	$level_6 = level_directs($level_5);
	$level_7 = level_directs($level_6);

	return level_directs($level_7);
}

/**
 * @param   array  $lvl_1
 *
 * @return array
 *
 * @since version
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
 * @param $sponsor_id
 *
 * @return array
 *
 * @since version
 */
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

/**
 * @param $user_id
 *
 * @return mixed|null
 *
 * @since version
 */
function user_leadership_fast_track_principal($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_leadership_fast_track_principal ' .
		'WHERE user_id = ' . $db->quote($user_id)
	)->loadObject();
}

/**
 * @param $lftp_add
 * @param $lftp
 * @param $user_id
 *
 *
 * @since version
 */
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

/**
 * @param $bonus
 * @param $user_id
 *
 *
 * @since version
 */
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