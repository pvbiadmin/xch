<?php

namespace BPL\Jumi\Admin_Account_Update;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
// require_once 'bpl/menu.php';
require_once 'bpl/mods/ajax.php';
require_once 'bpl/mods/helpers.php';

use function Templates\SB_Admin\Tmpl\Master\main as master;

// use function BPL\Menu\admin as menu_admin;

use Joomla\CMS\HTML\HTMLHelper;

use function BPL\Mods\Ajax\check_input3;

use function BPL\Mods\Helpers\page_validate_admin;
use function BPL\Mods\Helpers\session_get;

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

	page_validate_admin($usertype, $admintype);

	// $str = menu_admin($admintype, $account_type, $user_id, $username);

	$str = check_input3();

	$view_update_member_account = view_update_member_account();

	$str .= <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Update Member Account</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">Access And Update All Member Profile</li>
		</ol>				
		$view_update_member_account
	</div>
	HTML;

	return $str;
}

function view_update_member_account(): string
{
	$view_form = view_form();

	return <<<HTML
    <div class="container-fluid px-4">        
		<div class="row justify-content-center">
			<div class="col-lg-5">				
				<div class="card mb-4">
					$view_form
				</div>
        	</div>		
		</div>        
    </div>	
HTML;
}

function view_form()
{
	$form_token = HTMLHelper::_('form.token');

	$str = <<<HTML
    <div class="card-header">
        Update Member Account Detail
    </div>
    <div class="card-body">
        <form method="post" onsubmit="submit.disabled = true;">
            $form_token			
			<div class="form-group">				
				<div class="input-group mb-3">
					<span class="input-group-text"><label for="username">Username</label></span>
					<input type="text" name="username" id="username" class="form-control" 
						placeholder="Enter Username" required aria-label="Username">
					<span class="input-group-text btn btn-primary" onClick="checkInput('username')">Validate</span>
				</div>
				<div id="usernameDiv" class="help-block validation-message"></div>
			</div>
        </form>
    </div>
HTML;

	return $str;
}

// /**
//  *
//  * @return string
//  *
//  * @since version
//  */
// function view_form_(): string
// {
// 	return '
// 		<form method="post" enctype="multipart/form-data" onsubmit="return validateForm()">
// 		    <table class="category table table-striped table-bordered table-hover">
// 		        <tr>
// 		            <td><label for="username">Username: *</label></td>
// 		            <td><input type="text"
// 		                       name="username"
// 		                       id="username"
// 		                       size="40"
// 		                       required="required"
// 		                       style="float:left">
// 		                <a href="javascript:void(0)" onClick="checkInput(\'username\')"
// 		                   class="uk-button uk-button-primary"
// 		                   style="float:left">Check Username</a>
// 		                <div style="width:200px;
// 		                    height:20px;
// 		                    font-weight:bold;
// 		                    float:left;
// 		                    padding:7px 0 0 10px;" id="usernameDiv"></div>
// 		            </td>
// 		        </tr>
// 		    </table>
// 		</form>';
// }