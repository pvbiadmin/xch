<?php

namespace BPL\Jumi\Member_Info;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
require_once 'bpl/mods/root_url_upline.php';
require_once 'bpl/mods/helpers.php';

use function BPL\Mods\Root_Url_Upline\main as root_url;

use function BPL\Mods\Url_SEF\sef;
use function BPL\Mods\Url_SEF\qs;

use function Templates\SB_Admin\Tmpl\Master\main as master;

use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\page_validate;
// use function BPL\Mods\Helpers\menu;
use function BPL\Mods\Helpers\input_get;
use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\settings;
use function BPL\Mods\Helpers\user;

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

	$uid = input_get('uid');

	page_validate();

	$style_table = style_table();

	if ($uid !== '') {
		$user_id = $uid;
	}

	$account_information = account_information($user_id);
	$referral_information = referral_information($user_id);
	$contact_information = contact_information($user_id);
	$payment_information = payment_information($user_id);
	$btn_profile_update = btn_profile_update();

	$str = "<style>$style_table</style>";

	$str .= <<<HTML
<div class="container-fluid px-4">
	<h1 class="mt-4">Details</h1>
	<ol class="breadcrumb mb-4">
		<li class="breadcrumb-item active"></li>
	</ol>
	<div class="card mb-4">
		<div class="card-body">
			<div class="d-grid gap-2 d-md-flex justify-content-md-end mb-3">				
				$btn_profile_update
			</div>

			$account_information
			$referral_information
			$contact_information
			$payment_information
		</div>
	</div>
</div>
HTML;

	return $str;
}

function btn_profile_update()
{
	$user_id = session_get('user_id');
	$uid = input_get('uid');

	$user = user($user_id);

	$str = '';

	if (!empty($user_id) && (empty($uid) || $uid === $user_id)) {
		$url = sef(60) . qs() . 'uid=' . $user->id;
		$str .= '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') .
			'" class="btn btn-primary">Edit Details</a>';
	}

	return $str;
}

function style_table()
{
	$str = <<<CSS
.table th, .table td {
	padding: 10px;
	vertical-align: middle;
	text-align: left;
}

.table th {
	width: 30%;
}

.table td {
	width: 70%;
}

.input-group {
	display: flex;
	align-items: center;
	max-width: 400px;
}

.input-group input {
	flex: 1;
	min-width: 200px;
}

.input-group button {
	white-space: nowrap;
}
CSS;

	return $str;
}

function account_information($user_id)
{
	$sa = settings('ancillaries');

	$currency = $sa->currency;
	$currency_info = htmlspecialchars($currency);

	$user = user($user_id);

	$username = $user->username;
	$fullname = $user->fullname;
	$wallet_balance = $user->payout_transfer;

	$username_info = !empty($username) ? htmlspecialchars($username) : '---';
	$fullname_info = !empty($fullname) ? htmlspecialchars($fullname) : '---';
	$wallet_balance_info = number_format($wallet_balance, 2);

	$str = <<<HTML
<div class="card mb-4">
	<div class="card-header">
		<i class="fas fa-table me-1"></i>
		Member Information
	</div>
	<div class="card-body">
		<div class="row">
			<table class="table table-hover table-responsive">            
				<tbody>
					<tr>
						<th scope="row">Username</th>
						<td>$username_info</td>
					</tr>
					<tr>
						<th scope="row">Fullname</th>
						<td>$fullname_info</td>                        
					</tr>
					<tr>
						<th scope="row">Wallet Balance</th>
						<td colspan="2">$wallet_balance_info $currency_info</td>                        
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>
HTML;

	return $str;
}

function referral_information($user_id)
{
	$sp = settings('plans');

	if (!$sp->direct_referral_fast_track_principal) {
		return '';
	}

	$user = user($user_id);
	$username = $user->username;
	$sponsored_members = directs($user_id);
	$count_sponsored_members = count($sponsored_members);
	$link = 'http://' . $_SERVER['SERVER_NAME'] . root_url() . '/' . htmlspecialchars($username);
	$script = script_copy_referral_link();

	$view_sponsor = '';

	if ($user->usertype !== 'Admin') {
		$sponsor_id = $user->sponsor_id;
		$sponsor = user($sponsor_id);
		$sponsor_info_link = sef(44) . qs() . 'uid=' . $sponsor_id;

		$sponsor_username = htmlspecialchars($sponsor->username);

		$view_sponsor = <<<HTML
		<tr>
			<th scope="row">Sponsor</th>
			<td colspan="2"><a href="$sponsor_info_link">$sponsor_username</a></td>                        
		</tr>
HTML;
	}

	$str = <<<HTML
<div class="card mb-4">
	<div class="card-header">
		<i class="fas fa-table me-1"></i>
		Referral Information
	</div>
	<div class="card-body">
		<div class="row">
			<table class="table table-hover table-responsive">            
				<tbody>
					<tr>
						<th scope="row">Referral Link</th>
						<td>
							<div class="input-group">
								<input id="myLink" type="text" class="form-control" value="$link" 
									aria-label="Referral Link" aria-describedby="button-referral-link" readonly>
								<button class="btn btn-outline-secondary" type="button" id="button-referral-link" 
									data-tooltip="Copy to clipboard">
									Copy
								</button>
							</div>
						</td>
					</tr>
					<tr>
						<th scope="row">Sponsored Members</th>
						<td>$count_sponsored_members</td>                        
					</tr>
					$view_sponsor
				</tbody>
			</table>
		</div>
	</div>
</div>
HTML;

	$str .= "<script>$script</script>";
	return $str;
}

