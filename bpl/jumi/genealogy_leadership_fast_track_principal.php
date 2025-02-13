<?php

namespace BPL\Jumi\Genealogy_Leadership_Fast_Track_Principal;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
require_once 'bpl/ajax/ajaxer/genealogy.php';
require_once 'bpl/mods/helpers.php';

use function Templates\SB_Admin\Tmpl\Master\main as master;

use function BPL\Ajax\Ajaxer\Genealogy\main as genealogy;

use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\page_validate;
use function BPL\Mods\Helpers\input_get;

$content = main();

master($content);

/**
 *
 *
 * @since version
 */
function main()
{
	$user_id = session_get('user_id');

	page_validate();

	$uid = input_get('uid');

	if ($uid !== '') {
		$user_id = $uid;
	}

	$view_structure = view_structure($user_id);

	$str = <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Genealogy Tree</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">Structure of Accounts</li>
		</ol>				
		$view_structure
	</div>
	HTML;

	return $str;
}

function view_structure($user_id)
{
	$genealogy = genealogy('indirect', $user_id, 'leadership_fast_track_principal');

	return <<<HTML
	<div class="card mb-4">
		<div class="card-header">
			<i class="fas fa-sitemap me-1"></i>
			Royalty Bonus
		</div>
		<div class="card-body">
		$genealogy
		</div>
		<div class="card-footer small text-muted">Genealogy Structure</div>
	</div>
	HTML;
}