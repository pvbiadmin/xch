<?php

namespace Cron\Lftp_Deposit;

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

	$users = lftp_users();

	if (!empty($users)) {
		foreach ($users as $user) {
			if (can_claim_lftpb($user)) {
				try {
					$dbh->beginTransaction();

					update_indirect($user);

					$dbh->commit();
				} catch (Exception $e) {
					try {
						$dbh->rollback();
					} catch (Exception $e2) {
					}
				}
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
function lftp_users()
{
	return fetch_all(
		'SELECT *' .
		' FROM network_leadership_fast_track_principal lftp' .
		' INNER JOIN network_users u' .
		' ON lftp.user_id = u.id'
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

function can_claim_lftpb($lftp_user)
{
	$bonus_lftpb_reap = arr_lftpb_reap($lftp_user);

	if (empty($bonus_lftpb_reap)) {
		// return false;
		// check direct fast tracks
	}

	$fast_track_id = $bonus_lftpb_reap['fast_track_id'];

	$fast_track_user = fast_track_user($fast_track_id);

	// get the fast track
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

function arr_lftpb_reap($lftp_user)
{
	$user_bonus_lftpb_reap = $lftp_user->bonus_lftpb_reap;

	$json_user_bonus_lftpb_reap = empty($user_bonus_lftpb_reap) ? '{}' : $user_bonus_lftpb_reap;

	return json_decode($json_user_bonus_lftpb_reap, true);
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