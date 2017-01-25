<?php

/**
 * This file is the template for the contents of: widget.php
 * Used by the console command when creating widgets.
 */

return <<<'EOD'
<?php

/**
 * This is the "{{SLUG}}" CMS widget definition
 */
 
namespace Nails\Cms\Widget;

use Nails\Cms\Widget\WidgetBase;

class {{SLUG}} extends WidgetBase
{
    /**
     * Construct {{SLUG}}
     */
    public function __construct()
    {
        parent::__construct();

        $this->label       = '{{WIDGET_NAME}}';
        $this->description = '{{WIDGET_DESCRIPTION}}';
        $this->grouping    = '{{WIDGET_GROUPING}}';
        $this->keywords    = '{{WIDGET_KEYWORDS}}';
    }
}

EOD;
