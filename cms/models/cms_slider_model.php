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
     * This method applies the conditionals which are common across the get_*()
     * methods and the count() method.
     * @param array  $data    Data passed from the calling method
     * @param string $_caller The name of the calling method
     * @return void
     **/
    protected function _getcount_common($data = array(), $_caller = null)
    {
        $this->db->select($this->_table_prefix . '.*,u.first_name,u.last_name,u.profile_img,u.gender,ue.email');
        $this->db->join(NAILS_DB_PREFIX . 'user u', $this->_table_prefix . '.modified_by = u.id');
        $this->db->join(NAILS_DB_PREFIX . 'user_email ue', $this->_table_prefix . '.modified_by = ue.user_id AND ue.is_primary = 1');

        // --------------------------------------------------------------------------

        if (!empty($data['keywords'])) {

            if (!isset($data['or_like'])) {

                $data['or_like'] = array();
            }

            $data['or_like'][] = array($this->_table_prefix . '.label', $data['keywords']);
        }

        parent::_getcount_common($data, $_caller);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a slider object
     * @param  stdClass &$object The slider object to format
     * @return void
     */
    protected function _format_object(&$object)
    {
        parent::_format_object($object);

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

        $object->slides = $this->getSliderItems($object->id);
    }

    // --------------------------------------------------------------------------

    /**
     * Gets the slides of an individual slider
     * @param  int   $sliderId The Slider's ID
     * @return array
     */
    public function getSliderItems($sliderId)
    {
        $this->db->where('slider_id', $sliderId);
        $this->db->order_by('order');
        $items = $this->db->get($this->_table_item)->result();

        foreach ($items as $i) {

            $this->_format_object_item($i);
        }

        return $items;
    }

    // --------------------------------------------------------------------------

    /**
     * Format a slider item
     * @param  stdClass &$obj The slider item to format
     * @return voud
     */
    protected function _format_object_item(&$obj)
    {
        parent::_format_object($obj);

        // --------------------------------------------------------------------------

        $obj->slider_id = (int) $obj->slider_id;
        $obj->object_id = $obj->object_id ? (int) $obj->object_id : null;
        $obj->page_id   = $obj->page_id ? (int) $obj->page_id : null;

        unset($obj->slider_id);
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
        $this->db->trans_begin();

        if (isset($data['slides'])) {

            $slides = $data['slides'];
            unset($data['slides']);
        }

        $data['slug'] = $this->_generate_slug($data['label']);

        $result = parent::create($data, $returnObject);

        if ($result && $slides) {

            if ($returnObject) {

                $sliderId = $result->id;

            } else {

                $sliderId = $result;
            }

            /**
             * Take a note of the table prefixes, we're swapping them quickly while
             * we do this update, so we can leverage the parent methods for the slides.
             */

            $table       = $this->_table;
            $tablePrefix = $this->_table_prefix;

            $this->_table        = $this->_table_item;
            $this->_table_prefix = $this->_table_item_prefix;

            for ($i=0; $i<count($slides); $i++) {

                $data              = array();
                $data['slider_id'] = $sliderId;
                $data['object_id'] = $slides[$i]->object_id;
                $data['caption']   = $slides[$i]->caption;
                $data['url']       = $slides[$i]->url;

                $result = parent::create($data);

                if (!$result) {

                    $this->_set_error('Failed to create slide #' . $i);
                    $this->db->trans_rollback();
                    return false;

                }
            }

            //  Reset the table and table prefix
            $this->_table        = $table;
            $this->_table_prefix = $tablePrefix;

            //  Commit the transaction
            $this->db->trans_commit();

        } else {

            $this->db->trans_rollback();
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
        $this->db->trans_begin();

        if (isset($data['slides'])) {

            $slides = $data['slides'];
            unset($data['slides']);
        }

        $data['slug'] = $this->_generate_slug($data['label'], '', '', NULL, NULL, $id);

        $result = parent::update($id, $data);

        if ($result && $slides) {

            /**
             * Take a note of the table prefixes, we're swapping them quickly while
             * we do this update, so we can leverage the parent methods for the slides.
             */

            $table       = $this->_table;
            $tablePrefix = $this->_table_prefix;

            $this->_table        = $this->_table_item;
            $this->_table_prefix = $this->_table_item_prefix;

            $idsUpdated = array();
            for ($i=0; $i<count($slides); $i++) {

                $data = array();
                $data['object_id'] = $slides[$i]->object_id;
                $data['caption']   = $slides[$i]->caption;
                $data['url']       = $slides[$i]->url;
                $data['order']     = $i;

                //  Update or create? If create remember and save ID
                if (!empty($slide['id'])) {

                    $result = parent::update($slides[$i]->id, $data);

                    if (!$result) {

                        $this->_set_error('Failed to update slide #' . $i);
                        $this->db->trans_rollback();
                        return false;

                    } else {

                        $idsUpdated[] = $slides[$i]->id;
                    }

                } else {

                    $data['slider_id'] = $id;
                    $result = parent::create($data);

                    if (!$result) {

                        $this->_set_error('Failed to create slide #' . $i);
                        $this->db->trans_rollback();
                        return false;

                    } else {

                        $idsUpdated[] = $result;
                    }
                }
            }

            //  Remove any slides which weren't updated or created
            $idsUpdated = array_filter($idsUpdated);
            $idsUpdated = array_unique($idsUpdated);

            if ($idsUpdated) {

                $this->db->where_not_in('id', $idsUpdated);
                $this->db->delete($this->_table);
            }

            //  Reset the table and table prefix
            $this->_table        = $table;
            $this->_table_prefix = $tablePrefix;

            //  Commit the transaction
            $this->db->trans_commit();

        } else {

            $this->db->trans_rollback();
        }

        return $result;
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
