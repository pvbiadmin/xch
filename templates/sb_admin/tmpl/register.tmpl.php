<?php

namespace Templates\SB_Admin\Tmpl\Registration;

require_once 'templates/sb_admin/tmpl/footer.tmpl.php';
require_once 'bpl/mods/url_sef.php';
require_once 'bpl/mods/helpers.php';

require_once 'bpl/mods/ajax.php';

require_once 'bpl/mods/terms.php';

use Exception;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

use function Templates\SB_Admin\Tmpl\Footer\main as footer;
use function BPL\Mods\Url_SEF\sef;
use function BPL\Mods\Helpers\application;

use function BPL\Mods\Ajax\check_input;
use function BPL\Mods\Ajax\check_position;

use function BPL\Mods\Helpers\user;
use function BPL\Mods\Helpers\settings;
use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\input_get;

use function BPL\Mods\Terms\main as terms;

defined('_JEXEC') or die;

function main()
{
    try {
        // Get the Joomla application instance
        $app = application();

        // Set the MIME type and document type
        $document = Factory::getDocument();
        $document->setMimeEncoding('text/html');
        $document->setType('html');

        $body = body();

        // Heredoc for HTML content
        $html = <<<HTML
    <!DOCTYPE html>
    <html lang="en">
        <head>
            <meta charset="utf-8" />
            <meta http-equiv="X-UA-Compatible" content="IE=edge" />
            <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
            <meta name="description" content="" />
            <meta name="author" content="" />
            <title>Escudero</title>
            <link rel="shortcut icon" type="image/x-icon" href="../home/assets/images/x-icon/01.png">
            <link href="templates/sb_admin/css/styles.css" rel="stylesheet" />
            <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
            <!-- jQuery CDN -->
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        </head>
        {$body}
    </html>
    HTML;

        // Output the HTML directly
        echo $html;

        // Close the Joomla application to prevent further processing
        $app->close();

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

function body()
{
    $view_registration = view_registration();
    $footer = footer();

    $backgroundImageUrl = 'templates/sb_admin/assets/img/03.jpg';

    return <<<HTML
<body style="background-image: url('{$backgroundImageUrl}'); background-size: cover; background-position: center; background-repeat: no-repeat;">
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-5">
                            {$view_registration}
                        </div>
                    </div>
                </div>
            </main>
        </div>
        <div id="layoutAuthentication_footer">
        {$footer}
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="templates/sb_admin/js/scripts.js"></script>
</body>
HTML;
}

function view_registration()
{
    $user_id = session_get('user_id');

    $login_link = sef(43);

    $login = <<<HTML
        <div class="card-footer text-center py-3">
            <div class="small"><a href="{$login_link}">Back to home</a></div>
        </div>
        HTML;

    if (!$user_id) {
        $login = <<<HTML
        <div class="card-footer text-center py-3">
            <div class="small"><a href="{$login_link}">Have an account? Go to login</a></div>
        </div>
        HTML;
    }

    // Add notifications to the registration form
    $str = notifications();

    // Generate form token
    $form_token = HTMLHelper::_('form.token');

    // Get settings and session data
    $settings_plans = settings('plans');
    $s_username = session_get('s_username');
    $s_email = session_get('s_email');
    $s_password = session_get('s_password');
    $s_sponsor = session_get('s_sponsor');
    $sponsor = sponsor();
    $date_registered = date_registered();

    // Sponsor field
    $sponsor_field = '';

    if ($settings_plans->direct_referral_fast_track_principal) {
        $sponsor_value = $s_sponsor && !isset($sponsor) ? $s_sponsor : $sponsor;
        $readonly = $s_sponsor !== '' ? ' readonly' : '';
        $sponsor_field = <<<HTML
            <div class="form-group">
                <label for="sponsor">Sponsor Username: *</label>
                <div class="input-group">
                    <input type="text" name="sponsor" id="sponsor" class="form-control" value="$sponsor_value" placeholder="Enter Sponsor Username Here.." required$readonly>
                    <span class="input-group-btn">
                        <button type="button" onClick="checkInput('sponsor')" class="btn btn-default" style="height: 38px;">Check Validity</button>
                    </span>
                </div>
                <div id="sponsorDiv" class="help-block validation-message"></div>
            </div>
HTML;
    }

    // Build the registration form
    $str .= <<<HTML
<div class="card shadow-lg border-0 rounded-lg mt-5">
    <div class="card-header"><h3 class="text-center font-weight-light my-4"><img src="templates/sb_admin/assets/img/logo.png" alt=""></h3></div>
    <div class="card-body">
        <form name="regForm" method="post" enctype="multipart/form-data" onsubmit="submit.disabled = true; return validateForm()">
            {$form_token}
            <p>Please fill up all fields marked *</p>
            <!-- Username Field -->
            <div class="form-group">
                <label for="username">Username: *</label>
                <div class="input-group">
                    <input type="text" name="username" id="username" class="form-control" value="$s_username" placeholder="Enter Username Here.." required>
                    <span class="input-group-btn">
                        <button type="button" onClick="checkInput('username')" class="btn btn-default" style="height: 38px;">Check Availability</button>
                    </span>
                </div>
                <div id="usernameDiv" class="help-block validation-message"></div>
            </div><br>

            <!-- Email Field -->
            <div class="form-group">
                <label for="email">Email: *</label>
                <input type="email" name="email" id="email" class="form-control" value="$s_email" placeholder="Enter Email Here.." required>
            </div><br>

            <!-- Password Fields -->
            <div class="form-group">
                <label for="password1">Password: *</label>
                <input type="password" name="password1" id="password1" class="form-control" value="$s_password" placeholder="Enter Password Here.." required>
            </div><br>

            <div class="form-group">
                <label for="password2">Confirm Password: *</label>
                <input type="password" name="password2" id="password2" class="form-control" value="$s_password" placeholder="Confirm Password Here.." required>
            </div><br>

            <!-- Sponsor Field -->
            {$sponsor_field}<br>

            {$date_registered}<br>

            <!-- Terms and Conditions -->
            <div class="form-group terms">
                <label style="display: flex; align-items: center;">
                    <input type="checkbox" id="terms" required>&nbsp;
                    I Agree to the&nbsp; <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#termsModal">Terms & Conditions</a>
                </label>
            </div><br>

            <!-- Submit Button -->
            <div class="form-group actions">
                <button type="submit" id="register" class="btn btn-primary">Register</button>
            </div>
        </form>
    </div>
    {$login}
</div>
HTML;

    // Append additional functions
    $str .= display_loader();
    $str .= loader_js();
    $str .= terms();
    $str .= check_input();
    $str .= check_position();
    $str .= js();

    return $str;
}

function sponsor(): string
{
    $sid = input_get('s');
    $user_id = session_get('user_id');

    $sponsor = '';

    if ($sid !== '') {
        $sponsor = $sid;
    } elseif ($user_id !== '') {
        $sponsor = user($user_id)->username ?? '';
    }

    return $sponsor;
}

function date_registered()
{
    $admintype = session_get('admintype');

    if ($admintype !== 'Super') {
        return '';
    }

    return <<<HTML
    <div class="form-group">
        <label for="date">Date Registered:</label>
        <input type="date" name="date" id="date" class="form-control">
    </div>
    HTML;
}

function notifications(): string
{
    $app = application();

    // Display Joomla messages as dismissible alerts
    $messages = $app->getMessageQueue(true);
    $notification_str = fade_effect(); // Initialize the notification string

    if (!empty($messages)) {
        foreach ($messages as $message) {
            // Map Joomla message types to Bootstrap alert classes
            $alert_class = '';
            switch ($message['type']) {
                case 'error':
                    $alert_class = 'danger'; // Bootstrap uses 'danger' instead of 'error'
                    break;
                case 'warning':
                    $alert_class = 'warning';
                    break;
                case 'notice':
                    $alert_class = 'info'; // Joomla 'notice' maps to Bootstrap 'info'
                    break;
                case 'message':
                default:
                    $alert_class = 'success'; // Joomla 'message' maps to Bootstrap 'success'
                    break;
            }

            $notification_str .= <<<HTML
            <div class="alert alert-{$alert_class} alert-dismissible fade show mt-5" role="alert">
                {$message['message']}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
HTML;
        }
    }

    return $notification_str;
}

function fade_effect(int $duration = 10000)
{
    return <<<HTML
    <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Select all alert elements
      const alerts = document.querySelectorAll('.alert');

      // Loop through each alert and set a timeout to dismiss it
      alerts.forEach(function (alert) {
        setTimeout(function () {
          // Use Bootstrap's alert method to close the alert
          bootstrap.Alert.getOrCreateInstance(alert).close();
        }, $duration);
      });
    });
  </script>
HTML;
}


