<?php

namespace Templates\SB_Admin\Tmpl\Master;

require_once 'templates/sb_admin/tmpl/topnav.tmpl.php';
require_once 'templates/sb_admin/tmpl/sidenav.tmpl.php';
require_once 'templates/sb_admin/tmpl/footer.tmpl.php';
require_once 'bpl/mods/url_sef.php';
require_once 'bpl/mods/helpers.php';

use Exception;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

use function Templates\SB_Admin\Tmpl\Topnav\main as topnav;
use function Templates\SB_Admin\Tmpl\Sidenav\main as sidenav;
use function Templates\SB_Admin\Tmpl\Footer\main as footer;

use function BPL\Mods\Url_SEF\sef;
use function BPL\Mods\Helpers\application;
use function BPL\Mods\Helpers\session_get;

defined('_JEXEC') or die;

function main($content = '')
{
    $user_id = session_get('user_id');

    $head_matter = <<<HTML
        <title>Dashboard - SB Admin</title>
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="templates/sb_admin/css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    HTML;

    if (!$user_id) {
        $head_matter = <<<HTML
        <title>Login - SB Admin</title>
        <link href="templates/sb_admin/css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    HTML;
    }

    try {
        // Get the Joomla application instance
        $app = application();

        // Set the MIME type and document type
        $document = Factory::getDocument();
        $document->setMimeEncoding('text/html');
        $document->setType('html');

        $body = body($content);

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
            {$head_matter}
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

function body($content)
{
    $user_id = session_get('user_id');

    $topnav = topnav();
    $sidenav = sidenav();
    $view_login = view_login();
    $footer = footer();

    if (!$user_id) {
        return <<<HTML
<body class="bg-primary">
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-5">
                            {$view_login}
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

    return <<<HTML
    <body class="sb-nav-fixed">
        {$topnav}
        <div id="layoutSidenav">
            {$sidenav}
            <div id="layoutSidenav_content">
                <main>
                    {$content}
                </main>
                {$footer}
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="templates/sb_admin/js/scripts.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
        <script src="templates/sb_admin/assets/demo/chart-area-demo.js"></script>
        <script src="templates/sb_admin/assets/demo/chart-bar-demo.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
        <script src="templates/sb_admin/js/datatables-simple-demo.js"></script>
    </body>
    HTML;
}

function view_login()
{
    // Initialize the application
    $app = application();

    // Display Joomla messages as dismissible alerts
    $messages = $app->getMessageQueue(true);
    $notification_str = ''; // Initialize the notification string

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

    // Add notifications to the login form
    $str = $notification_str;

    // Clear localStorage (your existing code)
    $str .= <<<HTML
<script>
    // Clear all items from localStorage
    localStorage.clear();
    
    // For backwards compatibility, explicitly remove known items
    localStorage.removeItem('counter');
</script>
HTML;

    // Generate form token
    $form_token = HTMLHelper::_('form.token');

    $registration_link = sef(144);

    // Build the login form
    $str .= <<<HTML
<div class="card shadow-lg border-0 rounded-lg mt-5">
    <div class="card-header"><h3 class="text-center font-weight-light my-4">Login</h3></div>
    <div class="card-body">
        <form method="post">
            {$form_token}
            <div class="form-floating mb-3">
                <input class="form-control" id="inputUsername" type="text" name="username" placeholder="Username" />
                <label for="inputUsername">Username</label>
            </div>
            <div class="form-floating mb-3">
                <input class="form-control" id="inputPassword" type="password" name="password" placeholder="Password" />
                <label for="inputPassword">Password</label>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                <button class="btn btn-primary" type="submit">Login</button>
            </div>
        </form>
    </div>
    <div class="card-footer text-center py-3">
        <div class="small"><a href="{$registration_link}">Need an account? Sign up!</a></div>
    </div>
</div>
HTML;

    return $str;
}