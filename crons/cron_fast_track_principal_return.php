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

	$fts = fast_tracks();

	foreach ($fts as $ft) {
		try {
			$dbh->beginTransaction();

			return_principal_ft($ft);

			$dbh->commit();
		} catch (Exception $e) {
			try {
				$dbh->rollback();
			} catch (Exception $e2) {
			}
		}
	}
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

function return_principal_ft($fast_track)
{
	if ($fast_track->day >= 100) {
		$user_id = $fast_track->user_id;
		$fast_track_id = $fast_track->fast_track_id;

		$user = user($user_id);

		$principal = $fast_track->principal;

		update_user_efund($user, $fast_track_id, $principal);
		delete_fast_track($fast_track_id);
	}
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

function update_user_efund($user, $fast_track_id, $principal)
{
	$bonus_lftp_list = arr_lftp_list($user);

	foreach ($bonus_lftp_list as $key => &$entry) {
		if ($entry['fast_track_id'] == $fast_track_id) {
			unset($bonus_lftp_list[$key]);
		}
	}

	$bonus_lftp_list = array_values($bonus_lftp_list);

	$updated_bonus_lftp_list = json_encode($bonus_lftp_list);

	$efund = $user->payout_transfer;
	$efund_new = $efund + $principal;

	crud(
		'UPDATE network_users' .
		' SET payout_transfer = :efund' .
		', bonus_lftp_list = :bonus_lftp_list' .
		' WHERE id = :id',
		[
			'efund' => $efund_new,
			'bonus_lftp_list' => $updated_bonus_lftp_list,
			'id' => $user->id
		]
	);
}

function delete_fast_track($fast_track_id)
{
	crud(
		'DELETE FROM network_fast_track' .
		' WHERE fast_track_id = :fast_track_id',
		[
			'fast_track_id' => $fast_track_id
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