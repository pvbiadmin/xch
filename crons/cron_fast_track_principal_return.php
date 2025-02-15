<?php

namespace Cron\Fast_Track_Principal_Return;

require_once 'Cron_Db_Info.php';
require_once 'Cron_Db_Connect.php';
require_once 'cron_query_local.php';

use Exception;

use Cron\Db\Connect\Cron_Db_Connect as DB_Cron;

use function Cron\Database\Query\fetch;
use function Cron\Database\Query\fetch_all;
use function Cron\Database\Query\crud;

main();

/**
 *
 *
 * @since version
 */
function main()
{
	$dbh = DB_Cron::connect();

	$users = users();

	// testing and debugging
	// $arr_users = [];

	foreach ($users as $user) {
		try {
			$dbh->beginTransaction();

			// testing and debugging
			// $claim = claim_lftpb($user);
			// if ($claim) {
			// 	$arr_users[] = $claim;
			// }

			return_principal_fast_track($user);

			$dbh->commit();
		} catch (Exception $e) {
			try {
				$dbh->rollback();
			} catch (Exception $e2) {
			}
		}
	}

	// testing and debugging
	// echo '<pre>';
	// print_r($arr_users);
	// exit;
}

/**
 * @param $type
 *
 * @return mixed
 *
 * @since version
 */
function settings($type)
{
	return fetch(
		'SELECT * ' .
		'FROM network_settings_' . $type
	);
}

/**
 *
 * @return array|false
 *
 * @since version
 */
function users()
{
	return fetch_all(
		'SELECT *' .
		' FROM network_users'
	);
}

function user($user_id)
{
	return fetch(
		'SELECT *' .
		' FROM network_users' .
		' WHERE id = :id',
		['id' => $user_id]
	);
}

/**
 * @param $user
 *
 * @since version
 */
function update_indirect($user)
{
	crud(
		'UPDATE network_indirect ' .
		'SET income_today = :income_today, ' .
		'date_last_flushout = :date_last_flushout' .
		' WHERE user_id = :user_id',
		[
			'user_id' => $user->user_id,
			'income_today' => 0,
			'date_last_flushout' => time()
		]
	);
}

function return_principal_fast_track($user)
{
	// testing and debugging
	// $collect_lftp_list = [];

	if ($user->bonus_lftp_list !== null) {
		// $slftp = settings('leadership_fast_track_principal');

		$arr_lftp_list = arr_lftp_list($user);

		$filtered_arr_lftp_list = array_filter($arr_lftp_list, function ($item) {
			return $item['fast_track_id'] != 0 && $item['reaped'] != 0;
		});

		// testing and debugging
		// $bonus_list = [];

		foreach ($filtered_arr_lftp_list as $lftp) {

			$fast_track_id = $lftp['fast_track_id'];

			$fast_track = fast_track_user($fast_track_id);

			$fast_track_days = $fast_track->day;

			if ($fast_track_days >= 100) {
				// $fast_track_user_id = $fast_track->user_id;

				// $fast_track_user = user($fast_track_user_id);

				// $account_type = $fast_track_user->account_type;

				// $level = get_downline_level($user->id, $fast_track_user_id);

				// $share = $slftp->{$account_type . '_leadership_fast_track_principal_share_' . $level};
				// $share_cut = $slftp->{$account_type . '_leadership_fast_track_principal_share_cut_' . $level};

				$fast_track_principal = $fast_track->principal;

				$bonus = $fast_track_principal/*  * ($share / 100) * ($share_cut / 100) */ ;

				// testing and debugging
				// $bonus_list[$fast_track_id] = [$bonus];

				update_user_efund($user, $fast_track_id, $bonus);
			}
		}

		// testing and debugging
		// $collect_lftp_list[$user->username] = $bonus_list;
	}

	// testing and debugging
	// return $collect_lftp_list;
}

function arr_lftp_list($lftp_user)
{
	$user_bonus_lftp_list = $lftp_user->bonus_lftp_list;

	$json_user_bonus_lftp_list = empty($user_bonus_lftp_list) ? '{}' : $user_bonus_lftp_list;

	return json_decode($json_user_bonus_lftp_list, true);
}

function fast_track_user($fast_track_id)
{
	return fetch(
		'SELECT *' .
		' FROM network_fast_track' .
		' WHERE fast_track_id = :fast_track_id',
		['fast_track_id' => $fast_track_id]
	);
}

function update_user_efund($user, $fast_track_id, $bonus)
{
	// Decode the bonus_lftp_list JSON array
	$bonus_lftp_list = arr_lftp_list($user);

	foreach ($bonus_lftp_list as $key => &$entry) {
		if ($entry['fast_track_id'] == $fast_track_id) {
			// Remove the entry with $entry['fast_track_id'] == $fast_track_id
			unset($bonus_lftp_list[$key]);
		}
	}

	// Re-index the array to remove any gaps left by unset()
	$bonus_lftp_list = array_values($bonus_lftp_list);

	// Encode the updated array back to JSON
	$updated_bonus_lftp_list = json_encode($bonus_lftp_list);

	// Calculate the new bonus balance and efund
	// $bonus_lftp_balance = $user->bonus_leadership_fast_track_principal_balance;
	$efund = $user->payout_transfer;

	// $bonus_lftp_balance_new = $bonus_lftp_balance - $bonus;
	$efund_new = $efund + $bonus;

	// Update the database with the new bonus balance, efund, and updated bonus_lftp_list
	crud(
		'UPDATE network_users' .
		' SET' .
		// ' bonus_leadership_fast_track_principal_balance = :bonus_lftp_balance' .
		' payout_transfer = :efund' .
		', bonus_lftp_list = :bonus_lftp_list' .
		' WHERE id = :id',
		[
			// 'bonus_lftp_balance' => $bonus_lftp_balance_new,
			'efund' => $efund_new,
			'bonus_lftp_list' => $updated_bonus_lftp_list,
			'id' => $user->id
		]
	);
}

function fast_tracks()
{
	return fetch(
		'SELECT *' .
		' FROM network_fast_track'
	);
}

/**
 * Get the level depth of a user in the downline of a sponsor.
 *
 * @param int $sponsor_id The sponsor's user ID.
 * @param int $id The user ID to search for in the downline.
 * @param int $max_levels The maximum depth to search (default: 10).
 *
 * @return int The level depth if found, otherwise 0.
 *
 * @since version
 */
function get_downline_level($sponsor_id, $id, $max_levels = 10)
{
	// Queue to store nodes for BFS traversal
	$queue = new \SplQueue();

	// Start with the sponsor_id and level 0
	$queue->enqueue(['user_id' => $sponsor_id, 'level' => 0]);

	// Perform BFS
	while (!$queue->isEmpty()) {
		$current = $queue->dequeue();
		$current_user_id = $current['user_id'];
		$current_level = $current['level'];

		// If the current user_id matches the target id, return the level
		if ($current_user_id == $id) {
			return $current_level;
		}

		// If the current level exceeds max_levels, skip fetching downlines
		if ($current_level >= $max_levels) {
			continue;
		}

		// Fetch all direct downlines of the current user
		$downlines = fetch_all(
			'SELECT id FROM network_users WHERE sponsor_id = :sponsor_id',
			['sponsor_id' => $current_user_id]
		);

		// Add all downlines to the queue with incremented level
		foreach ($downlines as $downline) {
			$queue->enqueue(['user_id' => $downline->id, 'level' => $current_level + 1]);
		}
	}

	// If the id is not found in the downline within max_levels, return 0
	return 0;
}