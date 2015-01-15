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

class NAILS_Cms_menu_model extends NAILS_Model
{
    /**
     * Constructs the model
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $this->_table             = NAILS_DB_PREFIX . 'cms_menu';
        $this->_table_prefix      = 'm';
        $this->_table_item        = NAILS_DB_PREFIX . 'cms_menu_item';
        $this->_table_item_prefix = 'mi';
    }

    // --------------------------------------------------------------------------

    /**
     * Gets all menus
     * @param  boolean $includeMenuItems Whether or not to include the menu items
     * @param  boolean $nested           Whether or not to nest the menu items
     * @return array
     */
    public function get_all($includeMenuItems = false, $nested = true)
    {
        $this->db->select($this->_table_prefix . '.*,u.first_name,u.last_name,u.profile_img,u.gender,ue.email');
        $this->db->join(NAILS_DB_PREFIX . 'user u', $this->_table_prefix . '.modified_by = u.id', 'LEFT');
        $this->db->join(NAILS_DB_PREFIX . 'user_email ue', $this->_table_prefix . '.modified_by = ue.user_id AND ue.is_primary = 1', 'LEFT');
        $menus = parent::get_all();

        foreach ($menus as $m) {

            if ($includeMenuItems) {

                if ($nested) {

                    //  Fetch the nested menu items
                    $m->items = $this->nestItems($this->get_menu_items($m->id));

                } else {

                    //  Fetch the nested menu items
                    $m->items = $this->get_menu_items($m->id);
                }
            }
        }

        // --------------------------------------------------------------------------

        return $menus;
    }

    // --------------------------------------------------------------------------

    /**
     * Fetch an individual menu item by its ID
     * @param  int      $id               The ID of the menu to fetch
     * @param  boolean  $includeMenuItems Whetehr or not to include the menu items
     * @param  boolean  $nested           Whetehr or not to nest the menu items
     * @return stdClass
     */
    public function get_by_id($id, $includeMenuItems = false, $nested = true)
    {
        $this->db->where($this->_table_prefix . '.' . $this->_table_id_column, $id);
        $menu = $this->get_all($includeMenuItems, $nested);

        if (!$menu) {

            return false;
        }

        return $menu[0];
    }

    // --------------------------------------------------------------------------

    /**
     * Fetch an individual menu item by its slug
     * @param  int      $slug             The slug of the menu to fetch
     * @param  boolean  $includeMenuItems Whetehr or not to include the menu items
     * @param  boolean  $nested           Whetehr or not to nest the menu items
     * @return stdClass
     */
    public function get_by_slug($slug, $includeMenuItems = false, $nested = true)
    {
        $this->db->where($this->_table_prefix . '.' . $this->_table_slug_column, $slug);
        $menu = $this->get_all($includeMenuItems, $nested);

        if (!$menu) {

            return false;
        }

        return $menu[0];
    }

    // --------------------------------------------------------------------------

