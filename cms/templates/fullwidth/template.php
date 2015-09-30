<?php

/**
 * This is the "Full width" CMS template definition
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Template
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Template;

class Fullwidth extends TemplateBase
{
    /**
     * Construct and define the template
     */
    public function __construct()
    {
        parent::__construct();

        $this->label       = 'Full Width';
        $this->description = 'A full width template';

        /**
         * Widget areas; give each a unique index, the index will be passed as the
         * variable to the view
         */

        $this->widget_areas['mainbody']        = \Nails\Factory::factory('TemplateArea', 'nailsapp/module-cms');
        $this->widget_areas['mainbody']->title = 'Main Body';

    }
}
