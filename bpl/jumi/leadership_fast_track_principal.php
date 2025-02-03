<?php

namespace BPL\Jumi\Leadership_Fast_Track_Principal;

require_once 'bpl/leadership_fast_track_principal.php';
require_once 'bpl/mods/helpers.php';

use function BPL\Leadership_Fast_Track_Principal\view as view_leadership_fast_track_principal;

use function BPL\Mods\Helpers\menu;
use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\input_get;
use function BPL\Mods\Helpers\page_validate;
use function BPL\Mods\Helpers\page_reload;

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

	if ($uid) {
		$user_id = $uid;
	}

	$str .= page_reload();
	$str .= view_leadership_fast_track_principal($user_id);

	echo $str;
}