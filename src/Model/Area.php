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

use Nails\Cms\Exception\Widget\NotFoundException;
use Nails\Common\Model\Base;
use Nails\Environment;
use Nails\Factory;

/**
 * Class Area
 *
 * @package Nails\Cms\Model
 */
class Area extends Base
{
    /**
     * The table this model represents
     *
     * @var string
     */
    const TABLE = NAILS_DB_PREFIX . 'cms_area';

    /**
     * Whether to automatically set slugs or not
     *
     * @var bool
     */
    const AUTO_SET_SLUG = true;

    // --------------------------------------------------------------------------

    /**
     * Area constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->searchableFields[] = 'slug';
        $this->searchableFields[] = 'description';
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a single object
     *
     * The getAll() method iterates over each returned item with this method so as to
     * correctly format the output. Use this to cast integers and booleans and/or organise data into objects.
     *
     * @param object $oObj      A reference to the object being formatted.
     * @param array  $aData     The same data array which is passed to getCountCommon, for reference if needed
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

        $oObj->widget_data = json_decode($oObj->widget_data);
    }

    // --------------------------------------------------------------------------

    /**
     * Renders a CMS Area
     *
     * @param mixed $mAreaIdSlug The area's ID or slug
     *
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
     *
     * @param array $aWidgetData The array of data to render
     *
     * @return string
     * @throws NotFoundException
     */
    public function renderWithData($aWidgetData)
    {
        $sOut = '';

        if (!empty($aWidgetData)) {

            //  If a string is passed, assume it's a JSON encoded array
            if (is_string($aWidgetData)) {
                $aWidgetData = json_decode($aWidgetData);
                if (empty($aWidgetData)) {
                    return $sOut;
                }
            }

            $oWidgetService = Factory::service('Widget', 'nails/module-cms');

            foreach ($aWidgetData as $oWidgetData) {
                $sSlug   = $oWidgetData->slug ?? '';
                $oWidget = $oWidgetService->getBySlug($sSlug);
                if (!empty($oWidget)) {
                    $sOut .= $oWidget->render((array) $oWidgetData->data);
                } elseif (Environment::not(Environment::ENV_PROD)) {
                    throw new NotFoundException('"' . $sSlug . '" is not a valid widget');
                }
            }
        }

        return $sOut;
    }
}
