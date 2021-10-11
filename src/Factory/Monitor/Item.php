<?php

namespace Nails\Cms\Factory\Monitor;

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

    /** @var bool */
    public $is_deprecated;

    /** @var string */
    public $alternative;

    // --------------------------------------------------------------------------

    /**
     * Item constructor.
     *
     * @param string $sSlug
     * @param string $sLabel
     * @param string $sDescription
     */
    public function __construct(
        string $sSlug,
        string $sLabel,
        string $sDescription,
        int $iUsages,
        bool $bIsDeprecated = false,
        string $sAlternative = ''
    ) {
        $this->slug          = $sSlug;
        $this->label         = $sLabel;
        $this->description   = $sDescription;
        $this->usages        = $iUsages;
        $this->is_deprecated = $bIsDeprecated;
        $this->alternative   = $sAlternative;
    }
}
