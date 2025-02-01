<?php

namespace Templates\SB_Admin\Tmpl\Footer;

function main()
{
    return <<<HTML
<footer class="py-4 bg-light mt-auto">
    <div class="container-fluid px-4">
        <div class="d-flex align-items-center justify-content-center small">
            <div class="text-muted">Copyright &copy; Your Website <script>document.write(new Date().getFullYear());</script></div>
        </div>
    </div>
</footer>
HTML;
}