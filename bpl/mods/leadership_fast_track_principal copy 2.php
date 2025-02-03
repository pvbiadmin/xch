<?php

namespace BPL\Ajax\Mods\Leadership_Fast_Track_Principal;

use function BPL\Mods\Local\Database\Query\fetch;
use function BPL\Mods\Local\Database\Query\crud;

use function BPL\Mods\Local\Url_SEF\sef;
use function BPL\Mods\Local\Url_SEF\qs;

use function BPL\Mods\Local\Helpers\settings;
use function BPL\Mods\Local\Helpers\user;
use function BPL\Mods\Local\Helpers\users;

use function BPL\Mods\Local\Helpers\directs_valid;

function main()
{
    $slftp = settings('leadership_fast_track_principal');

    $users = users();

    foreach ($users as $user) {
        $account_type = $user->account_type;
        $user_id = $user->id;

        $user_bonus_lftp = $user->bonus_leadership_fast_track_principal;

        $income_limit_cycle = $slftp->{$account_type . '_leadership_fast_track_principal_max_daily_income'};
        $income_max = $slftp->{$account_type . '_leadership_fast_track_principal_maximum'};

        $user_lftp = user_lftp($user_id);

        $income_today = $user_lftp->income_today;

        if (has_lftp($user)) {
            $lftp_bonus_total = total($user_id)['bonus'];
            $lftp_bonus_add = $lftp_bonus_total - $user_lftp->bonus_leadership_fast_track_principal_last;

            if ($lftp_bonus_add > 0) {
                if ($income_limit_cycle > 0 && ($income_today + $lftp_bonus_add) >= $income_limit_cycle) {
                    $lftp_bonus_add = non_zero($income_limit_cycle - $income_today);
                }

                if ($income_max > 0 && ($user_bonus_lftp + $lftp_bonus_add) >= $income_max) {
                    $lftp_bonus_add = non_zero($income_max - $user_bonus_lftp);
                }
            }
        }
    }
}

function update_bonus_lftp($lftp_bonus_total, $lftp_bonus_add, $user)
{
    $user_id = $user->id;
    $username = $user->username;
    $sponsor_id = $user->sponsor_id;

    $user_bonus_lftp = $user->bonus_leadership_fast_track_principal;
    $user_efund = $user->payout_transfer;

    crud(
        'UPDATE network_users ' .
        'SET bonus_leadership_fast_track_principal = :bonus_lftp, ' .
        'payout_transfer = :payout_transfer ' .
        'WHERE id = :id',
        [
            'bonus_lftp' => ($user_bonus_lftp + $lftp_bonus_add),
            'payout_transfer' => ($user_efund + $lftp_bonus_add),
            'id' => $user_id
        ]
    );

    update_network_lftp($lftp_bonus_total, $lftp_bonus_add, $user_id);
    log_activity($lftp_bonus_add, $user_id, $sponsor_id, $username);
}

function update_network_lftp($lftp_bonus_total, $lftp_bonus_add, $user_id)
{
    $user_lftp = user_lftp($user_id);
    $user_bonus_lftp = $user_lftp->bonus_leadership_fast_track_principal;
    $user_bonus_lftp_now = $user_lftp->bonus_leadership_fast_track_principal_now;
    $user_bonus_lftp_last = $user_lftp->bonus_leadership_fast_track_principal_last;
    $user_lftp_income_today = $user_lftp->income_today;

    crud(
        'UPDATE network_leadership_fast_track_principal' .
        ' SET bonus_leadership_fast_track_principal = :bonus_lftp' .
        ', bonus_leadership_fast_track_principal_now = :bonus_lftp_now' .
        ', bonus_leadership_fast_track_principal_last = :bonus_lftp_last' .
        ', income_today = :income_today' .
        ' WHERE user_id = :user_id',
        [
            'bonus_lftp' => ($user_bonus_lftp + $lftp_bonus_add),
            'bonus_lftp_now' => ($user_bonus_lftp_now + $lftp_bonus_add),
            'bonus_lftp_last' => ($user_bonus_lftp_last + $lftp_bonus_total),
            'income_today' => ($user_lftp_income_today + $lftp_bonus_add),
            'user_id' => $user_id
        ]
    );
}

function log_activity($lftp_bonus_add, $user_id, $sponsor_id, $username)
{
    $sp = settings('plans');
    $sa = settings('ancillaries');

    $activity = '<b>' . $sp->leadership_fast_track_principal_name . ' Bonus: </b> <a href="' .
        sef(44) . qs() . 'uid=' . $user_id . '">' . $username . '</a> has earned ' .
        number_format($lftp_bonus_add, 2) . ' ' . $sa->currency;

    crud(
        'INSERT ' .
        'INTO network_activity' .
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
            'user_id' => $user_id,
            'sponsor_id' => $sponsor_id,
            'activity' => $activity,
            'activity_date' => time()
        ]
    );
}

function has_lftp($user)
{
    $slftp = settings('leadership_fast_track_principal');

    $user_id = $user->id;
    $account_type = $user->account_type;

    $sponsored = directs_valid($user_id);

    $type_level = $slftp->{$account_type . '_leadership_fast_track_principal_level'};
    $type_directs = $slftp->{$account_type . '_leadership_fast_track_principal_sponsored'};

    return $type_level && count($sponsored) >= $type_directs;
}

function total($user_id): array
{
    $slftp = settings('leadership_fast_track_principal');

    $head_user = user($user_id);

    $head_account_type = $head_user->account_type;

    $type_level = $slftp->{$head_account_type . '_leadership_fast_track_principal_level'};

    $member = 0;
    $bonus = 0;

    $ctr = 1;

    $results = nested($type_level, $user_id);

    foreach ($results as $result) {
        $member += count($result[0]);
        $bonus += get($result[0], $ctr);

        $ctr++;
    }

    return [
        'member' => $member,
        'bonus' => $bonus
    ];
}

function nested($level, $user_id): array
{
    $result[] = level([$user_id]);

    for ($i_i = 2; $i_i <= $level; $i_i++) {
        $last = array_reverse($result)[0];

        $result[] = level($last[0]);
    }

    return $result;
}

function level(array $lvl_1 = []): array
{
    $lvl_2 = [];
    $type = [];

    if (!empty($lvl_1)) {
        foreach ($lvl_1 as $head) {
            $user_direct = directs_valid($head);

            if ($user_direct) {
                foreach ($user_direct as $body) {
                    $lvl_2[] = $body->id;
                    $type[] = $body->account_type;
                }
            }
        }
    }

    return [$lvl_2, $type];
}

function get($indirects, $level)
{
    $bonus_lftp = 0;

    $slftp = settings('leadership_fast_track_principal');

    if (count($indirects) > 0) {
        foreach ($indirects as $user_id) {
            $user = user($user_id);

            $account_type = $user->account_type;
            $fast_track_principal = $user->fast_track_principal;

            $lftp_share = $slftp->{$account_type . '_leadership_fast_track_principal_share_' . $level};
            $lftp_share_cut = $slftp->{$account_type . '_leadership_fast_track_principal_share_cut_' . $level};

            $factor = ($lftp_share / 100) * ($lftp_share_cut / 100);

            $bonus_lftp += $fast_track_principal * $factor;
        }
    }

    return $bonus_lftp;
}

function user_lftp($user_id)
{
    return fetch(
        'SELECT * ' .
        'FROM network_leadership_fast_track_principal ' .
        'WHERE user_id = :user_id',
        ['user_id' => $user_id]
    );
}

function non_zero($value)
{
    return $value < 0 ? 0 : $value;
}

