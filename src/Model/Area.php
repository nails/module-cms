<?php

/**
 * This model handle CMS Areas
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

class Area extends Base
{
    /**
     * Model constructor
     **/
    public function __construct()
    {
        parent::__construct();
        $this->table = NAILS_DB_PREFIX . 'cms_area';
        $this->tablePrefix = 'a';
        $this->tableAutoSetSlugs = true;
    }

    // --------------------------------------------------------------------------

    /**
     * This method applies the conditionals which are common across the get_*()
     * methods and the count() method.
     * @param  array $data Data passed from the calling method
     * @return void
     **/
    protected function getCountCommon($data = array())
    {
        if (!empty($data['keywords'])) {

            if (empty($data['or_like'])) {

                $data['or_like'] = array();
            }

            $data['or_like'][] = array(
                'column' => $this->tablePrefix . '.label',
                'value'  => $data['keywords']
            );
            $data['or_like'][] = array(
                'column' => $this->tablePrefix . '.slug',
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
     * @param  object $obj      A reference to the object being formatted.
     * @param  array  $data     The same data array which is passed to _getcount_common, for reference if needed
     * @param  array  $integers Fields which should be cast as integers if numerical and not null
     * @param  array  $bools    Fields which should be cast as booleans if not null
     * @param  array  $floats   Fields which should be cast as floats if not null
     * @return void
     */
    protected function formatObject(
        &$obj,
        $data = array(),
        $integers = array(),
        $bools = array(),
        $floats = array()
    ) {
        parent::formatObject($obj, $data, $integers, $bools, $floats);
        $obj->widget_data = json_decode($obj->widget_data);
    }

    // --------------------------------------------------------------------------

    /**
     * Renders a CMS Area
     * @param  mixed  $mAreaIdSlug The area's ID or slug
     * @return string
     */
    public function render($mAreaIdSlug)
    {
        $sOut  = '';
        $oArea = $this->getByIdOrSlug($mAreaIdSlug);

        if ($oArea) {

            $sOut = $this->renderWithData($oArea->widget_data);
        }

        return $sOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Render an array of widget data
     * @param  array  $aWidgetData The array of data to render
     * @return string
     */
    public function renderWithData($aWidgetData)
    {
        $sOut = '';

        if (!empty($aWidgetData)) {

            //  If a string is passed, asusme it's a JSON encoded array
            if (is_string($aWidgetData)) {
                $aWidgetData = json_decode($aWidgetData);
                if (empty($aWidgetData)) {
                    return $sOut;
                }
            }

            $oWidgetModel = Factory::model('Widget', 'nailsapp/module-cms');

            foreach ($aWidgetData as $oWidgetData) {

                $oWidget = $oWidgetModel->getBySlug($oWidgetData->slug);
                $sOut   .= $oWidget->render((array) $oWidgetData->data);
            }
        }

        return $sOut;
    }
}
