<?php

/**
 * This class is the "Plain text" CMS widget definition
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Widget;

class Html extends WidgetBase
{
    /**
     * Defines the basic widget details object.
     * @return stdClass
     */
    public static function details()
    {
        $d              = parent::details();
        $d->label       = 'Plain Text';
        $d->description = 'Plain, completely unformatted text. Perfect for custom HTML.';
        $d->keywords    = 'text,html,code,plaintext,plain text';

        return $d;
    }
}
