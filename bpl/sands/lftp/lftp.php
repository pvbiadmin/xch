<?php

require_once '../../lib/Db_Connect.php';
require_once '../../mods/url_sef_local.php';
require_once '../../mods/helpers_local.php';

use function BPL\Mods\Local\Database\Query\fetch;
use function BPL\Mods\Local\Database\Query\crud;

use function BPL\Mods\Local\Url_SEF\sef;
use function BPL\Mods\Local\Url_SEF\qs;

use function BPL\Mods\Local\Helpers\settings;
use function BPL\Mods\Local\Helpers\user;
use function BPL\Mods\Local\Helpers\users;
use function BPL\Mods\Local\Helpers\directs_valid as user_directs;

function leadership_fast_track_principal()
{
    foreach (users() as $user) {
        process_user_lftp($user);
    }
}

function process_user_lftp($user)
{
    $slftp = settings('leadership_fast_track_principal');

    $account_type = $user->account_type;
    $count_directs = count(user_directs($user->id));

    $type_level = $slftp->{$account_type . '_leadership_fast_track_principal_level'};
    $required_directs = $slftp->{$account_type . '_leadership_fast_track_principalsponsored'};
    $max_daily_income = $slftp->{$account_type . '_leadership_fast_track_principal_max_daily_income'};
    $income_max = $slftp->{$account_type . '_leadership_fast_track_principal_maximum'};

    $user_bonus_lftp = $user->bonus_leadership_fast_track_principal;
    $ulftp = user_lftp($user->id);
    $income_today = $ulftp->income_today;

    if ($type_level > 0 && $count_directs >= $required_directs) {
        $lftp_total = lftp_total($user, $type_level);
        $lftp_add = $lftp_total - $ulftp->bonus_leadership_fast_track_principal_last;

        if ($lftp_add > 0) {
            if ($max_daily_income > 0 && ($income_today + $lftp_add) >= $max_daily_income) {
                $lftp_add = non_zero($max_daily_income - $income_today);
            }

            if ($income_max > 0 && ($user_bonus_lftp + $lftp_add) >= $income_max) {
                $lftp_add = non_zero($income_max - $user_bonus_lftp);
            }

            update_lftp($lftp_add, $lftp_total, $user->id);
            update_user_lftp($lftp_add, $user->id);
            log_activity($user, $lftp_total);
        }
    }
}

function lftp_total($user, $type_level)
{
    $total = 0;

    // Calculate the bonus for each level up to the user's level
    for ($level = 1; $level <= $type_level; $level++) {
        $total += calculate_level_bonus($user, $level);
    }

    return $total;
}

function calculate_level_bonus($user, $level)
{
    $users = get_level_users($user, $level);
    return bonus_lftp($level, $users);
}

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

function get_level_users($user, $level)
{
    $users = [$user];

    // Get direct referrals for each level
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

function update_lftp($lftp_add, $lftp, $user_id)
{
    $ulftp = user_lftp($user_id);

    $bonus_lftp = $ulftp->bonus_leadership_fast_track_principal;
    $bonus_lftp_now = $ulftp->bonus_leadership_fast_track_principal_now;
    $income_today = $ulftp->income_today;

    crud(
        'UPDATE network_leadership_fast_track_principal' .
        ' SET bonus_leadership_fast_track_principal = :bonus_lftp' .
        ', bonus_leadership_fast_track_principal_now = :bonus_lftp_now' .
        ', bonus_leadership_fast_track_principal_last = :bonus_lftp_last' .
        ', income_today = :income_today' .
        ' WHERE user_id = :user_id',
        [
            'bonus_lftp' => ($bonus_lftp + $lftp_add),
            'bonus_lftp_now' => ($bonus_lftp_now + $lftp_add),
            'bonus_lftp_last' => $lftp,
            'income_today' => ($income_today + $lftp_add),
            'user_id' => $user_id
        ]
    );
}

function log_activity($user, $bonus)
{
    crud(
        'INSERT INTO network_activity' .
        ' (user_id' .
        ', sponsor_id' .
        ', activity' .
        ', activity_date)' .
        ' VALUES' .
        ' (:user_id' .
        ', :sponsor_id' .
        ', :activity' .
        ', :activity_date)',
        [
            'user_id' => $user->id,
            'sponsor_id' => $user->sponsor_id,
            'activity' => '<b>' . settings('plans')->leadership_fast_track_principal_name .
                ' Bonus: </b> <a href="' . sef(44) . qs() . 'uid=' . $user->id . '">' .
                $user->username . '</a> has earned ' . number_format($bonus, 2) .
                ' ' . settings('ancillaries')->currency,
            'activity_date' => time()
        ]
    );
}

function update_user_lftp($bonus, $user_id)
{
    $user = user($user_id);

    $bonus_lftp = $user->bonus_leadership_fast_track_principal;
    $balance = $user->balance;
    $payout_transfer = $user->payout_transfer;

    $values = [
        'bonus_lftp' => $bonus_lftp + $bonus,
        'user_id' => $user_id
    ];

    // Check withdrawal mode and update balance or payout transfer accordingly
    if (settings('ancillaries')->withdrawal_mode === 'standard') {
        $field_efund = 'balance = :balance';
        $values['balance'] = $balance + $bonus;
    } else {
        $field_efund = 'payout_transfer = :payout_transfer';
        $values['payout_transfer'] = $payout_transfer + $bonus;
    }

    crud(
        'UPDATE network_users' .
        ' SET bonus_leadership_fast_track_principal = :bonus_lftp' .
        $field_efund .
        ' WHERE id = :user_id',
        $values
    );
}

function user_lftp($user_id)
{
    return fetch(
        'SELECT * ' .
        'FROM network_leadership_fast_track_principal ' .
        'WHERE user_id = :user_id',
        ['id' => $user_id]
    );
}

function non_zero($value)
{
    return $value < 0 ? 0 : $value;
}