<?php

namespace BPL\Jumi\Fast_Track;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
require_once 'bpl/ajax/ajaxer/fast_track_input.php';
require_once 'bpl/ajax/ajaxer/fast_track.php';
require_once 'bpl/mods/time_remaining.php';
// require_once 'bpl/ajax/ajaxer/table_fast_track.php';
require_once 'bpl/mods/time_remaining.php';
// require_once 'bpl/mods/table_daily_interest.php';
require_once 'bpl/mods/helpers.php';

// use Exception;

use DateTime;
use DateInterval;

use function BPL\Ajax\Ajaxer\Fast_Track_Input\main as fast_track_input;
use function BPL\Ajax\Ajaxer\Fast_Track\main as ajax_fast_track;
// use function BPL\Ajax\Ajaxer\Table_Fast_Track\main as ajax_table_fast_track;
// use function BPL\Echelon_Bonus\view;
use function BPL\Mods\Time_Remaining\main as time_remaining;
// use function BPL\Mods\Table_Daily_Interest\tbody;

use function Templates\SB_Admin\Tmpl\Master\main as master;

// use function BPL\Mods\Url_SEF\qs;
// use function BPL\Mods\Url_SEF\sef;

use function BPL\Mods\Helpers\session_get;
// use function BPL\Mods\Helpers\input_get;
use function BPL\Mods\Helpers\page_validate;
// use function BPL\Mods\Helpers\menu;
use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\settings;
use function BPL\Mods\Helpers\user;
// use function BPL\Mods\Helpers\pgn8;
// use function BPL\Mods\Helpers\live_reload;

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

    $notifications = notifications();
    $view_form = view_form();
    $view_shares = view_shares($user_id);
    $view_principal = view_principal($user_id);
    $view_balance = view_balance($user_id);
    $view_tbl_fast_track = view_tbl_fast_track($user_id, false);

    $str = <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Passive Income</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">Add Package</li>
		</ol>
		$notifications
		<div class="row">
			$view_form
			$view_shares
			$view_principal
			$view_balance
		</div>				
		$view_tbl_fast_track
	</div>
	HTML;

    $str .= fast_track_input($user_id);
    $str .= ajax_fast_track($user_id);

    return $str;
}

function notifications()
{
    $notification_style = <<<CSS
        .notification {
            display: none;
        }
    CSS;

    $notification_script = <<<JS
        function showNotification(type, message) {
            const notification = document.querySelector(`.\${type}`);
            notification.textContent = message;
            notification.style.display = 'block';
            
            // Run the original silentReload immediately
            if (type === 'success_fast_track') {
                silentReload();

                // Disable the submit button and clear the input field
                const submitButton = document.getElementById('fast_track');
                const inputField = document.getElementById('fast_track_input');
                if (submitButton && inputField) {
                    submitButton.disabled = true;
                    inputField.value = ''; // Clear the input field
                }
            }

            setTimeout(() => {
                notification.style.display = 'none';
                
                // // After the notification is hidden, start reloading the table every 5 seconds
                // if (type === 'success_fast_track') {
                //     setInterval(silentReloadTable, 5000); // Reload table every 5 seconds
                // }
            }, 5000);
        }

        setInterval(silentReloadTable, 5000);

        function silentReload() {
            fetch(window.location.href, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                // Update specific parts of the page without a full refresh
                const parser = new DOMParser();
                const newDoc = parser.parseFromString(html, 'text/html');

                // Update the principal value
                const newPrincipalValue = newDoc.querySelector('.fast_track_principal').textContent;
                document.querySelector('.fast_track_principal').textContent = newPrincipalValue;

                // Update the table
                // const newTableBody = newDoc.querySelector('#datatablesSimple tbody');
                // document.querySelector('#datatablesSimple tbody').innerHTML = newTableBody.innerHTML;

                // Clear the input field
                document.getElementById('fast_track_input').value = '';
                document.getElementById('fast_track').disabled = true;
            })
            .catch(error => {
                console.error('Error during silent reload:', error);
            });
        }

        function silentReloadTable() {
            fetch(window.location.href, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                // Update only the table
                const parser = new DOMParser();
                const newDoc = parser.parseFromString(html, 'text/html');
                const newTableBody = newDoc.querySelector('#datatablesSimple tbody');
                document.querySelector('#datatablesSimple tbody').innerHTML = newTableBody.innerHTML;

                // Update the shares value
                const newSharesValue = newDoc.querySelector('.fast_track_value_last').textContent;
                document.querySelector('.fast_track_value_last').textContent = newSharesValue;
            })
            .catch(error => {
                console.error('Error during table reload:', error);
            });
        }
    JS;

    return <<<HTML
		<div class="success_fast_track notification alert alert-success alert-dismissible fade show" 
			role="alert">Success Fast Track<button type="button" class="btn-close" 
				data-bs-dismiss="alert" aria-label="Close"></button>
		</div>
		<div class="error_fast_track notification alert alert-danger alert-dismissible fade show" 
			role="alert">Error Fast Track<button type="button" class="btn-close" 
				data-bs-dismiss="alert" aria-label="Close"></button>
		</div>
		<div class="debug_fast_track notification alert alert-info alert-dismissible fade show" 
			role="alert">Debug Fast Track<button type="button" class="btn-close" 
				data-bs-dismiss="alert" aria-label="Close"></button>
		</div>
		<style>{$notification_style}</style>
		<script>{$notification_script}</script>
	HTML;
}

