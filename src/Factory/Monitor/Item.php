<?php

namespace Nails\Cms\Factory\Monitor;

/**
 * Class Item
 *
 * @package Nails\Cms\Factory\Monitor
 */
class Item
{
    public string $slug;
    public string $label;
    public string $description;
    public string $image;
    public int    $usages;
    public bool   $is_deprecated;
    public string $alternative;

    // --------------------------------------------------------------------------

    public function __construct(
        string $sSlug,
        string $sLabel,
        string $sDescription,
        string $sImage,
        int $iUsages,
        bool $bIsDeprecated = false,
        string $sAlternative = ''
    ) {
        $this->slug          = $sSlug;
        $this->label         = $sLabel;
        $this->description   = $sDescription;
        $this->image         = $sImage;
        $this->usages        = $iUsages;
        $this->is_deprecated = $bIsDeprecated;
        $this->alternative   = $sAlternative;
    }
}
