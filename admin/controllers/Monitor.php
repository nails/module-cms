<?php

/**
 * This class provides CMS Widget monitor functionality
 *
 * @package    Nails
 * @subpackage module-cms
 * @category   AdminController
 * @author     Nails Dev Team
 */

namespace Nails\Admin\Cms;

use Nails\Admin;
use Nails\Cms\Constants;
use Nails\Cms\Controller\BaseAdmin;
use Nails\Cms\Service;
use Nails\Factory;

/**
 * Class Monitor
 *
 * @package Nails\Admin\Cms
 */
class Monitor extends BaseAdmin
{
    public static function announce()
    {
        if (userHasPermission('admin:cms:monitor:*')) {
            /** @var Nav $oNav */
            $oNav = Factory::factory('Nav', Admin\Constants::MODULE_SLUG);
            $oNav->setLabel('Utilities');

            if (userHasPermission('admin:cms:monitor:widget')) {
                $oNav->addAction('CMS Monitor: Widgets', 'widget');
            }

            if (userHasPermission('admin:cms:monitor:template')) {
                $oNav->addAction('CMS Monitor: Templates', 'template');
            }

            return $oNav;
        }
    }

    // --------------------------------------------------------------------------

    public static function permissions(): array
    {
        return array_merge(
            parent::permissions(),
            [
                'widget'   => 'Can monitor CMS widget usage',
                'template' => 'Can monitor CMS template usage',
            ]
        );
    }

    // --------------------------------------------------------------------------

    public function widget()
    {
        $this->overview(
            Factory::service('MonitorWidget', Constants::MODULE_SLUG),
            Factory::service('Widget', Constants::MODULE_SLUG),
            'Widgets',
            'widget'
        );
    }

    // --------------------------------------------------------------------------

    public function template()
    {
        $this->overview(
            Factory::service('MonitorTemplate', Constants::MODULE_SLUG),
            Factory::service('Template', Constants::MODULE_SLUG),
            'Templates',
            'template'
        );
    }

    // --------------------------------------------------------------------------

    /**
     * @param Service\Monitor\Widget|Service\Monitor\Template $oService
     * @param Service\Widget|Service\Template                 $oItemService
     * @param string                                          $sLabel
     * @param string                                          $sView
     *
     * @throws \Nails\Common\Exception\FactoryException
     */
    private function overview(
        object $oService,
        object $oItemService,
        string $sLabel,
        string $sView
    ) {
        /** @var \Nails\Common\Service\Uri $oUri */
        $oUri = Factory::service('Uri');
        /** @var \Nails\Common\Service\Input $oInput */
        $oInput = Factory::service('Input');

        if ($oUri->segment(5)) {

            $oItem = $oItemService->getBySlug($oUri->segment(5));
            if (empty($oItem)) {
                show404();
            }

            $this->data['aSummary']      = $oService->summariseItem($oItem);
            $this->data['bIsDeprecated'] = $oItem::isDeprecated();
            $this->data['sAlternative']  = $oItem::alternative();
            $this->data['page']->title   = sprintf(
                'CMS Monitor &rsaquo; %s &rsaquo; %s',
                $sLabel,
                $oItem->getLabel()
            );
            Admin\Helper::loadView('details');

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

            $this->data['aSummary']    = $aSummary;
            $this->data['page']->title = sprintf(
                'CMS Monitor &rsaquo; %s',
                $sLabel
            );
            Admin\Helper::loadView('index');
        }
    }
}
