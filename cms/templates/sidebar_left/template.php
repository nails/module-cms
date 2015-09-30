<?php

/**
 * This is the "Sidebar Left" CMS template definition
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Template
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Template;

class Sidebar_left extends TemplateBase
{
    /**
     * Construct and define the template
     */
    public function __construct()
    {
        parent::__construct();

        $this->label       = 'Sidebar Left';
        $this->description = 'Main body with a sidebar to the left.';

        /**
         * Widget areas; give each a unique index, the index will be passed as the
         * variable to the view
         */

        $this->widget_areas['sidebar']         = \Nails\Factory::factory('TemplateArea', 'nailsapp/module-cms');
        $this->widget_areas['sidebar']->title  = 'Sidebar';

        $this->widget_areas['mainbody']        = \Nails\Factory::factory('TemplateArea', 'nailsapp/module-cms');
        $this->widget_areas['mainbody']->title = 'Main Body';

        $this->additional_fields[0]          = \Nails\Factory::factory('TemplateOption', 'nailsapp/module-cms');
        $this->additional_fields[0]->type    = 'dropdown';
        $this->additional_fields[0]->key     = 'sidebarWidth';
        $this->additional_fields[0]->label   = 'Sidebar Width';
        $this->additional_fields[0]->class   = 'select2';
        $this->additional_fields[0]->default = '4';
        $this->additional_fields[0]->options = array(

            '1' => '1 Column',
            '2' => '2 Columns',
            '3' => '3 Columns',
            '4' => '4 Columns',
            '5' => '5 Columns',
            '6' => '6 Columns',
        );
    }
}
