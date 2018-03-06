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

namespace Nails\Cms\Cms\Widget;

use Nails\Cms\Widget\WidgetBase;

class Html extends WidgetBase
{
    /**
     * Construct and define the widget
     */
    public function __construct()
    {
        parent::__construct();

        $this->label       = 'Plain Text';
        $this->icon        = 'fa-code';
        $this->description = 'Plain, completely unformatted text. Perfect for custom HTML.';
        $this->keywords    = 'text,html,code,plaintext,plain text';
    }
}