function js(): string
{
    return <<<JS
		<script>
			function validateForm() {
				if (document.forms["regForm"]["username"].value === ""
					|| document.forms["regForm"]["password1"].value === ""
					|| document.forms["regForm"]["password2"].value === ""
					|| document.forms["regForm"]["code"].value === ""
					|| document.forms["regForm"]["sponsor"].value === ""
					|| document.forms["regForm"]["upline"].value === "") {
					alert("Please specify all required info.");
					
					return false;
				} else {
					return true;
				}
			}

			function disableMenu() {
				document.getElementById("menu").disabled = true;
			}

			(function ($) {
				$("#register").attr("disabled", true);

				$("#terms").change(function () {
					if (this.checked) {
						$("#register").attr("disabled", false);
					} else {
						$("#register").attr("disabled", true);
					}
					
					return false;
				});
			})(jQuery);
		</script>
	JS;
}

function display_loader(): string
{
    return <<<HTML
        <div id="loader-overlay" style="display: none;">
            <div class="logo">
                <img src="templates/sb_admin/assets/img/logo.png" alt="Logo">
            </div>
        </div>
        <style>
            #loader-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 255, 0.8);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000;
            }

            .logo img {
                width: auto; /* Increased size for the logo */
                height: 60px;
                animation: flash 2s ease-in-out infinite;
            }

            @keyframes flash {
                0%, 100% {
                    opacity: 1;
                }
                50% {
                    opacity: 0;
                }
            }
        </style>
    HTML;
}