function script_copy_referral_link()
{
	// JavaScript for tooltip and clipboard copy
	$script = <<<JS
document.addEventListener("DOMContentLoaded", function() {
	const copyButton = document.getElementById('button-referral-link');
	const copyInput = document.getElementById('myLink');
	let tooltipTimeout;

	// Create and append tooltip element
	const tooltip = document.createElement('div');
	tooltip.className = 'copy-tooltip';
	tooltip.style.cssText = 'position: absolute; background: rgba(0,0,0,0.8); color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px; pointer-events: none; opacity: 0; transition: opacity 0.2s; z-index: 1000;';
	document.body.appendChild(tooltip);

	// Show tooltip function
	function showTooltip(element, message) {
		const rect = element.getBoundingClientRect();
		tooltip.textContent = message;
		tooltip.style.opacity = '1';
		tooltip.style.left = rect.left + (rect.width - tooltip.offsetWidth) / 2 + 'px';
		tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
	}

	// Hide tooltip function
	function hideTooltip() {
		tooltip.style.opacity = '0';
	}

	// Modern clipboard API with fallback
	async function copyToClipboard(text) {
		try {
			if (navigator.clipboard && window.isSecureContext) {
				await navigator.clipboard.writeText(text);
				return true;
			} else {
				// Fallback for older browsers
				const textArea = document.createElement('textarea');
				textArea.value = text;
				textArea.style.position = 'fixed';
				textArea.style.left = '-999999px';
				document.body.appendChild(textArea);
				textArea.select();
				try {
					document.execCommand('copy');
					textArea.remove();
					return true;
				} catch (error) {
					console.error('Copy failed:', error);
					textArea.remove();
					return false;
				}
			}
		} catch (error) {
			console.error('Copy failed:', error);
			return false;
		}
	}

	// Event listeners
	copyButton.addEventListener('mouseover', () => {
		showTooltip(copyButton, copyButton.getAttribute('data-tooltip'));
	});

	copyButton.addEventListener('mouseout', () => {
		if (!copyButton.matches(':active')) {
			hideTooltip();
		}
	});

	copyButton.addEventListener('click', async () => {
		clearTimeout(tooltipTimeout);
		const success = await copyToClipboard(copyInput.value);
		
		if (success) {
			showTooltip(copyButton, 'Copied!');
			tooltipTimeout = setTimeout(() => {
				hideTooltip();
				copyButton.setAttribute('data-tooltip', 'Copy to clipboard');
			}, 1500);
		} else {
			showTooltip(copyButton, 'Copy failed');
			tooltipTimeout = setTimeout(hideTooltip, 1500);
		}
	});

	// Update tooltip position on scroll and resize
	['scroll', 'resize'].forEach(event => {
		window.addEventListener(event, () => {
			if (tooltip.style.opacity !== '0') {
				const rect = copyButton.getBoundingClientRect();
				tooltip.style.left = rect.left + (rect.width - tooltip.offsetWidth) / 2 + 'px';
				tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
			}
		}, { passive: true });
	});
});
JS;

	return $script;
}

function contact_information($user_id)
{
	$user = user($user_id);
	$user_address = $user->address;
	$user_email = $user->email;
	$user_contact = $user->contact;

	$user_address_format = get_formatted_address($user_address);
	$email_format = !empty($user_email) ? htmlspecialchars($user_email) : '---';
	$contact = contact_info($user_contact);

	$str = <<<HTML
<div class="card mb-4">
	<div class="card-header">
		<i class="fas fa-table me-1"></i>
		Contact Information
	</div>
	<div class="card-body">
		<div class="row">
			<table class="table table-hover table-responsive">            
				<tbody>
					<tr>
						<th scope="row">Address</th>
						<td>$user_address_format</td>
					</tr>
					<tr>
						<th scope="row">Email</th>
						<td>$email_format</td>                        
					</tr>
					<tr>
						<th scope="row">Contact</th>
						<td>$contact</td>                        
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>
HTML;

	return $str;
}

