<?php

namespace Nails\Cms\Cms\Widget;

use Nails\Cms\Widget\WidgetBase;

class Tabs extends WidgetBase
{
    /**
     * Construct and define the widget
     */
    public function __construct()
    {
        parent::__construct();

        $this->label       = 'Tabs';
        $this->icon        = 'fa-folder';
        $this->description = 'Show tabbed content.';
        $this->keywords    = 'tabs, tabbed';
    }
}
