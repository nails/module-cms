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

class {{SLUG}} extends WidgetBase
{
    /**
     * Construct {{SLUG}}
     */
    public function __construct()
    {
        parent::__construct();

        $this->label       = '{{NAME}}';
        $this->description = '{{DESCRIPTION}}';
        $this->grouping    = '{{GROUPING}}';
        $this->keywords    = '{{KEYWORDS}}';
    }

    // --------------------------------------------------------------------------

    /**
     * Can be used to ensure that $aWidgetData has fields defined in both the
     * editor and render views.
     *
     * @param array $aWidgetData The widget's data
     */
    protected function populateWidgetData(&$aWidgetData)
    {
        $aWidgetData                  = (array) $aWidgetData;
        $aWidgetData['sSomeVariable'] = getFromArray('sSomeVariable', $aWidgetData);
    }
}

EOD;
