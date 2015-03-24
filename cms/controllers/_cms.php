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

        //  Load language file
        $this->lang->load('cms');
    }
}
