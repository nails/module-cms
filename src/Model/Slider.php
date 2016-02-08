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

namespace Nails\Cms\Model;

use Nails\Factory;
use Nails\Common\Model\Base;

class Slider extends Base
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

        $this->table             = NAILS_DB_PREFIX . 'cms_slider';
        $this->tablePrefix       = 's';
        $this->table_item        = NAILS_DB_PREFIX . 'cms_slider_item';
        $this->table_item_prefix = 'si';
    }

    // --------------------------------------------------------------------------

    /**
     * This method applies the conditionals which are common across the get_*()
     * methods and the count() method.
     * @param array  $data Data passed from the calling method
     * @return void
     **/
    protected function getCountCommon($data = array())
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

        parent::getCountCommon($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a single object
     *
     * The getAll() method iterates over each returned item with this method so as to
     * correctly format the output. Use this to cast integers and booleans and/or organise data into objects.
     *
     * @param  object $oObj      A reference to the object being formatted.
     * @param  array  $aData     The same data array which is passed to _getcount_common, for reference if needed
     * @param  array  $aIntegers Fields which should be cast as integers if numerical and not null
     * @param  array  $aBools    Fields which should be cast as booleans if not null
     * @param  array  $aFloats   Fields which should be cast as floats if not null
     * @return void
     */
    protected function formatObject(
        &$oObj,
        $aData = array(),
        $aIntegers = array(),
        $aBools = array(),
        $aFloats = array()
    ) {

        parent::formatObject($oObj, $aData, $aIntegers, $aBools, $aFloats);

        $oTemp              = new \stdClass();
        $oTemp->id          = (int) $oObj->modified_by;
        $oTemp->email       = $oObj->email;
        $oTemp->first_name  = $oObj->first_name;
        $oTemp->last_name   = $oObj->last_name;
        $oTemp->gender      = $oObj->gender;
        $oTemp->profile_img = $oObj->profile_img ? (int) $oObj->profile_img : null;

        $oObj->modified_by = $oTemp;

        unset($oObj->email);
        unset($oObj->first_name);
        unset($oObj->last_name);
        unset($oObj->gender);
        unset($oObj->profile_img);

        // --------------------------------------------------------------------------

        $oObj->slides = $this->getSliderItems($oObj->id);
    }

    // --------------------------------------------------------------------------

    /**
     * Gets the slides of an individual slider
     * @param  int   $sliderId The Slider's ID
     * @return array
     */
    public function getSliderItems($sliderId)
    {
        $this->oDb->where('slider_id', $sliderId);
        $this->oDb->order_by('order');
        $items = $this->oDb->get($this->table_item)->result();

        foreach ($items as $i) {

            $this->formatObjectItem($i);
        }

        return $items;
    }

    // --------------------------------------------------------------------------

    /**
     * Format a slider item
     * @param  stdClass &$obj The slider item to format
     * @return voud
     */
    protected function formatObjectItem(&$obj)
    {
        parent::formatObject($obj);

        // --------------------------------------------------------------------------

        $obj->slider_id = (int) $obj->slider_id;
        $obj->object_id = $obj->object_id ? (int) $obj->object_id : null;

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
        $this->oDb->trans_begin();

        if (isset($data['slides'])) {

            $slides = $data['slides'];
            unset($data['slides']);
        }

        $data['slug'] = $this->generateSlug($data['label']);

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

            $table       = $this->table;
            $tablePrefix = $this->tablePrefix;

            $this->table       = $this->table_item;
            $this->tablePrefix = $this->table_item_prefix;

            for ($i=0; $i<count($slides); $i++) {

                $data              = array();
                $data['slider_id'] = $sliderId;
                $data['object_id'] = $slides[$i]->object_id;
                $data['caption']   = $slides[$i]->caption;
                $data['url']       = $slides[$i]->url;

                $result = parent::create($data);

                if (!$result) {

                    $this->setError('Failed to create slide #' . ($i+1));
                    $this->oDb->trans_rollback();
                    return false;

                }
            }

            //  Reset the table and table prefix
            $this->table        = $table;
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

        if (isset($data['slides'])) {

            $slides = $data['slides'];
            unset($data['slides']);
        }

        $data['slug'] = $this->generateSlug($data['label'], '', '', null, null, $id);

        $result = parent::update($id, $data);

        if ($result && $slides) {

            /**
             * Take a note of the table prefixes, we're swapping them quickly while
             * we do this update, so we can leverage the parent methods for the slides.
             */

            $table       = $this->table;
            $tablePrefix = $this->tablePrefix;

            $this->table       = $this->table_item;
            $this->tablePrefix = $this->table_item_prefix;

            $idsUpdated = array();
            for ($i=0; $i<count($slides); $i++) {

                $data = array();
                $data['object_id'] = $slides[$i]->object_id;
                $data['caption']   = $slides[$i]->caption;
                $data['url']       = $slides[$i]->url;
                $data['order']     = $i;

                //  Update or create? If create remember and save ID
                if (!empty($slides[$i]->id)) {

                    $result = parent::update($slides[$i]->id, $data);

                    if (!$result) {

                        $this->setError('Failed to update slide #' . ($i+1));
                        $this->oDb->trans_rollback();
                        return false;

                    } else {

                        $idsUpdated[] = $slides[$i]->id;
                    }

                } else {

                    $data['slider_id'] = $id;
                    $result = parent::create($data);

                    if (!$result) {

                        $this->setError('Failed to create slide #' . ($i+1));
                        $this->oDb->trans_rollback();
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

                $this->oDb->where('slider_id', $id);
                $this->oDb->where_not_in('id', $idsUpdated);
                $this->oDb->delete($this->table);
            }

            //  Reset the table and table prefix
            $this->table        = $table;
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
}