<?php

/**
 * This model handle CMS Sliders
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Cms_slider_model extends NAILS_Model
{
    /**
     * Constructs the model
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $this->_table             = NAILS_DB_PREFIX . 'cms_slider';
        $this->_table_prefix      = 's';
        $this->_table_item        = NAILS_DB_PREFIX . 'cms_slider_item';
        $this->_table_item_prefix = 'si';
    }

    // --------------------------------------------------------------------------

    /**
     * Gets all the sliders
     * @param  boolean $includeSliderItems Whether or not to include the slider items
     * @return array
     */
    public function get_all($includeSliderItems = false)
    {
        $this->db->select($this->_table_prefix . '.*,u.first_name,u.last_name,u.profile_img,u.gender,ue.email');
        $this->db->join(NAILS_DB_PREFIX . 'user u', $this->_table_prefix . '.modified_by = u.id');
        $this->db->join(NAILS_DB_PREFIX . 'user_email ue', $this->_table_prefix . '.modified_by = ue.user_id AND ue.is_primary = 1');
        $sliders = parent::get_all();

        foreach ($sliders as $m) {

            if ($includeSliderItems) {

                //  Fetch the nested slider items
                $m->items = $this->get_slider_items($m->id);
            }
        }

        // --------------------------------------------------------------------------

        return $sliders;
    }

    // --------------------------------------------------------------------------

    /**
     * Gets a single slider
     * @param  boolean $includeSliderItems Whther or not to include slider items
     * @return mixed
     */
    public function get_by_id($includeSliderItems = false)
    {
        $slder = $this->get_all($includeSliderItems);

        if (!$slder) {

            return false;
        }

        return $slder[0];
    }

    // --------------------------------------------------------------------------

    /**
     * Gets the slides of an individual slider
     * @param  int   $sliderId the Slider's ID
     * @return array
     */
    public function get_slider_items($sliderId)
    {
        $this->db->where('slider_id', $sliderId);
        $this->db->order_by('order');
        $items = $this->db->get($this->_table_item)->result();

        foreach ($items as $i) {

            $this->_format_slider_item($i);
        }

        return $items;
    }
    // --------------------------------------------------------------------------

    /**
     * Formats a slider object
     * @param  stdClass &$obj The sldier object to format
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

        $obj->modified_by = $temp;

        unset($obj->email);
        unset($obj->first_name);
        unset($obj->last_name);
        unset($obj->gender);
        unset($obj->profile_img);
    }

    // --------------------------------------------------------------------------

    /**
     * Format a slider item
     * @param  stdClass &$obj The slider item to format
     * @return voud
     */
    protected function _format_slider_item(&$obj)
    {
        parent::_format_object($obj);

        // --------------------------------------------------------------------------

        $obj->slider_id = (int) $obj->slider_id;
        $obj->object_id = $obj->object_id ? (int) $obj->object_id : null;
        $obj->page_id   = $obj->page_id ? (int) $obj->page_id : null;

        unset($obj->slider_id);
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

if (!defined('NAILS_ALLOW_EXTENSION_CMS_SLIDER_MODEL')) {

    class Cms_slider_model extends NAILS_Cms_slider_model
    {
    }
}

