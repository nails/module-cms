<?php

use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Cms\Constants;
use Nails\Cms\Model;
use Nails\Cms\Resource;
use Nails\Factory;

/**
 * This helper brings some convinient functions for interacting with CMS elements
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Helper
 * @author      Nails Dev Team
 */

if (!function_exists('cmsBlock')) {

    /**
     * Returns a block's value
     *
     * @param int|string|null $mIdSlug The block's ID or slug
     *
     * @return string
     * @throws FactoryException
     * @throws ModelException
     */
    function cmsBlock($mIdSlug): string
    {
        /** @var Model\Block $oModel */
        $oModel = Factory::model('Block', Constants::MODULE_SLUG);
        /** @var Resource\Block $oBlock */
        $oBlock = $oModel->getByIdOrSlug($mIdSlug);

        return $oBlock->value ?? '';
    }
}

// --------------------------------------------------------------------------

if (!function_exists('cmsMenu')) {

    /**
     * Returns a CMS menu
     *
     * @param int|string|null $mIdSlug The menu's ID or slug
     * @param array           $aData
     *
     * @return Resource\Menu
     * @throws FactoryException
     */
    function cmsMenu($mIdSlug, array $aData = []): ?Resource\Menu
    {
        /** @var Model\Menu $oModel */
        $oModel = Factory::model('Menu', Constants::MODULE_SLUG);
        return $oModel->getByIdOrSlug($mIdSlug, $aData);
    }
}

// --------------------------------------------------------------------------

if (!function_exists('cmsPage')) {

    /**
     * Returns a CMS page
     *
     * @param int|string|null $mIdSlug The page's ID or slug
     *
     * @return mixed
     */
    function cmsPage($mIdSlug): ?Resource\Page
    {
        /** @var Model\Page $oModel */
        $oModel = Factory::model('Page', Constants::MODULE_SLUG);
        return $oModel->getByIdOrSlug($mIdSlug);
    }
}

// --------------------------------------------------------------------------

if (!function_exists('cmsArea')) {

    /**
     * Returns a rendered CMS area
     *
     * @param int|string|null $mIdSlug The area's ID or slug
     *
     * @return string
     */
    function cmsArea($mIdSlug): string
    {
        /** @var Model\Area $oModel */
        $oModel = Factory::model('Area', Constants::MODULE_SLUG);
        return $oModel->render($mIdSlug);
    }
}

// --------------------------------------------------------------------------

if (!function_exists('cmsAreaWithData')) {

    /**
     * Returns a rendered CMS area using the supplied data
     *
     * @param string|array $mWidgetData The widget data to use
     *
     * @return string
     */
    function cmsAreaWithData($mWidgetData): string
    {
        $oAreaModel = Factory::model('Area', Constants::MODULE_SLUG);
        return $oAreaModel->renderWithData($mWidgetData);
    }
}

// --------------------------------------------------------------------------

if (!function_exists('cmsWidget')) {

    /**
     * Returns a rendered CMS widget
     *
     * @param string $sSlug The widget's slug
     * @param array  $aData Data to pass to the widget's render function
     *
     * @return string
     */
    function cmsWidget($sSlug, $aData = []): string
    {
        /** @var \Nails\Cms\Service\Widget $oWidgetService */
        $oWidgetService = Factory::service('Widget', Constants::MODULE_SLUG);
        /** @var \Nails\Cms\Widget\WidgetBase $oWidget */
        $oWidget = $oWidgetService->getBySlug($sSlug);

        if ($oWidget) {
            return $oWidget->render($aData);
        }

        return '';
    }
}
