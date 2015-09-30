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
     * Defines the basic widget details object.
     * @return stdClass
     */
    public static function details()
    {
        $d              = parent::details();
        $d->label       = 'Slider';
        $d->description = 'Embed easily configurable photo sliders into your page.';
        $d->keywords    = 'gallery,slider,image gallery,images';

        return $d;
    }
}
