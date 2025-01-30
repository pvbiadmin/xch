<?php

namespace Templates\SB_Admin\Tmpl\Footer;

require_once 'bpl/mods/helpers.php';

use function BPL\Mods\Helpers\session_get;

function main()
{
    return <<<HTML
<footer class="py-4 bg-light mt-auto">
    <div class="container-fluid px-4">
        <div class="d-flex align-items-center justify-content-between small">
            <div class="text-muted">Copyright &copy; Your Website <script>document.write(new Date().getFullYear());</script></div>
            <div>
                <a href="#">Privacy Policy</a>
                &middot;
                <a href="#">Terms &amp; Conditions</a>
            </div>
        </div>
    </div>
</footer>
HTML;
}