<?php

/**
 * This is the "Columns" CMS template definition
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Template
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Cms\Template;

use Nails\Cms\Template\TemplateBase;
use Nails\Factory;

class Columns extends TemplateBase
{
    /**
     * Construct and define the template
     */
    public function __construct()
    {
        parent::__construct();

        $this->label       = 'Columns';
        $this->description = 'Up to four evenly spaced columns';

        /**
         * Widget areas; give each a unique index, the index will be passed as the
         * variable to the view.
         */

        $this->widget_areas['col1'] = Factory::factory('TemplateArea', 'nailsapp/module-cms');
        $this->widget_areas['col1']->setTitle('First Column');

        $this->widget_areas['col2'] = Factory::factory('TemplateArea', 'nailsapp/module-cms');
        $this->widget_areas['col2']->setTitle('Second Column');

        $this->widget_areas['col3'] = Factory::factory('TemplateArea', 'nailsapp/module-cms');
        $this->widget_areas['col3']->setTitle('Third Column');

        $this->widget_areas['col4'] = Factory::factory('TemplateArea', 'nailsapp/module-cms');
        $this->widget_areas['col4']->setTitle('Fourth Column');

        /**
         * Widget additional fields.
         */
        $this->additional_fields[0] = Factory::factory('TemplateOption', 'nailsapp/module-cms');
        $this->additional_fields[0]->setType('dropdown');
        $this->additional_fields[0]->setKey('numColumns');
        $this->additional_fields[0]->setLabel('No. of columns');
        $this->additional_fields[0]->setClass('select2');
        $this->additional_fields[0]->setDefault('2');
        $this->additional_fields[0]->setOptions(
            array(
                '2' => '2 Columns',
                '3' => '3 Columns',
                '4' => '4 Columns'
            )
        );

        $this->additional_fields[1] = Factory::factory('TemplateOption', 'nailsapp/module-cms');
        $this->additional_fields[1]->setType('dropdown');
        $this->additional_fields[1]->setKey('breakpoint');
        $this->additional_fields[1]->setLabel('Breakpoint');
        $this->additional_fields[1]->setClass('select2');
        $this->additional_fields[1]->setDefault('md');
        $this->additional_fields[1]->setTip(
            'The minimum size of screen to maintain columns, before breaking down into full width columns'
        );
        $this->additional_fields[1]->setOptions(
            array(
                'xs' => 'Extra small devices (phones)',
                'sm' => 'Small devices (tablets)',
                'md' => 'Medium devices (desktops)',
                'lg' => 'Large devices (large desktops)'
            )
        );
    }
}
