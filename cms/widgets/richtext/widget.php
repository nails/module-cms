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

namespace Nails\Cms\Cms\Widget;

use Nails\Cms\Widget\WidgetBase;

class Richtext extends WidgetBase
{
    /**
     * Construct and define the widget
     */
    public function __construct()
    {
        parent::__construct();

        $this->label       = 'Rich Text';
        $this->icon        = 'fa-paragraph';
        $this->description = 'Build beautiful pages using the rich text editor; embed images, links and more.';
        $this->keywords    = 'rich text,formatted text,formatted,wysiwyg,embed';

        $this->assets_editor[] = ['https://cdnjs.cloudflare.com/ajax/libs/ckeditor/4.16.0/ckeditor.min.js'];
        $this->assets_editor[] = ['https://cdnjs.cloudflare.com/ajax/libs/ckeditor/4.16.0/adapters/jquery.min.js'];
    }
}
