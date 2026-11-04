<?php

/**
 * This service monitors CMS Widgets
 *
 * @package    Nails
 * @subpackage module-cms
 * @category   Service
 * @author     Nails Dev Team
 */

namespace Nails\Cms\Service\Monitor;

use Nails\Cms\Constants;
use Nails\Cms\Exception\Widget\NotFoundException;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\NailsException;
use Nails\Components;
use Nails\Cms\Interfaces;
use Nails\Cms\WidgetEditor\Monitor\Adapter;
use Nails\Factory;

/**
 * Class Widget
 *
 * @package Nails\Cms\Service\Monitor
 */
class Widget
{
    /** @var Interfaces\Monitor\Widget[] */
    protected $aMappers;

    // --------------------------------------------------------------------------

    /**
     * Widget constructor.
     *
     * @throws NailsException
     */
    public function __construct()
    {
        $this->aMappers = $this->discoverMappers();
    }

    // --------------------------------------------------------------------------

    /**
     * Discovers Widget mappers
     *
     * @return Interfaces\Monitor\Widget[]
     * @throws NailsException
     */
    protected function discoverMappers(): array
    {
        $aClasses = [];

        foreach (Components::available() as $oComponent) {

            $oClasses = $oComponent
                ->findClasses('Cms\\Monitor')
                ->whichImplement(Interfaces\Monitor\Widget::class)
                ->whichCanBeInstantiated();

            foreach ($oClasses as $sClass) {
                $aClasses[] = new $sClass();
            }
        }

        return $aClasses;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of widgets and their usages
     *
     * @return \Nails\Cms\Factory\Monitor\Item[]
     * @throws NotFoundException
     * @throws FactoryException
     */
    public function summary(): array
    {
        /** @var \Nails\Cms\Service\Widget $oWidgetService */
        $oWidgetService = Factory::service('Widget', Constants::MODULE_SLUG);

        $aSummary = [];
        foreach ($oWidgetService->getAvailable(true) as $oWidgetGroup) {
            foreach ($oWidgetGroup->getWidgets() as $oWidget) {

                $iUsages = 0;
                foreach ($this->aMappers as $oMapper) {
                    $iUsages += $oMapper->countUsages($oWidget);
                }

                /** @var \Nails\Cms\Factory\Monitor\Item $oItem */
                $oItem = Factory::factory(
                    'MonitorItem',
                    Constants::MODULE_SLUG,
                    $oWidget->getSlug(),
                    $oWidget->getLabel(),
                    $oWidget->getDescription(),
                    $oWidget->getScreenshot(),
                    $iUsages
                );

                $aSummary[] = $oItem;
            }
        }

        //  WidgetEditor (v2) widgets; usages are summed across each widget's aliases
        foreach ($this->getWidgetEditorAdapters() as $oAdapter) {

            /** @var \Nails\Cms\Factory\Monitor\Item $oItem */
            $oItem = Factory::factory(
                'MonitorItem',
                Constants::MODULE_SLUG,
                $oAdapter->getSlug(),
                $oAdapter->getLabel(),
                $oAdapter->getDescription(),
                $oAdapter->getScreenshot(),
                $this->countAdapterUsages($oAdapter),
                $oAdapter->isWidgetDeprecated(),
                $oAdapter->getWidgetAlternative()
            );

            $aSummary[] = $oItem;
        }

        arraySortMulti($aSummary, 'label');

        return $aSummary;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the WidgetEditor (v2) widgets to monitor, keyed by canonical slug
     *
     * Disabled widgets are left out, as they are in the v1 summary.
     *
     * @return Adapter[]
     * @throws FactoryException
     */
    protected function getWidgetEditorAdapters(): array
    {
        /** @var \Nails\Cms\WidgetEditor\Service\Registry $oRegistry */
        $oRegistry = Factory::service('WidgetEditorRegistry', Constants::MODULE_SLUG);

        $aOut = [];
        foreach ($oRegistry->all() as $sCanonical => $oWidget) {
            $aAliases = array_values(array_diff($oRegistry->slugsFor($sCanonical), [$sCanonical]));
            $aOut[$sCanonical] = Factory::factory(
                'WidgetEditorMonitorAdapter',
                Constants::MODULE_SLUG,
                $oWidget,
                $sCanonical,
                $aAliases
            );
        }

        return $aOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Finds a WidgetEditor (v2) widget by its canonical slug, for the monitor detail page
     *
     * @throws FactoryException
     */
    public function getWidgetEditorBySlug(string $sSlug): ?Adapter
    {
        return $this->getWidgetEditorAdapters()[$sSlug] ?? null;
    }

    // --------------------------------------------------------------------------

    /**
     * Counts a WidgetEditor (v2) widget's usages, across its canonical slug and aliases
     *
     * @throws FactoryException
     */
    protected function countAdapterUsages(Adapter $oAdapter): int
    {
        $iUsages = 0;
        foreach ($oAdapter->getMonitoredWidgets() as $oTarget) {
            foreach ($this->aMappers as $oMapper) {
                $iUsages += $oMapper->countUsages($oTarget);
            }
        }

        return $iUsages;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a summary of where a widget is used
     *
     * @param \Nails\Cms\Interfaces\Widget $oWidget
     *
     * @return \Nails\Cms\Factory\Monitor\Detail[]
     * @throws FactoryException
     */
    public function summariseItem(Interfaces\Widget $oWidget)
    {
        $aMappers = [];

        //  A WidgetEditor (v2) adapter stands for its canonical slug and every alias
        $aTargets = $oWidget instanceof Adapter ? $oWidget->getMonitoredWidgets() : [$oWidget];

        foreach ($this->aMappers as $oMapper) {

            $aUsages = [];
            foreach ($aTargets as $oTarget) {
                $aUsages = array_merge($aUsages, $oMapper->getUsages($oTarget));
            }

            if (!empty($aUsages)) {

                /** @var \Nails\Cms\Factory\Monitor\Detail $oDetail */
                $oDetail = Factory::factory(
                    'MonitorDetail',
                    Constants::MODULE_SLUG,
                    $oMapper->getLabel(),
                    $aUsages
                );

                $aMappers[] = $oDetail;
            }
        }

        return $aMappers;
    }
}
