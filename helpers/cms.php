<?php

use Nails\Factory;

/**
 * This helper brings some convinient functions for interacting with CMS elements
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Helper
 * @author      Nails Dev Team
 * @link
 */

if (!function_exists('cmsBlock')) {

    /**
     * Returns a block's value
     *
     * @param  string $sSlug The block's slug
     *
     * @return string
     */
    function cmsBlock($sSlug)
    {
        $oBlockModel = Factory::model('Block', 'nails/module-cms');
        $oBlock      = $oBlockModel->getBySlug($sSlug);

        if (!$oBlock) {

            return '';
        }

        return $oBlock->value;
    }
}

// --------------------------------------------------------------------------

if (!function_exists('cmsSlider')) {

    /**
     * Returns a CMS slider
     *
     * @param  string $sIdSlug The slider's ID or slug
     *
     * @return mixed
     */
    function cmsSlider($sIdSlug)
    {
        $oSliderModel = Factory::model('Slider', 'nails/module-cms');
        return $oSliderModel->getByIdOrSlug($sIdSlug);
    }
}

// --------------------------------------------------------------------------

if (!function_exists('cmsMenu')) {

    /**
     * Returns a CMS menu
     *
     * @param  string|integer $mIdSlug The menu's ID or slug
     *
     * @return mixed
     */
    function cmsMenu($mIdSlug)
    {
        $oMenuModel = Factory::model('Menu', 'nails/module-cms');
        return $oMenuModel->getByIdOrSlug($mIdSlug);
    }
}

// --------------------------------------------------------------------------

if (!function_exists('cmsMenuNested')) {

    /**
     * Returns a CMS menu
     *
     * @param  string|integer $mIdSlug The menu's ID or slug
     *
     * @return mixed
     */
    function cmsMenuNested($mIdSlug)
    {
        $oMenuModel = Factory::model('Block', 'nails/module-cms');
        $aData      = ['nestItems' => true];
        return $oMenuModel->getByIdOrSlug($mIdSlug, $aData);
    }
}

// --------------------------------------------------------------------------

if (!function_exists('cmsPage')) {

    /**
     * Returns a CMS page
     *
     * @param  string $mIdSlug The page's ID or slug
     *
     * @return mixed
     */
    function cmsPage($mIdSlug)
    {
        $oPageModel = Factory::model('Page', 'nails/module-cms');
        return $oPageModel->getByIdOrSlug($mIdSlug);
    }
}

// --------------------------------------------------------------------------

if (!function_exists('cmsArea')) {

    /**
     * Returns a rendered CMS area
     *
     * @param  string $mIdSlug The area's ID or slug
     *
     * @return string
     */
    function cmsArea($mIdSlug)
    {
        $oAreaModel = Factory::model('Area', 'nails/module-cms');
        return $oAreaModel->render($mIdSlug);
    }
}

// --------------------------------------------------------------------------

if (!function_exists('cmsAreaWithData')) {

    /**
     * Returns a rendered CMS area using the supplied data
     *
     * @param  array $aData The widget data to use
     *
     * @return string
     */
    function cmsAreaWithData($aData)
    {
        $oAreaModel = Factory::model('Area', 'nails/module-cms');
        return $oAreaModel->renderWithData($aData);
    }
}

// --------------------------------------------------------------------------

if (!function_exists('cmsWidget')) {

    /**
     * Returns a rendered CMS widget
     *
     * @param  string $sSlug The widget's slug
     * @param  array  $aData Data to pass to the widget's render function
     *
     * @return string
     */
    function cmsWidget($sSlug, $aData = [])
    {
        $oWidgetService = Factory::service('Widget', 'nails/module-cms');
        $oWidget        = $oWidgetService->getBySlug($sSlug);

        if ($oWidget) {

            return $oWidget->render($aData);

        } else {

            return '';
        }
    }
}
