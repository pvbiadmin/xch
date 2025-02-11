<?php

namespace BPL\Ajax\Mods\Indirect\Genealogy;

use function BPL\Mods\Local\Database\Query\fetch_all;

use function BPL\Mods\Local\Helpers\settings;
use function BPL\Mods\Local\Helpers\user;

$id_user = filter_input(INPUT_POST, 'id_user', FILTER_VALIDATE_INT);
$plan = filter_input(INPUT_POST, 'plan', FILTER_SANITIZE_STRING);

main($id_user, $plan);

/**
 * @param $id_user
 * @param $plan
 *
 *
 * @since version
 */
function main($id_user, $plan)
{
	$head = user($id_user);

	echo '{';

	details($head, $plan);

	if (count(get_child($id_user))) {
		echo ', "children": [';
		make_json($id_user, $plan);
		echo ']';
	}

	echo '}';
}

/**
 * @param $parent
 * @param $plan
 *
 *
 * @since version
 */
function make_json($parent, $plan)
{
	$children = get_child($parent);

	if (count($children)) {
		foreach ($children as $child) {
			echo array_search($child, $children, true) > 0 ? ', {' : '{';

			details($child, $plan);

			if (count(get_child($child->id))) {
				echo ', "children": [';
				make_json($child->id, $plan);
				echo ']';
			}

			echo '}';
		}
	}
}

/**
 * @param $child
 * @param $plan
 *
 * @since version
 */
function details($child, $plan): void
{
	echo '"id": "' . $child->id . '", ';
	echo '"username": "' . $child->username . '", ';
	echo '"account": "' . settings('entry')->{$child->account_type . '_package_name'} . '", ';

	get_attr($child, $plan);

	echo '"balance": "' . number_format($child->balance, 2) . '"';
}

/**
 * @param $id
 *
 * @return array
 *
 * @since version
 */
function get_child($id): array
{
	return fetch_all(
		'SELECT * ' .
		'FROM network_users ' .
		'WHERE sponsor_id = :sponsor_id ' .
		'AND block = :block',
		[
			'sponsor_id' => $id,
			'block' => 0
		]
	);
}

/**
 *
 * @param $child
 * @param $plan
 *
 * @since version
 */
function get_attr($child, $plan)
{
	$sp = settings('plans');
	$lftp_name = $sp->leadership_fast_track_principal_name;

	$p = [
		'indirect_referral' => ['IR', 'bonus_indirect_referral'],
		'unilevel' => ['UB', 'unilevel'],
		'leadership_binary' => ['LB', 'bonus_leadership'],
		'leadership_passive' => ['LP', 'bonus_leadership_passive'],
		'leadership_fast_track_principal' => ['LFTP', 'bonus_leadership_fast_track_principal']
	];

	foreach ($p as $k => $v) {
		if ($k === $plan) {
			$attr = $v[1];

			echo '"caption": "' . $v[0] . '", ';
			echo '"' . $attr . '": "' . number_format($child->$attr, 2) . '", ';
		}
	}
}