<?php

/**
 * This service monitors CMS Templates
 *
 * @package    Nails
 * @subpackage module-cms
 * @category   Service
 * @author     Nails Dev Team
 */

namespace Nails\Cms\Service\Monitor;

use Nails\Cms\Constants;
use Nails\Cms\Exception\Template\NotFoundException;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\NailsException;
use Nails\Components;
use Nails\Cms\Interfaces;
use Nails\Factory;

/**
 * Class Template
 *
 * @package Nails\Cms\Service\Monitor
 */
class Template
{
    /** @var Interfaces\Monitor\Template[] */
    protected $aMappers;

    // --------------------------------------------------------------------------

    /**
     * Template constructor.
     *
     * @throws NailsException
     */
    public function __construct()
    {
        $this->aMappers = $this->discoverMappers();
    }

    // --------------------------------------------------------------------------

    /**
     * Discovers Template mappers
     *
     * @return Interfaces\Monitor\Template[]
     * @throws NailsException
     */
    protected function discoverMappers(): array
    {
        $aClasses = [];

        foreach (Components::available() as $oComponent) {

            $oClasses = $oComponent
                ->findClasses('Cms\\Monitor')
                ->whichImplement(Interfaces\Monitor\Template::class)
                ->whichCanBeInstantiated();

            foreach ($oClasses as $sClass) {
                $aClasses[] = new $sClass();
            }
        }

        return $aClasses;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of templates and their usages
     *
     * @return \Nails\Cms\Factory\Monitor\Item[]
     * @throws NotFoundException
     * @throws FactoryException
     */
    public function summary(): array
    {
        /** @var \Nails\Cms\Service\Template $oTemplateService */
        $oTemplateService = Factory::service('Template', Constants::MODULE_SLUG);

        $aSummary = [];
        foreach ($oTemplateService->getAvailable() as $oTemplateGroup) {
            foreach ($oTemplateGroup->getTemplates() as $oTemplate) {

                $iUsages = 0;
                foreach ($this->aMappers as $oMapper) {
                    $iUsages += $oMapper->countUsages($oTemplate);
                }

                /** @var \Nails\Cms\Factory\Monitor\Item $oItem */
                $oItem = Factory::factory(
                    'MonitorItem',
                    Constants::MODULE_SLUG,
                    $oTemplate->getSlug(),
                    $oTemplate->getLabel(),
                    $oTemplate->getDescription(),
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
     * Returns a summary of where a template is used
     *
     * @param Interfaces\Template $oTemplate
     *
     * @return \Nails\Cms\Factory\Monitor\Detail[]
     * @throws FactoryException
     */
    public function summariseItem(Interfaces\Template $oTemplate)
    {
        $aMappers = [];

        foreach ($this->aMappers as $oMapper) {

            $aUsages = $oMapper->getUsages($oTemplate);
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
