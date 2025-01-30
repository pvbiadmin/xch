<?php

namespace BPL\Jumi\Logout;

require_once 'bpl/mods/helpers.php';

use Joomla\CMS\Uri\Uri;

use function BPL\Mods\Url_SEF\sef;
use function BPL\Mods\Helpers\session_set;
use function BPL\Mods\Helpers\application;

// Clear session data
session_set('usertype', '');
session_set('attempts', '');
session_set('user_id', '');
session_set('admintype', '');

// Redirect to the logout page
$redirectUrl = Uri::root(true) . '/' . sef(43);
application()->redirect($redirectUrl);