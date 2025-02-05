<?php

namespace BPL\Jumi\Fast_Track_Copy2;

require_once 'templates/sb_admin/tmpl/master.tmpl.php';
require_once 'bpl/ajax/ajaxer/fast_track_input.php';
require_once 'bpl/ajax/ajaxer/fast_track.php';
require_once 'bpl/mods/time_remaining.php';
require_once 'bpl/ajax/ajaxer/table_fast_track.php';
require_once 'bpl/mods/time_remaining.php';
// require_once 'bpl/mods/table_daily_interest.php';
require_once 'bpl/mods/helpers.php';

// use Exception;

use DateTime;
use DateInterval;

use function BPL\Ajax\Ajaxer\Fast_Track_Input\main as fast_track_input;
use function BPL\Ajax\Ajaxer\Fast_Track\main as ajax_fast_track;
use function BPL\Ajax\Ajaxer\Table_Fast_Track\main as ajax_table_fast_track;
use function BPL\Echelon_Bonus\view;
use function BPL\Mods\Time_Remaining\main as time_remaining;
// use function BPL\Mods\Table_Daily_Interest\tbody;

use function Templates\SB_Admin\Tmpl\Master\main as master;

use function BPL\Mods\Url_SEF\qs;
use function BPL\Mods\Url_SEF\sef;

use function BPL\Mods\Helpers\session_get;
// use function BPL\Mods\Helpers\input_get;
use function BPL\Mods\Helpers\page_validate;
// use function BPL\Mods\Helpers\menu;
use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\settings;
use function BPL\Mods\Helpers\user;
use function BPL\Mods\Helpers\pgn8;
use function BPL\Mods\Helpers\live_reload;

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
    // $page = substr(input_get('page'), 0, 3);

    page_validate();

    // $str = menu();

    // $str = live_reload(true, 5000);

    $notifications = notifications();
    $view_form = view_form();
    $view_shares = view_shares($user_id);
    $view_principal = view_principal($user_id);
    $view_tbl_fast_track = view_tbl_fast_track($user_id, false);

    $str = <<<HTML
	<div class="container-fluid px-4">
		<h1 class="mt-4">Passive Income</h1>
		<ol class="breadcrumb mb-4">
			<li class="breadcrumb-item active">Add Stakes</li>
		</ol>
		$notifications
		<div class="row">
			$view_form
			$view_shares
			$view_principal
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
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 5000);
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
                <div class="card-body">Add Stakes</div>
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
				<div class="card-body">Shares</div>
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
				<div class="card-body">Value</div>
				<div class="card-footer d-flex align-items-center justify-content-between">
					<span class="fast_track_principal">$value</span>
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
				List Stakes{$counter_span}
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
				<th>Initial</th>
				<th>Accumulated</th>
				<th>Running Days</th>
				<th>Maturity Date ($maturity days)</th>
				<th>Status</th>							
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Initial</th>
				<th>Accumulated</th>
				<th>Running Days</th>
				<th>Maturity Date ($maturity days)</th>
				<th>Status</th>
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

    if (empty($fast_tracks)) {
        $str .= <<<HTML
			<tr>
				<td>0.00</td>
				<td>0.00</td>
				<td>0</td>
				<td>n/a</td>
				<td>n/a</td>				
			</tr>					
		HTML;
    } else {
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
    }

    return $str;
}

/**
 * @param $user_id
 *
 * @return string
 *
 * @since version
 */
