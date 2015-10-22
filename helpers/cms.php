<?php

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
     * @param  string $sSlug The block's slug
     * @return string
     */
    function cmsBlock($sSlug)
    {
        $oBlockModel = \Nails\Factory::model('Block', 'nailsapp/module-cms');
        $oBlock      = $oBlockModel->get_by_slug($sSlug);

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
     * @param  string $sIdSlug The slider's ID or slug
     * @return mixed
     */
    function cmsSlider($sIdSlug)
    {
        $oSliderModel = \Nails\Factory::model('Slider', 'nailsapp/module-cms');
        return $oSliderModel->get_by_id_or_slug($sIdSlug);
    }
}

// --------------------------------------------------------------------------

if (!function_exists('cmsMenu')) {

    /**
     * Returns a CMS menu
     * @param  string|integer $mIdSlug The menu's ID or slug
     * @return mixed
     */
    function cmsMenu($mIdSlug)
    {
        $oMenuModel = \Nails\Factory::model('Menu', 'nailsapp/module-cms');
        return $oMenuModel->get_by_id_or_slug($mIdSlug);
    }
}

// --------------------------------------------------------------------------

if (!function_exists('cmsMenuNested')) {

    /**
     * Returns a CMS menu
     * @param  string|integer $mIdSlug The menu's ID or slug
     * @return mixed
     */
    function cmsMenuNested($mIdSlug)
    {
        $oMenuModel = \Nails\Factory::model('Block', 'nailsapp/module-cms');
        $aData      = array('nestItems' => true);
        return $oMenuModel->get_by_id_or_slug($mIdSlug, $aData);
    }
}

// --------------------------------------------------------------------------

if (!function_exists('cmsPage')) {

    /**
     * Returns a CMS page
     * @param  string $mIdSlug The page's ID or slug
     * @return mixed
     */
    function cmsPage($mIdSlug)
    {
        $oPageModel = \Nails\Factory::model('Page', 'nailsapp/module-cms');
        return $oPageModel->get_by_id_or_slug($mIdSlug);
    }
}

// --------------------------------------------------------------------------

if (!function_exists('cmsArea')) {

    /**
     * Returns a CMS area
     * @param  string $mIdSlug The area's ID or slug
     * @return mixed
     */
    function cmsArea($mIdSlug)
    {
        $oAreaModel = \Nails\Factory::model('Area', 'nailsapp/module-cms');
        return $oAreaModel->get_by_id_or_slug($mIdSlug);
    }
}
