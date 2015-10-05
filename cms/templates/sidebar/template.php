<?php

/**
 * This is the "Sidebar" CMS template definition
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Template
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Template;

class Sidebar extends TemplateBase
{
    /**
     * Construct and define the template
     */
    public function __construct()
    {
        parent::__construct();

        $this->label       = 'Sidebar';
        $this->description = 'Main body with a sidebar to either the left or right.';

        /**
         * Widget areas; give each a unique index, the index will be passed as the
         * variable to the view
         */

        $this->widget_areas['sidebar'] = \Nails\Factory::factory('TemplateArea', 'nailsapp/module-cms');
        $this->widget_areas['sidebar']->setTitle('Sidebar');

        $this->widget_areas['mainbody'] = \Nails\Factory::factory('TemplateArea', 'nailsapp/module-cms');
        $this->widget_areas['mainbody']->setTitle('Main Body');

        $this->additional_fields[0] = \Nails\Factory::factory('TemplateOption', 'nailsapp/module-cms');
        $this->additional_fields[0]->setType('dropdown');
        $this->additional_fields[0]->setKey('sidebarWidth');
        $this->additional_fields[0]->setLabel('Sidebar Width');
        $this->additional_fields[0]->setClass('select2');
        $this->additional_fields[0]->setDefault('4');
        $this->additional_fields[0]->setOptions(
            array(
                '1' => '1 Column',
                '2' => '2 Columns',
                '3' => '3 Columns',
                '4' => '4 Columns',
                '5' => '5 Columns',
                '6' => '6 Columns',
            )
        );

        $this->additional_fields[1] = \Nails\Factory::factory('TemplateOption', 'nailsapp/module-cms');
        $this->additional_fields[1]->setType('dropdown');
        $this->additional_fields[1]->setKey('sidebarSide');
        $this->additional_fields[1]->setLabel('Sidebar Side');
        $this->additional_fields[1]->setClass('select2');
        $this->additional_fields[1]->setDefault('LEFT');
        $this->additional_fields[1]->setOptions(
            array(
                'LEFT' => 'Left Hand Side',
                'RIGHT' => 'Right Hand Side'
            )
        );
    }
}
