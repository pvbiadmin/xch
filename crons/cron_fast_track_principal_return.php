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

function main()
{
	$dbh = DB_Cron::connect();

	$users = users();

	foreach ($users as $user) {
		try {
			$dbh->beginTransaction();

			return_principal_fast_track($user);

			$dbh->commit();
		} catch (Exception $e) {
			try {
				$dbh->rollback();
			} catch (Exception $e2) {
			}
		}
	}
}

function return_principal_fast_track($user)
{
	if ($user->bonus_lftp_list !== null) {
		$arr_lftp_list = arr_lftp_list($user);

		$filtered_arr_lftp_list = array_filter($arr_lftp_list, function ($item) {
			return $item['fast_track_id'] != 0 && $item['reaped'] != 0;
		});

		foreach ($filtered_arr_lftp_list as $lftp) {

			$dl_fast_track_id = $lftp['fast_track_id'];

			$dl_fast_track_user = fast_track_user($dl_fast_track_id);

			if (!$dl_fast_track_user) {
				update_bonus_lftp_list($user, $dl_fast_track_id);
				continue;
			}

			$fast_track_days = $dl_fast_track_user->day;

			if ($fast_track_days >= 100) {
				$fast_track_principal = $dl_fast_track_user->principal;

				$principal = $fast_track_principal;

				update_dl_user_efund($dl_fast_track_id, $principal);
				delete_dl_fast_track($dl_fast_track_id);
				update_bonus_lftp_list($user, $dl_fast_track_id);
			}
		}
	}
}

function update_dl_user_efund($dl_fast_track_id, $principal)
{
	$dl_fast_track_user = fast_track_user($dl_fast_track_id);

	$dl_fast_track_user_id = $dl_fast_track_user->user_id;

	$dl_user = user($dl_fast_track_user_id);

	$efund = $dl_user->payout_transfer;

	$efund_new = $efund + $principal;

	crud(
		'UPDATE network_users' .
		' SET payout_transfer = :payout_transfer' .
		' WHERE id = :id',
		[
			'payout_transfer' => $efund_new,
			'id' => $dl_fast_track_user_id
		]
	);
}

function delete_dl_fast_track($dl_fast_track_id)
{
	crud(
		'DELETE FROM network_fast_track' .
		' WHERE fast_track_id = :fast_track_id',
		[
			'fast_track_id' => $dl_fast_track_id
		]
	);
}

function update_bonus_lftp_list($user, $dl_fast_track_id)
{
	// Decode the bonus_lftp_list JSON array
	$bonus_lftp_list = arr_lftp_list($user);

	foreach ($bonus_lftp_list as $key => &$entry) {
		if ($entry['fast_track_id'] == $dl_fast_track_id) {
			// Remove the entry with $entry['fast_track_id'] == $fast_track_id
			unset($bonus_lftp_list[$key]);
		}
	}

	// Re-index the array to remove any gaps left by unset()
	$bonus_lftp_list = array_values($bonus_lftp_list);

	// Encode the updated array back to JSON
	$updated_bonus_lftp_list = json_encode($bonus_lftp_list);

	crud(
		'UPDATE network_users' .
		' SET bonus_lftp_list = :bonus_lftp_list' .
		' WHERE id = :id',
		[
			'bonus_lftp_list' => $updated_bonus_lftp_list,
			'id' => $user->id
		]
	);
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

function user($user_id)
{
	return fetch(
		'SELECT * ' .
		'FROM network_users ' .
		'WHERE id = :id',
		['id' => $user_id]
	);
}

function users()
{
	return fetch_all(
		'SELECT *' .
		' FROM network_users'
	);
}

function arr_lftp_list($lftp_user)
{
	$user_bonus_lftp_list = $lftp_user->bonus_lftp_list;

	$json_user_bonus_lftp_list = empty($user_bonus_lftp_list) ? '{}' : $user_bonus_lftp_list;

	return json_decode($json_user_bonus_lftp_list, true);
}