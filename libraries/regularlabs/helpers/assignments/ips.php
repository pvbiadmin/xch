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

if (is_file(JPATH_LIBRARIES . '/regularlabs/autoload.php'))
{
    require_once JPATH_LIBRARIES . '/regularlabs/autoload.php';
}

require_once dirname(__FILE__, 2) . '/assignment.php';

class RLAssignmentsIPs extends RLAssignment
{
    public function passIPs()
    {
        if (is_array($this->selection))
        {
            $this->selection = implode(',', $this->selection);
        }

        $this->selection = explode(',', str_replace([' ', "\r", "\n"], ['', '', ','], $this->selection));

        $pass = $this->checkIPList();

        return $this->pass($pass);
    }

    private function checkIP($range)
    {
        if (empty($range))
        {
            return false;
        }

        if (strpos($range, '-') !== false)
        {
            // Selection is an IP range
            return $this->checkIPRange($range);
        }

        // Selection is a single IP (part)
        return $this->checkIPPart($range);
    }

    private function checkIPList()
    {
        foreach ($this->selection as $range)
        {
            // Check next range if this one doesn't match
            if ( ! $this->checkIP($range))
            {
                continue;
            }

            // Match found, so return true!
            return true;
        }

        // No matches found, so return false
        return false;
    }

    private function checkIPPart($range)
    {
        $ip = $_SERVER['REMOTE_ADDR'];

        // Return if no IP address can be found (shouldn't happen, but who knows)
        if (empty($ip))
        {
            return false;
        }

        $ip_parts    = explode('.', $ip);
        $range_parts = explode('.', trim($range));

        // Trim the IP to the part length of the range
        $ip = implode('.', array_slice($ip_parts, 0, count($range_parts)));

        // Return false if ip does not match the range
        if ($range != $ip)
        {
            return false;
        }

        return true;
    }

    /* Fill the max range by prefixing it with the missing parts from the min range
     * So 101.102.103.104-201.202 becomes:
     * max: 101.102.201.202
     */

    private function checkIPRange($range)
    {
        $ip = $_SERVER['REMOTE_ADDR'];

        // Return if no IP address can be found (shouldn't happen, but who knows)
        if (empty($ip))
        {
            return false;
        }

        // check if IP is between or equal to the from and to IP range
        [$min, $max] = explode('-', trim($range), 2);

        // Return false if IP is smaller than the range start
        if ($ip < trim($min))
        {
            return false;
        }

        $max = $this->fillMaxRange($max, $min);

        // Return false if IP is larger than the range end
        if ($ip > trim($max))
        {
            return false;
        }

        return true;
    }

    private function fillMaxRange($max, $min)
    {
        $max_parts = explode('.', $max);

        if (count() == 4)
        {
            return $max;
        }

        $min_parts = explode('.', $min);

        $prefix = array_slice($min_parts, 0, count($min_parts) - count($max_parts));

        return implode('.', $prefix) . '.' . implode('.', $max_parts);
    }
}
