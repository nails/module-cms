<?php

namespace Nails\Cms\Interfaces\Monitor;

use Nails\Cms\Interfaces;
use Nails\Cms\Factory\Monitor\Detail\Usage;

/**
 * Interface Template
 *
 * @package Nails\Cms\Interfaces\Monitor
 */
interface Template
{
    /**
     * Returns the mapper's label, used on the details page
     *
     * @return string
     */
    public function getLabel(): string;

    // --------------------------------------------------------------------------

    /**
     * Counts the number of instances a given template is used
     *
     * @param Interfaces\Template $oTemplate
     *
     * @return int
     */
    public function countUsages(Interfaces\Template $oTemplate): int;

    // --------------------------------------------------------------------------

    /**
     * Locates instances where a given template is used
     *
     * @param Interfaces\Template $oTemplate
     *
     * @return Usage[]
     */
    public function getUsages(Interfaces\Template $oTemplate): array;
}