function payment_information($user_id)
{
	$user = user($user_id);
	$payment_method = $user->payment_method;

	$payment_info = payment_info2($payment_method);

	$str = <<<HTML
<div class="card mb-4">
	<div class="card-header">
		<i class="fas fa-table me-1"></i>
		Payment Information
	</div>
	<div class="card-body">
		<div class="row">
			<table class="table table-hover table-responsive">            
				<tbody>
					<tr>
						<th scope="row">Payment Methods</th>
						<td>$payment_info</td>
					</tr>					
				</tbody>
			</table>
		</div>
	</div>
</div>
HTML;

	return $str;
}

function view_table($user_id, $usertype): string
{
	$settings_plans = settings('plans');
	$user = user($user_id);
	$currency = settings('ancillaries')->currency;

	$str = '<div class="container table-responsive">';
	$str .= '<h1 class="text-center">' . /*htmlspecialchars($user->username)*/ 'Profile' . '</h1>';

	$str .= '<div class="card mb-3">';
	$str .= '<div class="card-header"><strong>Account Information</strong></div>';
	$str .= '<div class="card-body">';

	// Improved Table Styling
	$str .= '<style>
        .custom-table {
            background-color: #ffffff; /* White background for the table */
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .custom-table th {
            background-color: #f5f5f5; /* Light grey background for headers */
            color: #333; /* Dark text color for contrast */
            padding: 12px;
            text-align: left;
        }
        .custom-table td {
            background-color: #ffffff; /* White background for table cells */
            color: #333; /* Dark text color for contrast */
            padding: 12px;
            border-bottom: 1px solid #ddd; /* Light grey border for rows */
        }
        .custom-table tr:hover {
            background-color: #f9f9f9; /* Light grey background on row hover */
        }
    </style>';

	$str .= '<table class="custom-table">';
	$str .= '<tr><th>Detail</th><th>Information</th></tr>';
	$str .= '<tr><td>Username:</td><td>' . (!empty($user->username) ? htmlspecialchars($user->username) : '-') . '</td></tr>';
	$str .= '<tr><td>Full Name:</td><td>' . (!empty($user->fullname) ? htmlspecialchars($user->fullname) : '-') . '</td></tr>';
	//	$str .= '<tr><td>Account Type:</td><td><span class="badge badge-info">' . htmlspecialchars(settings('entry')->{$user->account_type . '_package_name'}) . '</span></td></tr>';
//	$str .= '<tr><td>Joined:</td><td>' . date('M j, Y - g:i A', $user->date_registered) . '</td></tr>';
	$str .= '<tr><td>USDT Wallet:</td><td><span class="badge badge-success">' . number_format($user->payout_transfer, 8) . ' ' . htmlspecialchars($currency) . '</span></td></tr>';
	$str .= '</table>';

	$str .= '</div></div>';

	if ($settings_plans->direct_referral) {
		$str .= '<div class="card mb-3">';
		$str .= '<div class="card-header d-flex justify-content-between align-items-center">';
		$str .= '<strong>Referral Information</strong>';
		$str .= '</div>'; // End of card-header
		$str .= '<div class="card-body">';

		// Create referral link input with a button to copy it
		$link = 'http://' . $_SERVER['SERVER_NAME'] . root_url() . '/' . htmlspecialchars($user->username);
		$str .= '<div class="uk-width-1-1 uk-form">
            <fieldset>                                                  
                <div class="uk-form-row">
                    <input type="text" value="' . $link . '" id="myLink" readonly
                    	class="uk-form-width-medium custom-input-width">
                    <button class="btn btn-primary" onClick="copyLink()" type="reset">Copy</button>
                </div>                            
            </fieldset>
        </div>';

		$str .= '<style>
			.custom-input-width {
			    width: 480px; /* Adjust as needed */
			}
		</style>';

		// Add table with referral details
		$str .= '<div class="table-responsive">'; // Add a wrapper for responsiveness
		$str .= '<table class="custom-table">';
		$str .= '<tr><td>Sponsored Members:</td><td>' . count(directs($user_id)) . '</td></tr>';

		$tmp = user($user->sponsor_id);
		$sponsor_link = (!empty($tmp) && $tmp->username !== '') ? '<a href="' . sef(44) . qs() . 'uid=' . $user->sponsor_id . '">' . htmlspecialchars($tmp->username) . '</a>' : '-';
		$str .= '<tr><td>Sponsor:</td><td>' . $sponsor_link . '</td></tr>';
		$str .= '</table>';
		$str .= '</div>'; // Close table-responsive wrapper

		$str .= '</div>'; // End of card-body
		$str .= '</div>'; // End of card

		$str .= '<style>
			/* Flexbox setup for card-header */
			.card-header {
			    display: flex;
			    justify-content: space-between;
			    align-items: center;
			    background-color: #f5f5f5; /* Light grey background */
			    border-bottom: 1px solid #ddd; /* Light border */
			    padding: 10px 15px;
			}
			
			/* Ensure card-body and content align well */
			.card-body {
			    padding: 15px;
			}
			
			.input-group {
			    width: 100%; /* Full width for the input group */
			}
			
			.input-group .form-control {
			    flex: 1; /* Ensure the input field takes up available space */
			}
			
			.table-responsive {
			    margin-top: 15px; /* Add some space above the table */
			}
			
			/* Custom table styling */
			.custom-table {
			    width: 100%;
			    border-collapse: collapse;
			}
			
			.custom-table th, .custom-table td {
			    padding: 8px 12px;
			    border: 1px solid #ddd;
			    text-align: left;
			}
			
			.custom-table tr:nth-child(even) {
			    background-color: #f9f9f9; /* Alternating row colors */
			}
			
			.custom-table tr:hover {
			    background-color: #f1f1f1; /* Hover effect */
			}
		</style>';
	}

	if ($settings_plans->binary_pair) {
		$user_binary = user_binary($user_id);

		$upline_username = 'n/a';

		if ($user_binary) {
			$user_binary = user($user_binary->upline_id);

			if ($user_binary) {
				$upline_username = htmlspecialchars($user_binary->username);
			}
		}

		$str .= '<div class="card mb-3">';
		$str .= '<div class="card-header"><strong>Binary Information</strong></div>';
		$str .= '<div class="card-body">';
		$str .= '<table class="custom-table">';
		$str .= '<tr><td>Upline:</td><td>' . $upline_username . '</td></tr>';
		$str .= '</table>';
		$str .= '</div></div>';
	}

	$str .= '<div class="card mb-3">';
	$str .= '<div class="card-header"><strong>Contact Information</strong></div>';
	$str .= '<div class="card-body">';
	$str .= '<table class="custom-table">';
	$str .= '<tr><td>Address:</td><td>' . get_formatted_address($user->address) . '</td></tr>';
	$str .= '<tr><td>Email:</td><td>' . (!empty($user->email) ? htmlspecialchars($user->email) : '-') . '</td></tr>';
	$str .= '<tr><td>Contact Info:</td><td>' . contact_info($user->contact) . '</td></tr>';
	$str .= '</table>';
	$str .= '</div></div>';

	$str .= '<div class="card mb-3">';
	$str .= '<div class="card-header"><strong>Payment Information</strong></div>';
	$str .= '<div class="card-body">';
	$str .= '<table class="custom-table">';
	$str .= '<tr><td>Payment Method:</td><td>' . payment_info($user->payment_method) . '</td></tr>';
	//	$str .= '<tr><td>Beneficiary:</td><td>' . contact_info($user->beneficiary) . '</td></tr>';
	$str .= '</table>';
	$str .= '</div></div>';

	$str .= '<div class="text-center">';

	// if ($user_id !== '' && (input_get('uid') === '' || (input_get('uid') !== '' && input_get('uid') === session_get('user_id')))) {
	// 	$str .= '<a href="' . sef(60) . qs() . 'uid=' . $user->id . '" class="btn btn-secondary">Update Profile</a> ';
	// }

	if (!empty($user_id) && (empty(input_get('uid')) || input_get('uid') === session_get('user_id'))) {
		$url = sef(60) . qs() . 'uid=' . $user->id;
		$str .= '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="btn btn-secondary">Update Profile</a> ';
	}

	if ($usertype === 'Admin') {
		$str .= '<a href="' . sef(106) . qs() . 'uid=' . $user->id . '" class="btn btn-secondary">View Transactions</a>';
	}
	$str .= '</div>';

	$str .= '</div>'; // Close container

	return $str;
}

/**
 * @param $user_id
 *
 * @return array|mixed
 *
 * @since version
 */
function directs($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_users ' .
		'WHERE sponsor_id = ' . $db->quote($user_id)
	)->loadObjectList();
}

