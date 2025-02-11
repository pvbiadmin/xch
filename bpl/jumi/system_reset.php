<?php

namespace BPL\Jumi\System_Reset;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
require_once 'bpl/mods/helpers.php';

use Joomla\CMS\Uri\Uri;

use function BPL\Mods\Url_SEF\sef;

use function Templates\SB_Admin\Tmpl\Master\main as master;

use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\application;

$content = main();

master($content);

/**
 *
 *
 * @since version
 */
function main()
{
	$usertype = session_get('usertype');
	$admintype = session_get('admintype');

	page_validate($usertype, $admintype);

	$link_reset = sef(77);
	$link_admin = sef(43);

	$str = <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">System Reset</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">Reset System Database</li>
		</ol>				
		<a href="$link_reset" type="button" class="btn btn-danger">Reset Database</a>
		<a href="$link_admin" type="button" class="btn btn-link">Back to Admin</a>
	</div>
HTML;

	return $str;
}

/**
 * @param $usertype
 * @param $admintype
 *
 *
 * @since version
 */
function page_validate($usertype, $admintype)
{
	if ($usertype !== 'Admin' && $admintype !== 'Super') {
		application()->redirect(Uri::root(true) . '/' . sef(43));
	}
}
