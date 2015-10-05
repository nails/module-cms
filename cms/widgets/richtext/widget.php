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

namespace Nails\Cms\Widget;

class Richtext extends WidgetBase
{
    /**
     * Construct and define the widget
     */
    public function __construct()
    {
        $this->label       = 'Rich Text';
        $this->description = 'Build beautiful pages using the rich text editor; embed images, links and more.';
        $this->keywords    = 'rich text,formatted text,formatted,wysiwyg,embed';

        $this->assets_editor[] = array('ckeditor/ckeditor.js', 'NAILS-BOWER');
        $this->assets_editor[] = array('ckeditor/adapters/jquery.js', 'NAILS-BOWER');
    }
}
