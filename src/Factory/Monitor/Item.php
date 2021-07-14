<?php

namespace Nails\Cms\Factory\Monitor;

use Nails\Cms\Factory\Monitor\Item\Usage;

/**
 * Class Item
 *
 * @package Nails\Cms\Factory\Monitor
 */
class Item
{
    /** @var string */
    public $slug;

    /** @var string */
    public $label;

    /** @var string */
    public $description;

    /** @var int */
    public $usages;

    // --------------------------------------------------------------------------

    /**
     * Item constructor.
     *
     * @param string $sSlug
     * @param string $sLabel
     * @param string $sDescription
     */
    public function __construct(string $sSlug, string $sLabel, string $sDescription, int $iUsages)
    {
        $this->slug        = $sSlug;
        $this->label       = $sLabel;
        $this->description = $sDescription;
        $this->usages      = $iUsages;
    }
}
