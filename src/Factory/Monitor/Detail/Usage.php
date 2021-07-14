<?php

namespace Nails\Cms\Factory\Monitor\Detail;

/**
 * Class Usage
 *
 * @package Nails\Cms\Factory\Monitor\Detail
 */
class Usage
{
    /** @var string */
    public $label;

    /** @var string|null */
    public $urlView;

    /** @var string|null */
    public $urlEdit;

    // --------------------------------------------------------------------------

    /**
     * Usage constructor.
     *
     * @param string      $sLabel
     * @param string|null $sUrlView
     * @param string|null $sUrlEdit
     */
    public function __construct(string $sLabel, ?string $sUrlView, ?string $sUrlEdit)
    {
        $this->label   = $sLabel;
        $this->urlView = $sUrlView;
        $this->urlEdit = $sUrlEdit;
    }
}
