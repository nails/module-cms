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

class NAILS_CMS_Widget_slider extends NAILS_CMS_Widget
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
