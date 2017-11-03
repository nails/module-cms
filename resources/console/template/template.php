<?php

/**
 * This file is the template for the contents of: template.php
 * Used by the console command when creating templates.
 */

return <<<'EOD'
<?php

/**
 * This is the "{{SLUG}}" CMS template definition
 */

namespace App\Cms\Template;

use Nails\Factory;
use Nails\Cms\Template\TemplateBase;

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
        // $this->widget_areas = [
        //     //  The template's body
        //     'body' => Factory::factory('TemplateArea', 'nailsapp/module-cms')
        //         ->setTitle('Body')
        // ];

        //  Define additional options
        // $this->additional_options = [
        //     //  The template's header text
        //     'body' => Factory::factory('TemplateOption', 'nailsapp/module-cms')
        //         ->setType('text'),
        //         ->setKey('header_text'),
        //         ->setLabel('Header Text'),
        // ];
    }
}
EOD;