function view_form()
{
    return <<<HTML
        <div class="col-xl-3 col-md-6" id="view-form-container">
            <div class="card mb-4">
                <div class="card-body">Add Package</div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <input type="text" id="fast_track_input" placeholder="Enter value" class="form-control">
                    &nbsp;
                    <button id="fast_track" class="btn btn-primary">Add</button>
                </div>
            </div>
        </div>
    HTML;
}

function view_shares($user_id)
{
    $user_fast_track = user_fast_track($user_id);

    $value_last = 0;

    foreach ($user_fast_track as $fast_track) {
        $value_last += $fast_track->value_last;
    }

    $shares = number_format($value_last, 8);

    return <<<HTML
		<div class="col-xl-3 col-md-6">
			<div class="card mb-4">
				<div class="card-body">Profit</div>
				<div class="card-footer d-flex align-items-center justify-content-between">
					<span class="fast_track_value_last">$shares</span>
					<div class="small"><i class="fas fa-angle-right"></i></div>
				</div>
			</div>
		</div>
	HTML;
}

function view_principal($user_id)
{
    $user = user($user_id);
    $value = number_format($user->fast_track_principal, 8);

    return <<<HTML
		<div class="col-xl-3 col-md-6">
			<div class="card mb-4">
				<div class="card-body">Total Principal</div>
				<div class="card-footer d-flex align-items-center justify-content-between">
					<span class="fast_track_principal">$value</span>
					<div class="small"><i class="fas fa-angle-right"></i></div>
				</div>
			</div>
		</div>
	HTML;
}

function view_balance($user_id)
{
    $sa = settings('ancillaries');
    $user = user($user_id);
    $balance = $user->payout_transfer;
    $balance_format = number_format($balance, 8);

    return <<<HTML
		<div class="col-xl-3 col-md-6">
			<div class="card mb-4">
				<div class="card-body">Wallet</div>
				<div class="card-footer d-flex align-items-center justify-content-between">
					<span class="usd_bal_now_user">$balance_format</span>
					<div class="small"><i class="fas fa-angle-right"></i></div>
				</div>
			</div>
		</div>
	HTML;
}

function view_tbl_fast_track($user_id, $counter): string
{
    $counter_span = '';

    if ($counter) {
        $counter_span = '<span id="counter" style="float:right">00:00:00</span>';
    }

    $table_fast_track = table_list_stakes($user_id);

    $str = <<<HTML
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-table me-1"></i>
				Package{$counter_span}
			</div>
			<div class="card-body">
				<table id="datatablesSimple">
					$table_fast_track
				</table>
			</div>
		</div>
	HTML;

    return $str;
}

function table_list_stakes($user_id)
{
    $si = settings('investment');

    $user = user($user_id);

    $account_type = $user->account_type;

    $maturity = $si->{$account_type . '_fast_track_maturity'};

    $row_list_stakes = row_list_stakes($user_id);

    $str = <<<HTML
		<thead>
			<tr>
				<th>Package</th>
				<th>Profit</th>
				<th>Day</th>
				<th>Time Frame ($maturity days)</th>
				<th>Remarks</th>							
			</tr>
		</thead>
		<tfoot>
            <tr>
				<th>Package</th>
				<th>Profit</th>
				<th>Day</th>
				<th>Time Frame ($maturity days)</th>
				<th>Remarks</th>							
			</tr>
		</tfoot>
		<tbody>
			$row_list_stakes						
		</tbody>
HTML;

    return $str;
}

function row_list_stakes($user_id)
{
    $si = settings('investment');

    $user = user($user_id);

    $account_type = $user->account_type;

    $interval = $si->{$account_type . '_fast_track_interval'};
    $maturity = $si->{$account_type . '_fast_track_maturity'};

    $fast_tracks = user_fast_track($user_id);

    $str = '';

    // if (empty($fast_tracks)) {
    //     $str .= <<<HTML
    // 		<tr>
    // 			<td>0.00</td>
    // 			<td>0.00</td>
    // 			<td>0</td>
    // 			<td>n/a</td>
    // 			<td>n/a</td>				
    // 		</tr>					
    // 	HTML;
    // } else {
    foreach ($fast_tracks as $ft) {
        $start = new DateTime('@' . $ft->date_entry);
        $end = new DateInterval('P' . $maturity . 'D');

        $start->add($end);

        $starting_value = number_format($ft->principal, 2);
        $current_value = number_format($ft->value_last, 2);
        $maturity_date = $start->format('F d, Y');
        $status = time_remaining($ft->day, $ft->processing, $interval, $maturity);

        $remaining = ($ft->processing + $maturity - $ft->day) * $interval;
        $remain_maturity = ($maturity - $ft->day) * $interval;

        $type_day = '';

        if ($remaining > $maturity && $ft->processing) {
            $type_day = 'Days for Processing: ';
        } elseif ($remain_maturity > 0) {
            $type_day = 'Days Remaining: ';
        }

        $str .= <<<HTML
				<tr>
					<td>$starting_value</td>
					<td>$current_value</td>
					<td>$ft->day</td>
					<td>$maturity_date</td>
					<td>{$type_day}{$status}</td>				
				</tr>
			HTML;
    }
    // }

    return $str;
}

/**
 * @param $user_id
 *
 * @return array|mixed
 *
 * @since version
 */
function user_fast_track($user_id)
{
    $db = db();

    return $db->setQuery(
        'SELECT * ' .
        'FROM network_fast_track ' .
        'WHERE user_id = ' . $db->quote($user_id) .
        ' ORDER BY fast_track_id DESC'
    )->loadObjectList();
}
