<?php

namespace Templates\SB_Admin\Tmpl\Topnav;

require_once 'bpl/mods/helpers.php';
require_once 'bpl/mods/url_sef.php';

use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\user;
use function BPL\Mods\Url_SEF\sef;

function main()
{
    $user_id = session_get('user_id');

    if (!$user_id) {
        return '';
    }

    $user = user($user_id);

    $usertype = $user->usertype;

    $activity_link = sef(3);
    $logout_link = sef(41);
    $home_link = sef(43);
    $profile_link = sef(44);

    $member_menu_name = 'Member';

    if ($usertype === 'Admin') {
        $member_menu_name = 'Admin';
    }

    return <<<HTML
<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <!-- Navbar Brand-->
    <a class="navbar-brand ps-3" href="$home_link"><img src="images/escudero2.png" /></a>
    <!-- Sidebar Toggle-->
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
    <!-- Navbar Search-->
    <div class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
        <!-- <div class="input-group">
            <input class="form-control" type="text" placeholder="Search for..." aria-label="Search for..." aria-describedby="btnNavbarSearch" />
            <button class="btn btn-primary" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
        </div> -->
    </div>
    <!-- Navbar-->
    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" id="navbarDropdown" href="javascript:void(0);" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user fa-fw"></i></a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <li><a class="dropdown-item" href="{$profile_link}">$member_menu_name</a></li>
                <!-- li><a class="dropdown-item" href="{$activity_link}">Activity Log</a></li> -->
                <li><hr class="dropdown-divider" /></li>
                <li><a class="dropdown-item" href="{$logout_link}">Logout</a></li>
            </ul>
        </li>
    </ul>
</nav>
HTML;
}

