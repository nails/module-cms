<?php

namespace Nails\Cms\Service\Monitor;

use Nails\Cms\Constants;
use Nails\Cms\Exception\Widget\NotFoundException;
use Nails\Cms\Service;
use Nails\Common\Exception\FactoryException;
use Nails\Factory;

class Cdn
{
    /** @var string[][] */
    private $aWidgetMappings = [];

    // --------------------------------------------------------------------------

    /**
     * @throws FactoryException
     * @throws NotFoundException
     */
    public function __construct()
    {
        $this->discoverWidgetMappings();
    }

    // --------------------------------------------------------------------------

    /**
     * @throws NotFoundException
     * @throws FactoryException
     */
    public function discoverWidgetMappings(): void
    {
        /** @var Service\Widget $oWidgetService */
        $oWidgetService = Factory::service('Widget', Constants::MODULE_SLUG);
        $aGroups        = $oWidgetService->getAvailable();

        $this->aWidgetMappings = [];
        foreach ($aGroups as $oGroup) {
            foreach ($oGroup->getWidgets() as $oWidget) {
                if ($oWidget instanceof \Nails\Cms\Interfaces\Monitor\Cdn\Widget) {
                    $this->aWidgetMappings[$oWidget->getSlug()] = $oWidget->getCdnMonitorPaths();
                }
            }
        }
    }

    // --------------------------------------------------------------------------

    public function getWidgetMappings(): array
    {
        return $this->aWidgetMappings;
    }
}
