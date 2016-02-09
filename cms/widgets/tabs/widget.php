<?php

namespace Nails\Cms\Widget;

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
        $this->description = 'Show tabbed content.';
        $this->keywords    = 'tabs, tabbed';

        $this->assets_editor[] = array('admin.widget.tabs.css', 'nailsapp/module-cms');
    }
}
