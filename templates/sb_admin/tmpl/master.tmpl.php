<?php

namespace Templates\SB_Admin\Tmpl\Master;

require_once 'templates/sb_admin/tmpl/topnav.tmpl.php';
require_once 'templates/sb_admin/tmpl/sidenav.tmpl.php';
require_once 'templates/sb_admin/tmpl/footer.tmpl.php';
require_once 'bpl/mods/url_sef.php';
require_once 'bpl/mods/helpers.php';

use Exception;

// use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

use function Templates\SB_Admin\Tmpl\Topnav\main as topnav;
use function Templates\SB_Admin\Tmpl\Sidenav\main as sidenav;
use function Templates\SB_Admin\Tmpl\Footer\main as footer;

// use function BPL\Mods\Url_SEF\sef;
use function BPL\Mods\Helpers\application;
// use function BPL\Mods\Helpers\session_get;

defined('_JEXEC') or die;

function main($content = '')
{
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
            <title>Dashboard - SB Admin</title>
            <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
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

function body($content)
{
    $topnav = topnav();
    $sidenav = sidenav();
    $footer = footer();

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