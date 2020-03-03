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

namespace Nails\Cms\Cms\Template;

use Nails\Cms\Constants;
use Nails\Cms\Template\TemplateBase;
use Nails\Factory;

class Fullwidth extends TemplateBase
{
    /**
     * Set this template as the default template so it is rendered first
     * @var boolean
     */
    protected static $isDefault = true;

    // --------------------------------------------------------------------------

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

        $this->widget_areas['mainbody'] = Factory::factory('TemplateArea', Constants::MODULE_SLUG);
        $this->widget_areas['mainbody']->setTitle('Main Body');
    }
}
