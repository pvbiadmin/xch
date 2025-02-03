<?php

namespace BPL\Jumi\Genealogy_Leadership_Fast_Track_Principal;

require_once 'bpl/ajax/ajaxer/genealogy.php';
require_once 'bpl/mods/helpers.php';

use function BPL\Ajax\Ajaxer\Genealogy\main as genealogy;

use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\page_validate;
use function BPL\Mods\Helpers\menu;
use function BPL\Mods\Helpers\input_get;

main();

/**
 *
 *
 * @since version
 */
function main()
{
	$user_id = session_get('user_id');

	page_validate();

	$str = menu();

	$uid = input_get('uid');

	if ($uid !== '') {
		$user_id = $uid;
	}

	$str .= '<h1>Genealogy Tree</h1>';
	$str .= genealogy('indirect', $user_id, 'leadership_fast_track_principal');

	echo $str;
}