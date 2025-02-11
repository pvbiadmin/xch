<?php

namespace Templates\SB_Admin\Tmpl\Footer;

function main()
{
    return <<<HTML
<footer class="py-4 bg-light mt-auto">
    <div class="container-fluid px-4">
        <div class="d-flex align-items-center justify-content-center small">
            <div class="text-muted">Copyright &copy; <script>document.write(new Date().getFullYear());</script> Escudero Animal Raising</div>
        </div>
    </div>
</footer>
HTML;
}