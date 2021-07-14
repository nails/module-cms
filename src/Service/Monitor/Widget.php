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
        foreach ($oWidgetService->getAvailable(false, true) as $oWidgetGroup) {
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
                    $iUsages
                );

                $aSummary[] = $oItem;
            }
        }

        arraySortMulti($aSummary, 'label');

        return $aSummary;
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

        foreach ($this->aMappers as $oMapper) {

            $aUsages = $oMapper->getUsages($oWidget);
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