function loader_js(): string
{
    return <<<JS
        <script>
            // Configuration options
            const delayEnabled = true; // Set to false to disable the delay
            const delayDuration = 2000; // Delay duration in milliseconds (e.g., 2000ms = 2 seconds)

            function showLoader() {
                // Display the loader overlay
                document.getElementById('loader-overlay').style.display = 'flex';
            }

            function hideLoader() {
                // Hide the loader overlay
                document.getElementById('loader-overlay').style.display = 'none';
            }

            function submitForm() {
                // Show the loader immediately
                showLoader();

                if (delayEnabled) {
                    // Add a delay before submitting the form
                    setTimeout(function () {
                        document.forms['regForm'].submit(); // Submit the form after the delay
                    }, delayDuration);
                } else {
                    // Submit the form immediately
                    document.forms['regForm'].submit();
                }
            }

            // Attach the submitForm function to the form's onsubmit event
            document.forms['regForm'].onsubmit = function (event) {
                event.preventDefault(); // Prevent the form from submitting immediately
                submitForm(); // Call the custom submit function
            };
        </script>
    JS;
}

// function display_loader(): string
// {
//     return <<<HTML
//         <div id="loader-overlay" style="display: none;">
//             <div class="wave">
//                 <div class="ball"></div>
//                 <div class="ball"></div>
//                 <div class="ball"></div>
//                 <div class="ball"></div>
//                 <div class="ball"></div>
//             </div>
//         </div>
//         <style>
//             #loader-overlay {
//                 position: fixed;
//                 top: 0;
//                 left: 0;
//                 width: 100%;
//                 height: 100%;
//                 background: rgba(255, 255, 255, 0.8);
//                 display: flex;
//                 justify-content: center;
//                 align-items: center;
//                 z-index: 1000;
//             }

//             .wave {
//                 display: flex;
//                 justify-content: center;
//                 align-items: center;
//             }

//             .ball {
//                 width: 10px;
//                 height: 10px;
//                 border-radius: 50%;
//                 margin: 0 3px;
//                 background-color: #6c5ce7;
//                 animation: wave 1s ease-in-out infinite;
//             }

//             @keyframes wave {
//                 0% {
//                     transform: translateY(0);
//                 }
//                 50% {
//                     transform: translateY(-5px);
//                 }
//                 100% {
//                     transform: translateY(0);
//                 }
//             }

//             .ball:nth-child(2) {
//                 animation-delay: -0.2s;
//             }

//             .ball:nth-child(3) {
//                 animation-delay: -0.4s;
//             }

//             .ball:nth-child(4) {
//                 animation-delay: -0.6s;
//             }

//             .ball:nth-child(5) {
//                 animation-delay: -0.8s;
//             }
//         </style>
//     HTML;
// }

// function loader_js(): string
// {
//     return <<<JS
//         <script>
//             // Configuration options
//             const delayEnabled = true; // Set to false to disable the delay
//             const delayDuration = 2000; // Delay duration in milliseconds (e.g., 2000ms = 2 seconds)

//             function showLoader() {
//                 // Display the loader overlay
//                 document.getElementById('loader-overlay').style.display = 'flex';
//             }

//             function hideLoader() {
//                 // Hide the loader overlay
//                 document.getElementById('loader-overlay').style.display = 'none';
//             }

//             function submitForm() {
//                 // Show the loader immediately
//                 showLoader();

//                 if (delayEnabled) {
//                     // Add a delay before submitting the form
//                     setTimeout(function () {
//                         document.forms['regForm'].submit(); // Submit the form after the delay
//                     }, delayDuration);
//                 } else {
//                     // Submit the form immediately
//                     document.forms['regForm'].submit();
//                 }
//             }

//             // Attach the submitForm function to the form's onsubmit event
//             document.forms['regForm'].onsubmit = function (event) {
//                 event.preventDefault(); // Prevent the form from submitting immediately
//                 submitForm(); // Call the custom submit function
//             };
//         </script>
//     JS;
// }