function user_binary($user_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT * ' .
		'FROM network_users u ' .
		'INNER JOIN network_binary b ' .
		'ON u.id = b.user_id ' .
		'WHERE b.user_id = ' . $db->quote($user_id)
	)->loadObject();
}

function get_formatted_address($address): string
{
	$tmp = explode('|', $address);
	$country = (isset($tmp[4]) && $tmp[4] !== '') ? (get_country_name($tmp[4])) : '';

	return (!empty($address) ? $tmp[0] . ' ' . $tmp[1] . '<br>' . $tmp[2] . ', ' . $tmp[3] . '<br>' . $country : '---');
}

function payment_info($payment_method): string
{
	$pmu = json_decode($payment_method, true);

	$str = '';

	if (!empty($pmu)) {
		foreach ($pmu as $k => $v) {
			$str .= '<p class="category table table-bordered">';
			$str .= '<a class="uk-button uk-button-success uk-button-small" href="javascript:void(0)">';
			$str .= strtoupper($k);
			$str .= '</a>';

			if (!is_array($v)) {
				$str .= '<small style="padding-left: 7px"><b>' . $v . '</b></small>';
			} else {
				foreach ($v as $x => $y) {
					$str .= '<small style="padding-left: 7px"><b>' . strtoupper($x) . ': ' . $y . '</b></small>';
				}
			}

			$str .= '</p>';
		}
	}

	return $str;
}