    /**
     * Fetch all menu items for a given menu ID
     * @param  int   $menuId The ID of the menu
     * @return array
     */
    public function get_menu_items($menuId)
    {
        $this->db->where('menu_id', $menuId);
        $this->db->order_by('order');
        $items = $this->db->get($this->_table_item)->result();

        foreach ($items as $i) {

            $this->_format_menu_item($i);
        }

        return $items;
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new menu
     * @param  array $data The data to sue to create the menu
     * @return mixed       ID on success, false on failure
     */
    public function create($data)
    {
        $this->db->trans_begin();

        $this->db->set('created', 'NOW()', false);
        if ($this->user_model->is_logged_in()) {

            $this->db->set('created_by', active_user('id'));
        }

        $this->db->insert($this->_table);

        $id = $this->db->insert_id();

        if ($id) {

            if ($this->update($id, $data, false)) {

                $this->db->trans_commit();
                return $id;

            } else {

                $this->db->trans_rollback();
            }

        } else {

            $this->_set_error('Failed to create menu.');
            $this->db->trans_rollback();
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Update a menu, including menu items
     * @param  int   $id   The ID of the menu to update
     * @param  array $data The data array of updates
     * @return boolean
     */
    public function update($id, $data)
    {
        $menu = $this->get_by_id($id);

        if (!$menu) {

            $this->_set_error('Invalid Menu ID');
            return false;
        }

        // --------------------------------------------------------------------------

        //  Validation
        if (empty($data['label'])) {

            $this->_set_error('"label" is a required field.');
            return false;
        }

        if (empty($data['description'])) {

            $this->_set_error('"description" is a required field.');
            return false;
        }

        // --------------------------------------------------------------------------

        //  start the transaction
        $this->db->trans_begin();

        //  Update the menu itself
        $this->db->set('label', $data['label']);

        //  Decide on a slug, if need be
        if (trim($data['label']) != $menu->label) {

            //  Changed, generate a slug
            $this->db->Set('slug', $this->_generate_slug( trim($data['label'])));
        }
        $this->db->set('description', $data['description']);
        $this->db->set('modified', 'NOW()', false);

        if ($this->user_model->is_logged_in()) {

            $this->db->set('modified_by', active_user('id'));
        }

        $this->db->where('id', $menu->id);

        if ($this->db->update($this->_table)) {

            if (isset($data['menu_item'])) {

                //  All gone, loop new items and update/create
                $idMap = array();
                foreach ($data['menu_item'] as $item) {

                    $data              = new stdClass();
                    $data->order       = !empty($item['order'])   ? $item['order']   : 0;
                    $data->page_id     = !empty($item['page_id']) ? $item['page_id'] : null;
                    $data->url         = !empty($item['url'])     ? $item['url']     : null;
                    $data->label       = !empty($item['label'])   ? $item['label']   : null;
                    $data->modified    = date('Y-m-d H:i:s');
                    $data->modified_by = $this->user_model->is_logged_in() ? active_user('id') : null;

                    //  Work out the parent ID
                    if (!empty($item['parent_id']) && isset($idMap[$item['parent_id']])) {

                        $data->parent_id = $idMap[$item['parent_id']];

                    } else {

                        $data->parent_id = null;
                    }

                    if (empty($item['id']) || substr($item['id'], 0, 5) == 'newid') {

                        $action = 'INSERT';
                        $data->menu_id     = $menu->id;
                        $data->created     = $data->modified;
                        $data->created_by  = $data->modified_by;

                    } else {

                        $action = 'UPDATE';
                    }

                    //  what we doin'?
                    if ($action == 'UPDATE') {

                        $this->db->set($data);
                        $this->db->where('id', $item['id']);
                        if (!$this->db->update($this->_table_item)) {

                            $this->db->trans_rollback();
                            $this->_set_error('Failed to update menu item');
                            return false;
                        }

                        $lastId = $item['id'];

                    } elseif ($action == 'INSERT') {

                        $this->db->set($data);

                        if (!$this->db->insert($this->_table_item)) {

                            $this->db->trans_rollback();
                            $this->_set_error('Failed to update menu item');
                            return false;
                        }

                        $lastId = $this->db->insert_id();
                    }

                    //  Add to the ID map
                    $idMap[$item['id']] = $lastId;
                }

                //  Delete any untouched menu items
                $idMap = array_values($idMap);

                $this->db->where('menu_id', $menu->id);
                $this->db->where_not_in('id', $idMap);

                if (!$this->db->delete($this->_table_item)) {

                    $this->db->trans_rollback();
                    $this->_set_error('Failed to delete old menu items');
                    return false;
                }

                $this->db->trans_commit();
                return true;

            } else {

                //  No menu items specified, assume unchanged, done!
                $this->db->trans_commit();
                return true;
            }

        } else {

            $this->db->trans_rollback();
            $this->_set_error('Failed to update menu data.');
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Nests menu items
     * Hat tip to Timur; http://stackoverflow.com/a/9224696/789224
     * @param  stdClass &$list  The list to nest
     * @param  int      $parent The parent list's ID
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

    // --------------------------------------------------------------------------

    /**
     * Formats a menu object
     * @param  stdClass &$obj The item to format
     * @return void
     */
    protected function _format_object(&$obj)
    {
        $temp              = new stdClass();
        $temp->id          = $obj->modified_by;
        $temp->email       = $obj->email;
        $temp->first_name  = $obj->first_name;
        $temp->last_name   = $obj->last_name;
        $temp->gender      = $obj->gender;
        $temp->profile_img = $obj->profile_img;

        $obj->modified_by   = $temp;

        unset($obj->email);
        unset($obj->first_name);
        unset($obj->last_name);
        unset($obj->gender);
        unset($obj->profile_img);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a menu item
     * @param  stdClass &$obj The menu item to format
     * @return void
     */
    protected function _format_menu_item(&$obj)
    {
        parent::_format_object($obj);

        // --------------------------------------------------------------------------

        $obj->menu_id   = (int) $obj->menu_id;
        $obj->parent_id = $obj->parent_id ? (int) $obj->parent_id : null;
        $obj->page_id   = $obj->page_id ? (int) $obj->page_id : null;
    }
}

// --------------------------------------------------------------------------

/**
 * OVERLOADING NAILS' MODELS
 *
 * The following block of code makes it simple to extend one of the core Nails
 * models. Some might argue it's a little hacky but it's a simple 'fix'
 * which negates the need to massively extend the CodeIgniter Loader class
 * even further (in all honesty I just can't face understanding the whole
 * Loader class well enough to change it 'properly').
 *
 * Here's how it works:
 *
 * CodeIgniter instantiate a class with the same name as the file, therefore
 * when we try to extend the parent class we get 'cannot redeclare class X' errors
 * and if we call our overloading class something else it will never get instantiated.
 *
 * We solve this by prefixing the main class with NAILS_ and then conditionally
 * declaring this helper class below; the helper gets instantiated et voila.
 *
 * If/when we want to extend the main class we simply define NAILS_ALLOW_EXTENSION
 * before including this PHP file and extend as normal (i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if (!defined('NAILS_ALLOW_EXTENSION_CMS_MENU_MODEL')) {

    class Cms_menu_model extends NAILS_Cms_menu_model
    {
    }
}
