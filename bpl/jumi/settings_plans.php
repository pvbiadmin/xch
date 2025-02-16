<?php

namespace BPL\Jumi\Settings_Plans;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
require_once 'bpl/settings/plans.php';
require_once 'bpl/mods/helpers.php';

use Exception;

use Joomla\CMS\Uri\Uri;

use function Templates\SB_Admin\Tmpl\Master\main as master;

use function BPL\Settings\Plans\update as update_settings_plans;
use function BPL\Settings\Plans\view as view_settings_plans;

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

	page_validate($usertype, $admintype);

	$str = '';

	try {
		update_settings_plans();
	} catch (Exception $e) {
	}

	$view_settings_plans = view_settings_plans();

	$str .= <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Marketing Plan</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">Settings</li>
		</ol>				
		$view_settings_plans
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