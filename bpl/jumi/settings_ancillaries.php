<?php

namespace BPL\Jumi\Settings_Ancillaries;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
// require_once 'bpl/menu.php';
require_once 'bpl/settings/ancillaries.php';
require_once 'bpl/mods/helpers.php';

use Joomla\CMS\Uri\Uri;

// use function BPL\Menu\admin as menu_admin;

use function Templates\SB_Admin\Tmpl\Master\main as master;

use function BPL\Settings\Ancillaries\update as update_settings_ancillaries;
use function BPL\Settings\Ancillaries\view as view_settings_ancillaries;

use function BPL\Mods\Url_SEF\sef;

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
	// $account_type = session_get('account_type');
	// $user_id = session_get('user_id');
	// $username = session_get('username');

	page_validate($usertype, $admintype);

	// $str = menu_admin($admintype, $account_type, $user_id, $username);

	$str = '';

	update_settings_ancillaries();

	$view_settings_ancillaries = view_settings_ancillaries();

	$str .= <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Ancillaries</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">Settings</li>
		</ol>				
		$view_settings_ancillaries
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