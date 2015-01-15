<?php

/**
 * This class is the "Image" CMS widget definition
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

class NAILS_CMS_Widget_image extends NAILS_CMS_Widget
{
    /**
     * Defines the basic widget details object.
     * @return stdClass
     */
    static function details()
    {
        $d              = parent::details();
        $d->label       = 'Image';
        $d->description = 'A single image.';
        $d->keywords    = 'image,images,photo,photos';

        return $d;
    }
}
