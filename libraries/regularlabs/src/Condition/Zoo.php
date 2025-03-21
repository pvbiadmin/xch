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

namespace RegularLabs\Library\Condition;

defined('_JEXEC') or die;

use Joomla\CMS\Factory as JFactory;
use RegularLabs\Library\Condition;
use RegularLabs\Library\ConditionContent;

/**
 * Class Zoo
 *
 * @package RegularLabs\Library\Condition
 */
abstract class Zoo extends Condition
{
    use ConditionContent;

    public function getItem($fields = [])
    {
        $query = $this->db->getQuery(true)
            ->select($fields)
            ->from('#__zoo_item')
            ->where('id = ' . (int) $this->request->id);
        $this->db->setQuery($query);

        return $this->db->loadObject();
    }

    public function initRequest(&$request)
    {
        $request->view = $request->task ?: $request->view;

        switch ($request->view)
        {
            case 'item':
                $request->idname = 'item_id';
                break;
            case 'category':
                $request->idname = 'category_id';
                break;
        }

        if ( ! isset($request->idname))
        {
            $request->idname = '';
        }

        switch ($request->idname)
        {
            case 'item_id':
                $request->view = 'item';
                break;
            case 'category_id':
                $request->view = 'category';
                break;
        }

        $request->id = JFactory::getApplication()->input->getInt($request->idname, 0);

        if ($request->id)
        {
            return;
        }

        $menu = JFactory::getApplication()->getMenu()->getItem((int) $request->Itemid);

        if (empty($menu))
        {
            return;
        }

        $request->id = $menu->getParams()->get('item_id', 0);
    }

}
