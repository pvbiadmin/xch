<?php

namespace BPL\Ajax\Mods\Leadership_Fast_Track_Principal;

use function BPL\Mods\Local\Database\Query\fetch;
use function BPL\Mods\Local\Database\Query\fetch_all;
use function BPL\Mods\Local\Database\Query\crud;

use function BPL\Mods\Local\Helpers\settings;
use function BPL\Mods\Local\Helpers\user;

function main()
{
    if (!empty($users)) {
        foreach ($users as $user) {
            process_user_leadership_fast_track_principal($user);
        }
    }
}

function process_user_leadership_fast_track_principal($user)
{
    $slftp = settings('leadership_fast_track_principal');

    $user_id = $user->user_id;
    $account_type = $user->account_type;

    // Count the number of direct referrals
    $count_directs = count(user_directs($user_id));

    // Fetch leadership passive settings for the user's account type
    $type_level = $slftp->{$account_type . '_leadership_fast_track_principal_level'};
    $required_directs = $slftp->{$account_type . '_leadership_fast_track_principal_sponsored'};
    $max_daily_income = $slftp->{$account_type . '_leadership_fast_track_principal_max_daily_income'};
    $income_max = $slftp->{$account_type . '_leadership_fast_track_principal_maximum'};

    $user_bonus_lftp = $user->u_bonus_leadership_fast_track_principal;
    $income_today = $user->income_today;

    // Check if the user qualifies for leadership fixed daily bonus
    if ($type_level > 0 && $count_directs >= $required_directs) {
        // Calculate total leadership fixed daily bonus
        $lftp_total = bonus_total_leadership_fast_track_principal($user_id)['bonus'];
        $lftp_add = $lftp_total - $user->bonus_leadership_fast_track_principal_last;

        if ($lftp_add > 0) {
            // Apply daily and maximum income limits
            if ($max_daily_income > 0 && ($income_today + $lftp_add) >= $max_daily_income) {
                $lftp_add = non_zero($max_daily_income - $income_today);
            }

            if ($income_max > 0 && ($user_bonus_lftp + $lftp_add) >= $income_max) {
                $lftp_add = non_zero($income_max - $user_bonus_lftp);
            }

            update_lftp($user, $lftp_add, $lftp_total);
            update_user($user, $lftp_add);
        }
    }
}

function bonus_total_leadership_fast_track_principal($user_id): array
{
    $account_type = user($user_id)->account_type;
    $slftp = settings('leadership_fast_track_principal');

    $total_members = 0;
    $total_bonus = 0;

    // Start with the user's direct referrals
    $current_level = [$user_id];

    for ($level = 1; $level <= $slftp->{$account_type . '_leadership_fast_track_principal_level'}; $level++) {
        // Get users and their passive income for the current level
        [$current_level, $passive] = level_leadership_fast_track_principal($current_level);

        // Add members and calculate bonus for the current level
        $total_members += count($current_level);
        $total_bonus += get_leadership_fast_track_principal(
            $passive,
            $slftp->{$account_type . '_leadership_fast_track_principal_share_' . $level},
            $slftp->{$account_type . '_leadership_fast_track_principal_share_cut_' . $level}
        );
    }

    return [
        'member' => $total_members,
        'bonus' => $total_bonus
    ];
}

function get_leadership_fast_track_principal($indirect, $share, $share_cut)
{
    $leadership = 0;

    if ($indirect) {
        foreach ($indirect as $fast_track_principal) {
            $leadership += $fast_track_principal * $share * $share_cut / 100 / 100;
        }
    }

    return $leadership;
}

function level_leadership_fast_track_principal(array $head = []): array
{
    $group = [];
    $passive = [];

    foreach ($head as $sponsor) {
        $directs = user_directs($sponsor);

        if (!empty($directs)) {
            foreach ($directs as $direct) {
                $group[] = $direct->id;

                if (empty(user_cd($direct->id))) {
                    $passive[] = $direct->fast_track_principal;
                }
            }
        }
    }

    return [$group, $passive];
}

function leadership_fast_track_principal_users()
{
    return fetch_all(
        'SELECT account_type, ' .
        'user_id, ' .
        'p.bonus_leadership_fast_track_principal p_bonus_leadership_fast_track_principal, ' .
        'flushout_local, ' .
        'flushout_global, ' .
        'income_today, ' .
        'bonus_leadership_fast_track_principal_now, ' .
        'u.bonus_leadership_fast_track_principal u_bonus_leadership_fast_track_principal, ' .
        'bonus_leadership_fast_track_principal_balance, ' .
        'income_cycle_global, ' .
        'income_flushout, ' .
        'status_global, ' .
        'u.id u_id, ' .
        'bonus_leadership_fast_track_principal_last ' .
        'FROM network_users u ' .
        'INNER JOIN network_leadership_fast_track_principal p ' .
        'ON u.id = p.user_id ' .
        'WHERE u.account_type <> :account_type',
        ['account_type' => 'starter']
    );
}

function user_directs($sponsor_id)
{
    return fetch_all(
        'SELECT * ' .
        'FROM network_users ' .
        'WHERE account_type <> :account_type ' .
        'AND sponsor_id = :sponsor_id',
        [
            'account_type' => 'starter',
            'sponsor_id' => $sponsor_id
        ]
    );
}

function update_leadership_fast_track_principal($user, $bonus, $leadership_fast_track_principal)
{
    crud(
        'UPDATE network_leadership_fast_track_principal ' .
        'SET bonus_leadership_fast_track_principal = :fast_track_principal, ' .
        'bonus_leadership_fast_track_principal_now = :fast_track_principal_now, ' .
        'income_today = :income_today, ' .
        'bonus_leadership_fast_track_principal_last = :fast_track_principal_last ' .
        'WHERE user_id = :id',
        [
            'fast_track_principal' => ($user->p_bonus_leadership_fast_track_principal + $bonus),
            'fast_track_principal_now' => ($user->bonus_leadership_fast_track_principal_now + $bonus),
            'income_today' => ($user->income_today + $bonus),
            'fast_track_principal_last' => $leadership_fast_track_principal,
            'id' => $user->user_id
        ]
    );
}

function update_user($user, $bonus)
{
    crud(
        'UPDATE network_users ' .
        'SET bonus_leadership_fast_track_principal = :fast_track_principal, ' .
        'bonus_leadership_fast_track_principal_balance = :balance ' .
        'WHERE id = :id',
        [
            'fast_track_principal' => ($user->u_bonus_leadership_fast_track_principal + $bonus),
            'balance' => ($user->bonus_leadership_fast_track_principal_balance + $bonus),
            'id' => $user->u_id
        ]
    );
}

function user_cd($user_id)
{
    return fetch(
        'SELECT * ' .
        'FROM network_commission_deduct ' .
        'WHERE id = :id',
        ['id' => $user_id]
    );
}

function non_zero($value)
{
    return $value < 0 ? 0 : $value;
}