<?php

/**
 * This file is the template for the contents of: template.php
 * Used by the console command when creating templates.
 */

use Nails\Cms\Constants;

return <<<'EOD'
<?php

/**
 * This is the "{{SLUG}}" CMS template definition
 */

namespace App\Cms\Template;

use Nails\Cms\Constants;
use Nails\Cms\Template\TemplateBase;
use Nails\Factory;

class {{SLUG}} extends TemplateBase
{
    /**
     * Construct {{SLUG}}
     */
    public function __construct()
    {
        parent::__construct();

        //  Basic template configuration
        $this->label       = '{{NAME}}';
        $this->description = '{{DESCRIPTION}}';

        //  Define template widget areas
        $this->widget_areas = [
            //  The template's body
            'sBody' => Factory::factory('TemplateArea', Constants::MODULE_SLUG)
                ->setTitle('Body')
        ];
    }
}
EOD;
