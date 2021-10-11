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

namespace App\Cms\Widget;

use Nails\Cms\Widget\WidgetBase;

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
        $this->data        = [
            'sSomeVariable' => 'A default value',
        ];
    }
}

EOD;
