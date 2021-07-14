<?php

namespace Nails\Cms\Factory\Monitor;

use Nails\Cms\Factory\Monitor\Detail\Usage;

/**
 * Class Detail
 *
 * @package Nails\Cms\Factory\Monitor
 */
class Detail
{
    /** @var string */
    public $label;

    /** @var Usage[] */
    public $usages = [];

    // --------------------------------------------------------------------------

    /**
     * Detail constructor.
     *
     * @param string  $sSlug
     * @param Usage[] $aUsages
     */
    public function __construct(string $sLabel, array $aUsages)
    {
        $this->label  = $sLabel;
        $this->usages = $aUsages;
    }
}