function fast_track($user_id): string
{
    $currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;

    $sa = settings('ancillaries');
    $se = settings('entry');
    $sp = settings('plans');

    $user_fast_track = user_fast_track($user_id);

    $value_last = 0;

    foreach ($user_fast_track as $fast_track) {
        $value_last += $fast_track->value_last;
    }

    $user = user($user_id);

    $currency = settings('ancillaries')->currency;

    $str = css_tbl_fast_track();
    $str .= js_table_fast_track();

    if ($user->account_type !== 'starter') {
        $package_name = $se->{$user->account_type . '_package_name'};

        // header and points balance
        $str .= '<h2>' . $package_name . ' ' . $sp->fast_track_name .
            '<span style="float: right; font-size: x-large; font-weight: bold">
            <span style="color: green">' . $sa->efund_name . ' Balance: '
            /*'Points: '*/ . '</span><span class="usd_bal_now_user">' .
            number_format($user->payout_transfer /*$user->points*/ , 2) . ' ' . $currency .
            '</span></h2>';

        // wallet button
        $str .= !0 ? '' : '<span style="float: right; font-size: x-large; font-weight: bold"><span style="float: right">
	        <a href="' . sef(20) . '" class="uk-button uk-button-primary">Wallet</a></span></span>';

        $shares = number_format($value_last, 2);
        $value = number_format($user->fast_track_principal, 2);

        $str .= <<<HTML
            <!-- Notification Handler -->
            <div class="notification-container">
                <div class="notification success_fast_track"></div>
                <div class="notification error_fast_track"></div>
                <div class="notification debug_fast_track"></div>
            </div>
            <div class="card-container">
                <!-- Card for Fast Track Input (Switched Position) -->
                <div class="card">
                    <div class="card-header">Fast Track</div>
                    <div class="card-content">
                        <input type="text" id="fast_track_input" placeholder="Enter value" class="input-field">
                        <button id="fast_track" class="btn-primary" disabled>{$sp->fast_track_name}</button>
                    </div>
                </div>

                <!-- Card for Shares -->
                <div class="card">
                    <div class="card-header">Shares</div>
                    <div class="card-content">
                        <span class="fast_track_value_last" style="color: #444444; font-size: x-large;">{$shares}</span>
                    </div>
                </div>

                <!-- Card for Value (Switched Position) -->
                <div class="card">
                    <div class="card-header">Value</div>
                    <div class="card-content">
                        <span class="fast_track_principal" style="color: #444444; font-size: x-large;">{$value}</span>
                    </div>
                </div>                
            </div>
        HTML;

        $str .= '<div id="table_fast_track">' . table_fast_track($user_id, $currentPage) . '</div>';

        $str .= fast_track_input($user_id);
        $str .= ajax_fast_track($user_id);
        $str .= ajax_table_fast_track($user_id);
    }

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

/**
 * @param        $user_id
 * @param        $page
 *
 * @return string
 *
 * @since version
 */
function table_fast_track($user_id, $page): string
{
    $settings_investment = settings('investment');

    $account_type = user($user_id)->account_type;

    $interval = $settings_investment->{$account_type . '_fast_track_interval'};
    $maturity = $settings_investment->{$account_type . '_fast_track_maturity'};

    $str = '';

    $fast_tracks = user_fast_track($user_id);

    $pagination = pgn8($fast_tracks, sef(19), qs(), $page);

    $offset = $pagination['offset'];
    $limit = $pagination['limit'];
    $nav_pg = $pagination['html'];

    $paginated_fast_tracks = array_slice($fast_tracks, $offset, $limit, true);

    if (!empty($paginated_fast_tracks)) {
        $str .= <<<HTML
        <div class="card-container">
            <div class="table-responsive" style="background: white">
                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th style="text-align: center;"><h4>Initial</h4></th>
                            <th style="text-align: center;"><h4>Accumulated</h4></th>
                            <th style="text-align: center;"><h4>Running Days</h4></th>
                            <th style="text-align: center;"><h4>Maturity Date ($maturity days)</h4></th>
                            <th style="text-align: center;"><h4>Status</h4></th>
                        </tr>
                    </thead>
                    <tbody>
        HTML;

        foreach ($paginated_fast_tracks as $fs) {
            $start = new DateTime('@' . $fs->date_entry);
            $end = new DateInterval('P' . $maturity . 'D');

            $start->add($end);

            $starting_value = number_format($fs->principal, 2);
            $current_value = number_format($fs->value_last, 2);
            $maturity_date = $start->format('F d, Y');
            $status = time_remaining($fs->day, $fs->processing, $interval, $maturity);

            $remaining = ($fs->processing + $maturity - $fs->day) * $interval;
            $remain_maturity = ($maturity - $fs->day) * $interval;

            $type_day = '';

            if ($remaining > $maturity && $fs->processing) {
                $type_day = 'Days for Processing: ';
            } elseif ($remain_maturity > 0) {
                $type_day = 'Days Remaining: ';
            }

            $str .= <<<HTML
                <tr>
                    <td style="text-align: center;">{$starting_value}</td>
                    <td style="text-align: center;">{$current_value}</td>
                    <td style="text-align: center;">{$fs->day}</td>
                    <td style="text-align: center;">{$maturity_date}</td>
                    <td style="text-align: center;">{$type_day}{$status}</td>
                </tr>
            HTML;
        }

        $str .= <<<HTML
            <tr style="visibility: hidden;">
                <td colspan="5"></td>
            </tr>
        HTML;

        $str .= <<<HTML
            </tbody>
        </table>
        </div>
        HTML;

        $str .= $nav_pg;

        $str .= <<<HTML
            </div>
        HTML;
    }

    return $str;
}

function css_tbl_fast_track()
{
    $str = <<<CSS
        <style>
            /* Card Container */
            .card-container {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                padding: 15px;
                justify-content: center;
            }

            /* Card Styling */
            .card {
                flex: 1 1 calc(33.333% - 30px); /* Three cards per row on desktop */
                background-color: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                padding: 15px;
                text-align: center;
                transition: transform 0.2s;
            }

            .card:hover {
                transform: translateY(-5px);
            }

            .card-header {
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 10px;
                color: #006600;
            }

            .card-content {
                font-size: 16px;
            }

            /* Input Field */
            .input-field {
                width: 100%;
                padding: 10px;
                font-size: 16px;
                border: 1px solid #ddd;
                border-radius: 4px;
                margin-bottom: 10px;
            }

            /* Button */
            .btn-primary {
                width: 100%;
                padding: 10px;
                font-size: 16px;
                background-color: #006600;
                color: #fff;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }

            .btn-primary:disabled {
                background-color: #ccc;
                cursor: not-allowed;
            }

            /* Notification Container */
            .notification-container {
                margin-top: 20px;
                padding: 15px;
            }

            .notification {
                padding: 10px;
                margin-bottom: 10px;
                border-radius: 4px;
                display: none; /* Hidden by default */
            }

            .success_fast_track {
                background-color: #d4edda;
                color: #155724;
            }

            .error_fast_track {
                background-color: #f8d7da;
                color: #721c24;
            }

            .debug_fast_track {
                background-color: #fff3cd;
                color: #856404;
            }

            /* Responsive Design */
            @media (max-width: 768px) {
                .card {
                    flex: 1 1 calc(50% - 30px); /* Two cards per row on tablets */
                }
            }

            @media (max-width: 480px) {
                .card {
                    flex: 1 1 100%; /* One card per row on mobile */
                }
            }
        </style>
    CSS;

    return $str;
}

function js_table_fast_track()
{
    $str = <<<JS
        <script>                
            function showNotification(type, message) {
                const notification = document.querySelector(`.\${type}`);
                notification.textContent = message;
                notification.style.display = 'block';
                
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 5000);
            }                
        </script>
    JS;

    return $str;
}
