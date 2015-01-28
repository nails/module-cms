<?php

/**
 * This class provides some common CMS controller functionality
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class NAILS_CMS_Controller extends NAILS_Controller
{
    /**
     * Constructs the controller
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        //  Check this module is enabled in settings
        if (! isModuleEnabled('nailsapp/module-cms')) {

            //  Cancel execution, module isn't enabled
            show_404();
        }

        // --------------------------------------------------------------------------

        //  Load language file
        $this->lang->load('cms');
    }
}
