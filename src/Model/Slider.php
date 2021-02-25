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

use Nails\Common\Model\Base;
use Nails\Config;
use Nails\Factory;

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

        //  @todo (Pablo - 2021-02-24) - Refactor this to use expandable fields rather than table switching
        $this->table             = Config::get('NAILS_DB_PREFIX') . 'cms_slider';
        $this->tableAlias        = 's';
        $this->table_item        = Config::get('NAILS_DB_PREFIX') . 'cms_slider_item';
        $this->table_item_prefix = 'si';
    }

    // --------------------------------------------------------------------------

    /**
     * This method applies the conditionals which are common across the get_*()
     * methods and the count() method.
     *
     * @param array $data Data passed from the calling method
     *
     * @return void
     **/
    protected function getCountCommon(array $data = []): void
    {
        $this->oDb->select($this->tableAlias . '.*,u.first_name,u.last_name,u.profile_img,u.gender,ue.email');
        $this->oDb->join(
            Config::get('NAILS_DB_PREFIX') . 'user u',
            $this->tableAlias . '.modified_by = u.id',
            'LEFT'
        );
        $this->oDb->join(
            Config::get('NAILS_DB_PREFIX') . 'user_email ue',
            $this->tableAlias . '.modified_by = ue.user_id AND ue.is_primary = 1',
            'LEFT'
        );

        // --------------------------------------------------------------------------

        if (!empty($data['keywords'])) {

            if (empty($data['or_like'])) {

                $data['or_like'] = [];
            }

            $data['or_like'][] = [
                'column' => $this->tableAlias . '.label',
                'value'  => $data['keywords'],
            ];
            $data['or_like'][] = [
                'column' => $this->tableAlias . '.description',
                'value'  => $data['keywords'],
            ];
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
     * @param object $oObj      A reference to the object being formatted.
     * @param array  $aData     The same data array which is passed to _getcount_common, for reference if needed
     * @param array  $aIntegers Fields which should be cast as integers if numerical and not null
     * @param array  $aBools    Fields which should be cast as booleans if not null
     * @param array  $aFloats   Fields which should be cast as floats if not null
     *
     * @return void
     */
    protected function formatObject(
        &$oObj,
        array $aData = [],
        array $aIntegers = [],
        array $aBools = [],
        array $aFloats = []
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
     *
     * @param int $sliderId The Slider's ID
     *
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
     *
     * @param stdClass &$obj The slider item to format
     *
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
     *
     * @param array $data         The data to create the object with
     * @param bool  $returnObject Whether to return just the new ID or the full object
     *
     * @return mixed
     */
    public function create(array $data = [], $returnObject = false)
    {
        $this->oDb->transaction()->start();

        if (isset($data['slides'])) {

            $slides = $data['slides'];
            unset($data['slides']);
        }

        $data['slug'] = $this->generateSlug($data);

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

            $table      = $this->table;
            $tableAlias = $this->tableAlias;

            $this->table      = $this->table_item;
            $this->tableAlias = $this->table_item_prefix;

            for ($i = 0; $i < count($slides); $i++) {

                $data              = [];
                $data['slider_id'] = $sliderId;
                $data['object_id'] = $slides[$i]->object_id;
                $data['caption']   = $slides[$i]->caption;
                $data['url']       = $slides[$i]->url;

                $result = parent::create($data);

                if (!$result) {

                    $this->setError('Failed to create slide #' . ($i + 1));
                    $this->oDb->transaction()->rollback();
                    return false;

                }
            }

            //  Reset the table and table prefix
            $this->table      = $table;
            $this->tableAlias = $tableAlias;

            //  Commit the transaction
            $this->oDb->transaction()->commit();

        } elseif ($result) {

            $this->oDb->transaction()->commit();

        } else {

            $this->oDb->transaction()->rollback();
        }

        return $result;
    }

    // --------------------------------------------------------------------------

    /**
     * Updates an existing object
     *
     * @param int   $id   The ID of the object to update
     * @param array $data The data to update the object with
     *
     * @return bool
     */
    public function update($id, array $data = []): bool
    {
        $this->oDb->transaction()->start();

        if (isset($data['slides'])) {

            $slides = $data['slides'];
            unset($data['slides']);
        }

        $data['slug'] = $this->generateSlug($data, $id);

        $result = parent::update($id, $data);

        if ($result && $slides) {

            /**
             * Take a note of the table prefixes, we're swapping them quickly while
             * we do this update, so we can leverage the parent methods for the slides.
             */

            $table      = $this->table;
            $tableAlias = $this->tableAlias;

            $this->table      = $this->table_item;
            $this->tableAlias = $this->table_item_prefix;

            $idsUpdated = [];
            for ($i = 0; $i < count($slides); $i++) {

                $data              = [];
                $data['object_id'] = $slides[$i]->object_id;
                $data['caption']   = $slides[$i]->caption;
                $data['url']       = $slides[$i]->url;
                $data['order']     = $i;

                //  Update or create? If create remember and save ID
                if (!empty($slides[$i]->id)) {

                    $result = parent::update($slides[$i]->id, $data);

                    if (!$result) {

                        $this->setError('Failed to update slide #' . ($i + 1));
                        $this->oDb->transaction()->rollback();
                        return false;

                    } else {

                        $idsUpdated[] = $slides[$i]->id;
                    }

                } else {

                    $data['slider_id'] = $id;
                    $result            = parent::create($data);

                    if (!$result) {

                        $this->setError('Failed to create slide #' . ($i + 1));
                        $this->oDb->transaction()->rollback();
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
            $this->table      = $table;
            $this->tableAlias = $tableAlias;

            //  Commit the transaction
            $this->oDb->transaction()->commit();

        } elseif ($result) {

            $this->oDb->transaction()->commit();

        } else {

            $this->oDb->transaction()->rollback();
        }

        return $result;
    }
}
