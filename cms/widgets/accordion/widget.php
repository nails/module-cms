<?php

/**
 * This class is the "Accordion" CMS widget definition
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Cms\Widget;

use Nails\Cms\Widget\WidgetBase;

class Accordion extends WidgetBase
{
    /**
     * Construct and define the widget
     */
    public function __construct()
    {
        parent::__construct();

        $this->label       = 'Accordion';
        $this->icon        = 'fa-list-alt';
        $this->description = 'A collapsible accordion component.';
        $this->keywords    = 'accordion';

        $this->assets_editor[] = ['admin.widget.accordion.css', 'nails/module-cms'];
    }
}
