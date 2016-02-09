<?php

/**
 * This class is the "Blockquote" CMS widget definition
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Widget;

class Blockquote extends WidgetBase
{
    /**
     * Construct and define the widget
     */
    public function __construct()
    {
        parent::__construct();

        $this->label       = 'Blockquote';
        $this->icon        = 'fa-quote-left';
        $this->description = 'A block quote with optional citation';
        $this->keywords    = 'text,plain text,quote,blockquote,callout';
    }
}
