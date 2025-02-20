<?php

namespace Templates\SB_Admin\Tmpl\Error403;

require_once 'templates/sb_admin/tmpl/footer.tmpl.php';

use function Templates\SB_Admin\Tmpl\Footer\main as footer;

defined('_JEXEC') or die;

function main($message, $error_code = '403')
{
    $error_title = 'Access Forbidden';
    if ($error_code === '403') {
        $error_title = 'Access Forbidden';
    } elseif ($error_code === '429') {
        $error_title = 'Too Many Requests';
    }

    $footer = footer();

    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>{$error_title} - SB Admin</title>
        <link href="templates/sb_admin/css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    </head>
    <body>
        <div id="layoutError">
            <div id="layoutError_content">
                <main>
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-lg-6">
                                <div class="text-center mt-4">
                                    <h1 class="display-1">{$error_code}</h1>
                                    <p class="lead">{$message}</p>                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
            <div id="layoutError_footer">
            {$footer}
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="templates/sb_admin/js/scripts.js"></script>
    </body>
</html>
HTML;
    exit; // Stop further execution
}