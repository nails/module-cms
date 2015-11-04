<?php

/**
 * This class is the "Slider" CMS widget definition
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Widget;

class Slider extends WidgetBase
{
    /**
     * Construct and define the widget
     */
    public function __construct()
    {
        parent::__construct();

        $this->label       = 'Slider';
        $this->icon        = 'fa-clone';
        $this->description = 'Embed easily configurable photo sliders into your page.';
        $this->keywords    = 'gallery,slider,image gallery,images';
    }
}
