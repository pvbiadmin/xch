<?php

namespace BPL\Jumi\Slogin;

require_once 'templates/sb_admin/tmpl/slogin.tmpl.php';
require_once 'bpl/mods/url_sef.php';
require_once 'bpl/mods/helpers.php';

use Joomla\CMS\Uri\Uri;

use function BPL\Mods\Url_SEF\sef;

use function Templates\SB_Admin\Tmpl\Slogin\main as view_slogin;

use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\input_get;
use function BPL\Mods\Helpers\application;
use function BPL\Mods\Helpers\session_set;

main();

/**
 *
 *
 * @since version
 */
function main()
{
	$password = input_get('password');
	$username = input_get('username');

	if ($username === '') {
		view_slogin();
	} else {
		slogin($username, $password);
	}
}

/**
 * @param $susername
 * @param $spassword
 *
 * @return array|mixed
 *
 * @since version
 */
function admin_get($susername, $spassword)
{
	$db = db();

	return $db->setQuery(
		'SELECT admintype ' .
		'FROM network_admin ' .
		'WHERE username = ' . $db->quote($susername) .
		' AND password = ' . $db->quote(md5($spassword))
	)->loadObjectList();
}

/**
 * @param $susername
 * @param $spassword
 *
 *
 * @since version
 */
function slogin($susername, $spassword)
{
	$app = application();

	$result = admin_get($susername, $spassword);

	if (!empty($result)) {
		session_set('admintype', $result[0]->admintype);

		$app->redirect(Uri::root(true) . '/' . sef(43));
	} else {
		$app->enqueueMessage('Super Login Failed!', 'error');
		$app->redirect(Uri::root(true) . '/' . sef(43));
	}
}