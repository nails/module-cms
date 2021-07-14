<?php

namespace Nails\Cms\Interfaces\Monitor;

use Nails\Cms\Interfaces;
use Nails\Cms\Factory\Monitor\Item\Usage;

/**
 * Interface Widget
 *
 * @package Nails\Cms\Interfaces\Monitor
 */
interface Widget
{
    /**
     * Returns the mapper's label, used on the details page
     *
     * @return string
     */
    public function getLabel(): string;

    // --------------------------------------------------------------------------

    /**
     * Counts the number of instances a given widget is used
     *
     * @param Interfaces\Widget $oWidget
     *
     * @return int
     */
    public function countUsages(Interfaces\Widget $oWidget): int;

    // --------------------------------------------------------------------------

    /**
     * Locates instances where a given widget is used
     *
     * @param Interfaces\Widget $oWidget
     *
     * @return Usage[]
     */
    public function getUsages(Interfaces\Widget $oWidget): array;
}
