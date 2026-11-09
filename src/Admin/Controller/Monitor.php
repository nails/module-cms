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
use Nails\Admin\Factory\Nav;
use Nails\Admin\Helper;
use Nails\Cms\Admin\Permission;
use Nails\Cms\Constants;
use Nails\Cms\Helper\Monitor as MonitorHelper;
use Nails\Cms\Service;
use Nails\Cms\WidgetEditor\Monitor\Adapter;
use Nails\Common\Service\Input;
use Nails\Factory;

/**
 * Class Monitor
 *
 * @package Nails\Admin\Cms
 */
class Monitor extends Admin\Controller\Base
{
    public static function announce(): Nav|array|null
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

    public function widgetExport(): void
    {
        if (!userHasPermission(Permission\Monitor\Widget::class)) {
            unauthorised();
        }

        /** @var Input $input */
        $input = Factory::service('Input');
        /** @var Service\Monitor\Widget $service */
        $service = Factory::service('MonitorWidget', Constants::MODULE_SLUG);

        Helper::loadCsv(
            MonitorHelper::widgetCsv($this->filterSummary($service->summary(), $input)),
            'cms-widgets.csv',
            false
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

            //  Not a legacy widget; the widget monitor also lists WidgetEditor (v2) widgets
            if (empty($oItem) && $oService instanceof Service\Monitor\Widget) {
                $oItem = $oService->getWidgetEditorBySlug($oUri->segment(5));
            }

            if (empty($oItem)) {
                show404();
            }

            //  v2 adapters report deprecation per instance; the static v1 flags cannot see the widget
            $bIsDeprecated = $oItem instanceof Adapter ? $oItem->isWidgetDeprecated() : $oItem::isDeprecated();
            $sAlternative  = $oItem instanceof Adapter ? $oItem->getWidgetAlternative() : $oItem::alternative();

            $this
                ->setData('aSummary', $oService->summariseItem($oItem))
                ->setData('bIsDeprecated', $bIsDeprecated)
                ->setData('sAlternative', $sAlternative)
                ->setTitles(['CMS Monitor', $sLabel, $oItem->getLabel()])
                ->loadView('details');

        } else {

            $aSummary = $this->filterSummary($oService->summary(), $oInput);

            if ($sView === 'widget') {
                $aExport = MonitorHelper::widgetExportQuery(
                    $oInput->get('keywords'),
                    $oInput->get('sortOn'),
                    $oInput->get('sortOrder')
                );

                Helper::addHeaderButton(
                    static::url('widgetExport') . ($aExport ? '?' . http_build_query($aExport) : ''),
                    'Export as CSV',
                    'default'
                );
            }

            $this
                ->setData('aSummary', $aSummary)
                ->setTitles(['CMS Monitor', $sLabel])
                ->loadView('index');
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Applies the list's search and sort to a monitor summary
     *
     * @param \Nails\Cms\Factory\Monitor\Item[] $aSummary
     *
     * @return \Nails\Cms\Factory\Monitor\Item[]
     */
    private function filterSummary(array $aSummary, Input $oInput): array
    {
        $sKeywords = strtolower(trim((string) $oInput->get('keywords')));
        if ($sKeywords !== '') {
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

        $sSortOn    = trim((string) $oInput->get('sortOn'));
        $sSortOrder = trim((string) $oInput->get('sortOrder'));
        if ($sSortOn !== '' || $sSortOrder !== '') {

            if ($sSortOn === 'usages') {
                arraySortMulti($aSummary, 'usages');
            }

            if ($sSortOrder === 'desc') {
                $aSummary = array_reverse($aSummary);
            }
        }

        return $aSummary;
    }
}
