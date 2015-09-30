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

namespace Nails\Cms\Template;

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

        $this->widget_areas['col1']        = \Nails\Factory::factory('TemplateArea', 'nailsapp/module-cms');
        $this->widget_areas['col1']->title = 'First Column';

        $this->widget_areas['col2']        = \Nails\Factory::factory('TemplateArea', 'nailsapp/module-cms');
        $this->widget_areas['col2']->title = 'Second Column';

        $this->widget_areas['col3']        = \Nails\Factory::factory('TemplateArea', 'nailsapp/module-cms');
        $this->widget_areas['col3']->title = 'Third Column';

        $this->widget_areas['col4']        = \Nails\Factory::factory('TemplateArea', 'nailsapp/module-cms');
        $this->widget_areas['col4']->title = 'Fourth Column';


        /**
         * Widget additional fields.
         */
        $this->additional_fields[0]          = \Nails\Factory::factory('TemplateOption', 'nailsapp/module-cms');
        $this->additional_fields[0]->type    = 'dropdown';
        $this->additional_fields[0]->key     = 'numColumns';
        $this->additional_fields[0]->label   = 'Number of columns';
        $this->additional_fields[0]->class   = 'select2';
        $this->additional_fields[0]->default = '2';
        $this->additional_fields[0]->options = array(

            '2' => '2 Columns',
            '3' => '3 Columns',
            '4' => '4 Columns'
        );

        $this->additional_fields[1]          = \Nails\Factory::factory('TemplateOption', 'nailsapp/module-cms');
        $this->additional_fields[1]->type    = 'dropdown';
        $this->additional_fields[1]->key     = 'breakpoint';
        $this->additional_fields[1]->label   = 'Breakpoint';
        $this->additional_fields[1]->class   = 'select2';
        $this->additional_fields[1]->default = 'md';
        $this->additional_fields[1]->tip     = 'The minimum size of screen to maintain columns, before breaking down into full width columns';
        $this->additional_fields[1]->options = array(

            'xs' => 'Extra small devices (phones)',
            'sm' => 'Small devices (tablets)',
            'md' => 'Medium devices (desktops)',
            'lg' => 'Large devices (large desktops)'
        );
    }
}
