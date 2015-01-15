<?php

/**
 * This class is the "Rich Text" CMS widget definition
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

class NAILS_CMS_Widget_richtext extends NAILS_CMS_Widget
{
    /**
     * Defines the basic widget details object.
     * @return stdClass
     */
    public static function details()
    {
        $d              = parent::details();
        $d->label       = 'Rich Text';
        $d->description = 'Build beautiful pages using the rich text editor; embed images, links and more.';
        $d->keywords    = 'rich text,formatted text,formatted,wysiwyg,embed';

        $d->assets_editor[] = array('ckeditor/ckeditor.js', 'BOWER');
        $d->assets_editor[] = array('ckeditor/adapters/jquery.js', 'BOWER');

        return $d;
    }
}
