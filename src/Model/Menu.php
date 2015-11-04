<?php

/**
 * This model handle CMS Menus
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Model;

use Nails\Factory;
use Nails\Common\Model\Base;

class Menu extends Base
{
    private $oDb;

    // --------------------------------------------------------------------------

    /**
     * Constructs the model
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $this->oDb = Factory::service('Database');

        // --------------------------------------------------------------------------

        $this->table             = NAILS_DB_PREFIX . 'cms_menu';
        $this->tablePrefix       = 'm';
        $this->table_item        = NAILS_DB_PREFIX . 'cms_menu_item';
        $this->table_item_prefix = 'mi';
    }

    // --------------------------------------------------------------------------

    /**
     * This method applies the conditionals which are common across the get_*()
     * methods and the count() method.
     * @param array  $data    Data passed from the calling method
     * @param string $_caller The name of the calling method
     * @return void
     **/
    protected function _getcount_common($data = array(), $_caller = null)
    {
        $this->oDb->select($this->tablePrefix . '.*,u.first_name,u.last_name,u.profile_img,u.gender,ue.email');
        $this->oDb->join(
            NAILS_DB_PREFIX . 'user u',
            $this->tablePrefix . '.modified_by = u.id',
            'LEFT'
        );
        $this->oDb->join(
            NAILS_DB_PREFIX . 'user_email ue',
            $this->tablePrefix . '.modified_by = ue.user_id AND ue.is_primary = 1',
            'LEFT'
        );

        // --------------------------------------------------------------------------

        if (!empty($data['keywords'])) {

            if (empty($data['or_like'])) {

                $data['or_like'] = array();
            }

            $data['or_like'][] = array(
                'column' => $this->tablePrefix . '.label',
                'value'  => $data['keywords']
            );
            $data['or_like'][] = array(
                'column' => $this->tablePrefix . '.description',
                'value'  => $data['keywords']
            );
        }

        parent::_getcount_common($data, $_caller);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a menu object
     * @param  stdClass &$object The menu object to format
     * @return void
     */
    protected function _format_object(&$object, $data = array())
    {
        parent::_format_object($object, $data);

        $temp              = new stdClass();
        $temp->id          = (int) $object->modified_by;
        $temp->email       = $object->email;
        $temp->first_name  = $object->first_name;
        $temp->last_name   = $object->last_name;
        $temp->gender      = $object->gender;
        $temp->profile_img = $object->profile_img ? (int) $object->profile_img : null;

        $object->modified_by = $temp;

        unset($object->email);
        unset($object->first_name);
        unset($object->last_name);
        unset($object->gender);
        unset($object->profile_img);

        // --------------------------------------------------------------------------

        $nestMenuItems = !empty($data['nestItems']) ? true : false;
        $object->items = $this->getMenuItems($object->id, $nestMenuItems);
    }

    // --------------------------------------------------------------------------

    /**
     * Gets the items of an individual menu
     * @param  int     $menuId The Menu's ID
     * @param  boolean $nested Whether to nest the menu items or not
     * @return array
     */
    public function getMenuItems($menuId, $nested = false)
    {
        $this->oDb->where('menu_id', $menuId);
        $this->oDb->order_by('order');
        $items = $this->oDb->get($this->table_item)->result();

        foreach ($items as $i) {

            $this->_format_object_item($i);
        }

        if ($nested) {

            $items = $this->nestItems($items);
        }

        return $items;
    }

    // --------------------------------------------------------------------------

    /**
     * Format a menu item
     * @param  stdClass &$obj The menu item to format
     * @return voud
     */
    protected function _format_object_item(&$obj)
    {
        parent::_format_object($obj);

        // --------------------------------------------------------------------------

        $obj->page_id = $obj->page_id ? (int) $obj->page_id : null;

        //  If the menu is tied to a page then fetch that page's URL
        if ($obj->page_id) {

            $oPageModel = Factory::model('Page', 'nailsapp/module-cms');
            $obj->pageUrl = $oPageModel->getUrl($obj->page_id);

        } else {

            $obj->pageUrl = null;
        }

        // --------------------------------------------------------------------------

        unset($obj->menu_id);
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a new object
     * @param  array   $data         The data to create the object with
     * @param  boolean $returnObject Whether to return just the new ID or the full object
     * @return mixed
     */
    public function create($data = array(), $returnObject = false)
    {
        $this->oDb->trans_begin();

        if (isset($data['items'])) {

            $items = $data['items'];
            unset($data['items']);
        }

        $data['slug'] = $this->_generate_slug($data['label']);

        $result = parent::create($data, $returnObject);

        if ($result && $items) {

            if ($returnObject) {

                $menuId = $result->id;

            } else {

                $menuId = $result;
            }

            /**
             * Take a note of the table prefixes, we're swapping them quickly while
             * we do this update, so we can leverage the parent methods for the items.
             */

            $table       = $this->table;
            $tablePrefix = $this->tablePrefix;

            $this->table        = $this->table_item;
            $this->tablePrefix = $this->table_item_prefix;

            $newIds  = array();
            $counter = 0;

            foreach ($items as $item) {

                $data            = array();
                $data['menu_id'] = $menuId;
                $data['page_id'] = !empty($item['page_id']) ? $item['page_id'] : null;
                $data['url']     = !empty($item['url']) ? $item['url'] : null;
                $data['label']   = !empty($item['label']) ? $item['label'] : null;
                $data['order']   = $counter;

                /**
                 * Is both a page_id _and_ url set? If so, complain
                 */

                if (!empty($data['page_id']) && !empty($data['url'])) {

                    $this->_set_error('Can only set a URL or a CMS Page for item #' . ($counter+1) . ', not both.');
                    $this->oDb->trans_rollback();
                    return false;
                }

                /**
                 * Look at the parent_id, if it's numerica, then it's an existing menu item,
                 * if not, then it's a new menu item. Non-numerical parents will be processed
                 * _after_ their parents, so we can assume that the paren'ts [newly created]
                 * ID is in $newIds - if it's not, then bugger,
                 */

                if (!empty($item['parent_id']) && is_numeric($item['parent_id'])) {

                    $data['parent_id'] = $item['parent_id'];

                } elseif (!empty($item['parent_id'])) {

                    $parentId = $item['parent_id'];
                    $data['parent_id'] = !empty($newIds[$parentId]) ? $newIds[$parentId] : null;

                    if (empty($data['parent_id'])) {

                        $this->_set_error('Failed to determine the parent item of item #' . ($counter+1));
                        $this->oDb->trans_rollback();
                        return false;
                    }
                }

                $result = parent::create($data);

                if (!$result) {

                    $this->_set_error('Failed to create item #' . ($counter+1));
                    $this->oDb->trans_rollback();
                    return false;

                } else {

                    $newIds[$item['id']] = $result;
                }

                $counter++;
            }

            //  Reset the table and table prefix
            $this->table       = $table;
            $this->tablePrefix = $tablePrefix;

            //  Commit the transaction
            $this->oDb->trans_commit();

        } elseif ($result) {

            $this->oDb->trans_commit();

        } else {

            $this->oDb->trans_rollback();
        }

        return $result;
    }

    // --------------------------------------------------------------------------

    /**
     * Updates an existing object
     * @todo Add transactions
     * @param int      $id   The ID of the object to update
     * @param array    $data The data to update the object with
     * @return boolean
     **/
    public function update($id, $data = array())
    {
        $this->oDb->trans_begin();

        if (isset($data['items'])) {

            $items = $data['items'];
            unset($data['items']);
        }

        $data['slug'] = $this->_generate_slug($data['label'], '', '', null, null, $id);

        $result = parent::update($id, $data);

        if ($result && $items) {

            /**
             * Take a note of the table prefixes, we're swapping them quickly while
             * we do this update, so we can leverage the parent methods for the items.
             */

            $table       = $this->table;
            $tablePrefix = $this->tablePrefix;

            $this->table       = $this->table_item;
            $this->tablePrefix = $this->table_item_prefix;

            $idsUpdated = array();
            $newIds     = array();
            $counter    = 0;

            foreach ($items as $item) {

                $data            = array();
                $data['page_id'] = !empty($item['page_id']) ? $item['page_id'] : null;
                $data['url']     = !empty($item['url']) ? $item['url'] : null;
                $data['label']   = !empty($item['label']) ? $item['label'] : null;
                $data['order']   = $counter;

                /**
                 * Is both a page_id _and_ url set? If so, complain
                 */

                if (!empty($data['page_id']) && !empty($data['url'])) {

                    $this->_set_error('Can only set a URL or a CMS Page for item #' . ($counter+1) . ', not both.');
                    $this->oDb->trans_rollback();
                    return false;
                }

                /**
                 * Look at the parent_id, if it's numerical, then it's an existing menu item,
                 * if not, then it's a new menu item. Non-numerical parents will be processed
                 * _after_ their parents, so we can assume that the parent's [newly created]
                 * ID is in $newIds - if it's not, then bugger,
                 */

                if (!empty($item['parent_id']) && is_numeric($item['parent_id'])) {

                    $data['parent_id'] = $item['parent_id'];

                } elseif (!empty($item['parent_id'])) {

                    $parentId = $item['parent_id'];
                    $data['parent_id'] = !empty($newIds[$parentId]) ? $newIds[$parentId] : null;

                    if (empty($data['parent_id'])) {

                        $this->_set_error('Failed to determine the parent item of item #' . ($counter+1));
                        $this->oDb->trans_rollback();
                        return false;
                    }

                } else {

                    $data['parent_id'] = null;
                }

                /**
                 * Look at the ID, is it numerical? If so it's an existing menu item, if
                 * not it's a new item - create it and remember it's ID (in case it has
                 * any kiddy winkles).
                 */

                //  Update or create? If create remember and save ID
                if (!empty($item['id']) && is_numeric($item['id'])) {

                    $result = parent::update($item['id'], $data);

                    if (!$result) {

                        $this->_set_error('Failed to update item #' . ($counter+1));
                        $this->oDb->trans_rollback();
                        return false;

                    } else {

                        $idsUpdated[] = $item['id'];
                    }

                } else {

                    $data['menu_id'] = $id;
                    $result = parent::create($data);

                    if (!$result) {

                        $this->_set_error('Failed to create item #' . ($counter+1));
                        $this->oDb->trans_rollback();
                        return false;

                    } else {

                        $idsUpdated[]        = $result;
                        $newIds[$item['id']] = $result;
                    }
                }

                $counter++;
            }

            //  Remove any items which weren't updated or created
            $idsUpdated = array_filter($idsUpdated);
            $idsUpdated = array_unique($idsUpdated);

            if ($idsUpdated) {

                $this->oDb->where('menu_id', $id);
                $this->oDb->where_not_in('id', $idsUpdated);
                $this->oDb->delete($this->table);
            }

            //  Reset the table and table prefix
            $this->table       = $table;
            $this->tablePrefix = $tablePrefix;

            //  Commit the transaction
            $this->oDb->trans_commit();

        } elseif ($result) {

            $this->oDb->trans_commit();

        } else {

            $this->oDb->trans_rollback();
        }

        return $result;
    }

    // --------------------------------------------------------------------------

    /**
     * Nests menu items
     * Hat tip to Timur; http://stackoverflow.com/a/9224696/789224
     * @param  stdClass &$list  The list to nest
     * @param  int      $parent The parent list item's ID
     * @return array
     */
    protected function nestItems(&$list, $parent = null)
    {
        $result = array();

        for ($i = 0, $c = count($list); $i < $c; $i++) {

            if ($list[$i]->parent_id == $parent) {

                $list[$i]->children = $this->nestItems($list, $list[$i]->id);
                $result[]           = $list[$i];
            }
        }

        return $result;
    }
}
