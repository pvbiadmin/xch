<?php
/**
 * @package         Regular Labs Library
 * @version         23.9.3039
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            https://regularlabs.com
 * @copyright       Copyright © 2023 Regular Labs All Rights Reserved
 * @license         GNU General Public License version 2 or later
 */

/* @DEPRECATED */

defined('_JEXEC') or die;

use RegularLabs\Library\Cache as RL_Cache;

if (is_file(JPATH_LIBRARIES . '/regularlabs/autoload.php'))
{
    require_once JPATH_LIBRARIES . '/regularlabs/autoload.php';
}

class RLCache
{
    static $cache = [];

    public static function get($id)
    {
        return RL_Cache::get($id);
    }

    public static function has($id)
    {
        return RL_Cache::has($id);
    }

    public static function read($id)
    {
        return RL_Cache::read($id);
    }

    public static function set($id, $data)
    {
        return RL_Cache::set($id, $data);
    }

    public static function write($id, $data, $ttl = 0)
    {
        return RL_Cache::write($id, $data, $ttl);
    }
}
