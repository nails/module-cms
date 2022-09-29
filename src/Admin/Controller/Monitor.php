<?php

/**
 * This class provides CMS Widget monitor functionality
 *
 * @package    Nails
 * @subpackage module-cms
 * @category   AdminController
 * @author     Nails Dev Team
 */

namespace Nails\Cms\Admin\Controller;

use Nails\Admin;
use Nails\Cms\Admin\Permission;
use Nails\Cms\Constants;
use Nails\Cms\Service;
use Nails\Factory;

/**
 * Class Monitor
 *
 * @package Nails\Admin\Cms
 */
class Monitor extends Admin\Controller\Base
{
    public static function announce()
    {
        /** @var Nav $oNav */
        $oNav = Factory::factory('Nav', Admin\Constants::MODULE_SLUG);
        $oNav->setLabel('Utilities');

        if (userHasPermission(Permission\Monitor\Widget::class)) {
            $oNav->addAction('CMS Monitor: Widgets', 'widget');
        }

        if (userHasPermission(Permission\Monitor\Template::class)) {
            $oNav->addAction('CMS Monitor: Templates', 'template');
        }

        return $oNav;
    }

    // --------------------------------------------------------------------------

    public function widget()
    {
        $this->overview(
            Factory::service('MonitorWidget', Constants::MODULE_SLUG),
            Factory::service('Widget', Constants::MODULE_SLUG),
            'Widgets',
            'widget',
            Permission\Monitor\Widget::class
        );
    }

    // --------------------------------------------------------------------------

    public function template()
    {
        $this->overview(
            Factory::service('MonitorTemplate', Constants::MODULE_SLUG),
            Factory::service('Template', Constants::MODULE_SLUG),
            'Templates',
            'template',
            Permission\Monitor\Template::class
        );
    }

    // --------------------------------------------------------------------------

    /**
     * @param object $oService
     * @param object $oItemService
     * @param string $sLabel
     * @param string $sView
     * @param string $sPermission
     *
     * @throws \Nails\Cms\Exception\Template\NotFoundException
     * @throws \Nails\Cms\Exception\Widget\NotFoundException
     * @throws \Nails\Common\Exception\FactoryException
     */
    private function overview(
        object $oService,
        object $oItemService,
        string $sLabel,
        string $sView,
        string $sPermission
    ) {
        if (!userHasPermission($sPermission)) {
            unauthorised();
        }

        /** @var \Nails\Common\Service\Uri $oUri */
        $oUri = Factory::service('Uri');
        /** @var \Nails\Common\Service\Input $oInput */
        $oInput = Factory::service('Input');

        if ($oUri->segment(5)) {

            $oItem = $oItemService->getBySlug($oUri->segment(5));
            if (empty($oItem)) {
                show404();
            }

            $this
                ->setData('aSummary', $oService->summariseItem($oItem))
                ->setData('bIsDeprecated', $oItem::isDeprecated())
                ->setData('sAlternative', $oItem::alternative())
                ->setTitles(['CMS Monitor', $sLabel, $oItem->getLabel()])
                ->loadView('details');

        } else {

            $aSummary = $oService->summary();

            //  Search
            $sKeywords = strtolower(trim($oInput->get('keywords')));
            if ($sKeywords) {
                $aSummary = array_filter($aSummary, function (\Nails\Cms\Factory\Monitor\Item $oItem) use ($sKeywords) {

                    if (str_contains(strtolower($oItem->slug), $sKeywords)) {
                        return true;
                    } elseif (str_contains(strtolower($oItem->label), $sKeywords)) {
                        return true;
                    } elseif (str_contains(strtolower($oItem->description), $sKeywords)) {
                        return true;
                    }

                    return false;
                });
            }

            //  Sorting
            $sSortOn    = trim($oInput->get('sortOn'));
            $sSortOrder = trim($oInput->get('sortOrder'));
            if ($sSortOn || $sSortOrder) {

                if ($sSortOn === 'usages') {
                    arraySortMulti($aSummary, 'usages');
                }

                if ($sSortOrder === 'desc') {
                    $aSummary = array_reverse($aSummary);
                }
            }

            $this
                ->setData('aSummary', $aSummary)
                ->setTitles(['CMS Monitor', $sLabel])
                ->loadView('index');
        }
    }
}