function payment_info2($payment_method): string
{
	$pmu = json_decode($payment_method, true);
	$str = '---';

	if (!empty($pmu)) {
		$str = ''; // Reset string if we have content
		foreach ($pmu as $k => $v) {
			if (!is_array($v)) {
				// Handle simple key-value pairs
				$str .= '<div class="input-group mb-2">';
				$str .= '<div class="input-group-text">' . ucwords(htmlspecialchars($k)) . '</div>';
				$str .= '<input type="text" class="form-control" value="' . htmlspecialchars($v) . '" readonly>';
				$str .= '</div>';
			} else {
				// Handle nested arrays
				$str .= '<div class="input-group mb-2">';
				$str .= '<div class="input-group-text">' . ucwords(htmlspecialchars($k)) . '</div>';

				// Create a concatenated string of all nested values
				$nested_values = [];
				foreach ($v as $x => $y) {
					$nested_values[] = ucwords(htmlspecialchars($x)) . ': ' . htmlspecialchars($y);
				}
				$str .= '<input type="text" class="form-control" value="' . implode(' | ', $nested_values) . '" readonly>';
				$str .= '</div>';
			}
		}
	}

	return $str;
}

function contact_info($contact): string
{
	$ciu = json_decode($contact, true);
	$str = '---';

	if (!empty($ciu)) {
		foreach ($ciu as $k => $v) {
			$str = '<div class="input-group mb-2">';
			$str .= '<div class="input-group-text">' . ucwords(htmlspecialchars($k)) . '</div>';
			$str .= '<input type="text" class="form-control" value="' . htmlspecialchars($v) . '" readonly>';
			$str .= '</div>';
		}
	}

	return $str;
}

/**
 * @param $country_id
 *
 * @return mixed
 *
 * @since version
 */
function get_country_name($country_id)
{
	$db = db();

	return $db->setQuery(
		'SELECT countryName ' .
		'FROM countries ' .
		'WHERE idCountry = ' . $db->quote($country_id)
	)->loadObject()->countryName;
}

/**
 *
 * @return string
 *
 * @since version
 */
function scripts(): string
{
	$str = '<script>';
	$str .= 'function copyLink() {
        /* Get the text field */
        var copyText = document.getElementById("myLink");

        /* Select the text field */
        copyText.select();

        /* Copy the text inside the text field */
        document.execCommand("copy");

        /* Alert the copied text */
        //alert("Copied the text: " + copyText.value);
    };
    
    (function ($) {
        $.fn.textWidth = function (text, font) {

            if (!$.fn.textWidth.fakeEl) $.fn.textWidth.fakeEl = $("<span>").hide().appendTo(document.body);

            $.fn.textWidth.fakeEl.text(text || this.val() || this.text() || this.attr("placeholder")).css("font", font || this.css("font"));

            return $.fn.textWidth.fakeEl.width();
        };

        var cl_width_dynamic = $(".width-dynamic");

        cl_width_dynamic.on("input", function () {
            var inputWidth = $(this).textWidth();
            $(this).css({
                width: inputWidth
            })
        }).trigger("input");


        function inputWidth(elem) {
            elem = $(this);
            console.log(elem)
        }

        //var targetElem = cl_width_dynamic;

        inputWidth(cl_width_dynamic);
    })(jQuery);';
	$str .= '</script>';

	return $str;
}