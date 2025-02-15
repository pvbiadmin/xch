<?php

namespace BPL\Ajax\Mods\Fast_Track;

use BPL\Lib\Local\Database\Db_Connect as DB;

use DateTime;
use DateTimeZone;
use Exception;

use function BPL\Mods\Local\Database\Query\fetch;
use function BPL\Mods\Local\Database\Query\fetch_all;
use function BPL\Mods\Local\Database\Query\crud;

use function BPL\Mods\Local\Url_SEF\sef;
use function BPL\Mods\Local\Url_SEF\qs;

use function BPL\Mods\Local\Helpers\settings;
use function BPL\Mods\Local\Helpers\user;
use function BPL\Mods\Local\Helpers\echo_json;
use function BPL\Mods\Local\Helpers\validate_fast_track;
use function BPL\Mods\Local\Helpers\directs_valid as user_directs;

$input = filter_input(INPUT_POST, 'input', FILTER_VALIDATE_FLOAT);
$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

main($input, $user_id);

/**
 * @param $input
 * @param $user_id
 *
 *
 * @since version
 */
function main($input, $user_id)
{
    header('Content-Type: application/json');

    $dbh = DB::connect();

    $return = $_POST;

    $settings_plans = settings('plans');

    validate_fast_track($input, $user_id, $return);

    try {
        $dbh->beginTransaction();

        // Insert the fast track record and get the insert ID
        $fast_track_id = insert_fast_track($dbh, $user_id, $input);

        // Update the user's fast track details
        $update_user = update_user_fast_track($user_id, $input);

        if ($update_user) {
            $sponsor_id = user($user_id)->sponsor_id;

            // Process referral and leadership logic
            referral_fast_track_principal($sponsor_id, $input);
            leadership_fast_track_principal($sponsor_id, $fast_track_id, $input);
        }

        $dbh->commit();
    } catch (Exception $e) {
        try {
            $dbh->rollback();
        } catch (Exception $e2) {
        }
    }

    $user_latest = user($user_id);

    $return['principal'] = $user_latest->fast_track_principal;
    $return['balance'] = $user_latest->payout_transfer;

    $fast_tracks = user_fast_track($user_id);

    $value_last = 0;

    foreach ($fast_tracks as $fast_track) {
        $value_last += $fast_track->value_last;
    }

    $return['interest'] = $value_last + $user_latest->fast_track_interest;
    $return['input'] = $input;

    $return['success_fast_track'] = $settings_plans->fast_track_name . ' successful! (fast_track_id: ' . $fast_track_id . ')';

    try {
        $return['fast_track_json'] = json_encode($return, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    } catch (Exception $e) {

    }

    echo_json($return);
}

function referral_fast_track_principal($user_id, $input)
{
    $sp = settings('plans');

    if ($sp->direct_referral_fast_track_principal) {
        $srftp = settings('referral_fast_track_principal');

        $sponsor = user($user_id);

        $account_type = $sponsor->account_type;

        $percent = $srftp->{$account_type . '_referral_fast_track_principal'};

        $bonus = $input * ($percent / 100);

        $income_referral_ftp = $sponsor->income_referral_fast_track_principal;
        $balance = $sponsor->payout_transfer;

        return crud(
            'UPDATE network_users ' .
            'SET income_referral_fast_track_principal = :income_referral_ftp, ' .
            'payout_transfer = :payout_transfer ' .
            'WHERE id = :id',
            [
                'income_referral_ftp' => ($income_referral_ftp + $bonus),
                'payout_transfer' => ($balance + $bonus),
                'id' => $user_id
            ]
        );
    }
}

function leadership_fast_track_principal($user_id, $fast_track_id, $input)
{
    $sp = settings('plans');
    $slftp = settings('leadership_fast_track_principal');

    if (!$sp->leadership_fast_track_principal) {
        return false;
    }

    // Current sponsor ID to track upline
    $current_sponsor_id = $user_id;

    // Process each level
    for ($level = 1; $level <= 10; $level++) {
        // Get sponsor at current level
        $sponsor = user($current_sponsor_id);

        // Break if no sponsor found
        if (!$sponsor) {
            break;
        }

        $account_type = $sponsor->account_type;
        $level_type = $slftp->{$account_type . '_leadership_fast_track_principal_level'};
        $required_directs = $slftp->{$account_type . '_leadership_fast_track_principal_sponsored'};

        $sponsor_directs = user_directs($current_sponsor_id);

        if ($level <= $level_type && $sponsor_directs >= $required_directs) {
            $share = $slftp->{$account_type . '_leadership_fast_track_principal_share_' . $level};
            $share_cut = $slftp->{$account_type . '_leadership_fast_track_principal_share_cut_' . $level};

            $percent = ($share / 100) * ($share_cut / 100);
            $bonus = $input * $percent;

            update_user_bonus_lftp($current_sponsor_id, $fast_track_id, $bonus);
        }

        // Move up to next sponsor
        $current_sponsor_id = $sponsor->sponsor_id;
    }
}

function insert_fast_track($dbh, $user_id, $input)
{
    $settings_investment = settings('investment');
    $account_type = user($user_id)->account_type;
    $fast_track_processing = $settings_investment->{$account_type . '_fast_track_processing'};

    $time = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $now = $time->format('U'); // seconds since Unix epoch

    // Execute the insert query using the same PDO instance
    $stmt = $dbh->prepare(
        'INSERT INTO network_fast_track' .
        ' (user_id, time_last, value_last, day, principal, date_entry, processing, date_last_cron)' .
        ' VALUES (:user_id, :time_last, :value_last, :day, :principal, :date_entry, :processing, :date_last_cron)'
    );

    $stmt->execute([
        'user_id' => $user_id,
        'time_last' => 0,
        'value_last' => 0,
        'day' => 0,
        'principal' => $input,
        'date_entry' => $now,
        'processing' => $fast_track_processing,
        'date_last_cron' => $now
    ]);

    // Get the last inserted ID using the same DB instance
    $insert_id = $dbh->lastInsertId();

    // Log admin income (if needed)
    log_income_admin($insert_id, $input);

    return $insert_id;
}

function log_income_admin($insert_id, $input)
{
    $income_admin = income_admin();

    $income_total = $income_admin->income_total + $input;

    crud(
        'INSERT INTO network_income' .
        ' (transaction_id' .
        ', amount' .
        ', income_total' .
        ', income_date)' .
        ' VALUES' .
        ' (:transaction_id' .
        ', :amount' .
        ', :income_total' .
        ', :income_date)',
        [
            'transaction_id' => $insert_id,
            'amount' => $input,
            'income_total' => $income_total,
            'income_date' => time()
        ]
    );
}

function income_admin()
{
    return fetch(
        'SELECT * ' .
        'FROM network_income ' .
        'ORDER BY income_id DESC'
    );
}


function update_user_fast_track($user_id, $input)
{
    $user = user($user_id);

    $balance = $user->payout_transfer;
    $fast_track_principal = $user->fast_track_principal;

    return crud(
        'UPDATE network_users ' .
        'SET fast_track_principal = :principal, ' .
        'payout_transfer = :payout_transfer ' .
        'WHERE id = :id',
        [
            'principal' => ($fast_track_principal + $input),
            'payout_transfer' => ($balance - $input)/*'points' => ($balance - $input)*/ ,
            'id' => $user_id
        ]
    );
}

function arr_lftpb_list($user)
{
    $bonus_lftpb_list = empty($user->bonus_lftpb_list) ? '{}' : $user->bonus_lftpb_list;

    return json_decode($bonus_lftpb_list, true);
}

function update_user_bonus_lftp($current_sponsor_id, $fast_track_id, $bonus)
{
    $sponsor = user($current_sponsor_id);

    // Get current values
    $bonus_lftp = $sponsor->bonus_leadership_fast_track_principal;
    $bonus_lftp_bal = $sponsor->bonus_leadership_fast_track_principal_balance;
    // $balance = $sponsor->payout_transfer;
    $arr_bonus_lftp_list = arr_bonus_lftp_list($sponsor);

    $arr_bonus_lftp_list[] = ['fast_track_id' => $fast_track_id, 'reaped' => 0];

    $bonus_lftp_list = json_encode($arr_bonus_lftp_list);

    $update_user_bonus_lftp = crud(
        'UPDATE network_users' .
        ' SET bonus_leadership_fast_track_principal = :bonus_lftp' .
        ', bonus_leadership_fast_track_principal_balance = :bonus_lftp_bal' .
        // ', payout_transfer = :payout_transfer' .
        ', bonus_lftp_list  = :bonus_lftp_list' .
        ' WHERE id = :id',
        [
            'bonus_lftp' => $bonus_lftp + $bonus,
            'bonus_lftp_bal' => $bonus_lftp_bal + $bonus,
            // 'payout_transfer' => $balance + $bonus,
            'bonus_lftp_list' => $bonus_lftp_list,
            'id' => $current_sponsor_id
        ]
    );

    if ($update_user_bonus_lftp) {
        log_activity_lftp($current_sponsor_id, $bonus);
    }
}

function arr_bonus_lftp_list($user)
{
    $bonus_lftp_list = $user->bonus_lftp_list;

    $bonus_lftp_list = empty($bonus_lftp_list) ? '{}' : $bonus_lftp_list;

    return json_decode($bonus_lftp_list, true);
}

function log_activity_lftp($current_sponsor_id, $bonus)
{
    $sp = settings('plans');
    $sa = settings('ancillaries');

    $sponsor = user($current_sponsor_id);

    // log activity
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
            'user_id' => $current_sponsor_id,
            'sponsor_id' => $sponsor->sponsor_id,
            'activity' => '<b>' . $sp->leadership_fast_track_principal_name . ' Bonus: </b> <a href="' .
                sef(44) . qs() . 'uid=' . $current_sponsor_id . '">' . $sponsor->username .
                '</a> has earned ' . number_format($bonus, 2) . ' ' . $sa->currency,
            'activity_date' => time()
        ]
    );
}

/**
 * @param $user_id
 *
 * @return array|false
 *
 * @since version
 */
function user_fast_track($user_id)
{
    return fetch_all(
        'SELECT * ' .
        'FROM network_fast_track ' .
        'WHERE user_id = :user_id ' .
        'ORDER BY id DESC',
        ['user_id' => $user_id]
    );
}