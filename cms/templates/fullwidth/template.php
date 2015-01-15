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

class Nails_CMS_Template_fullwidth extends Nails_CMS_Template
{
    /**
     * Defines the basic template details object.
     * @return stdClass
     */
    public static function details()
    {
        //  Base object
        $d = parent::details();

        //  Basic details; describe the template for the user
        $d->label       = 'Full Width';
        $d->description = 'A full width template';

        /**
         * Widget areas; give each a unique index, the index will be passed as the
         * variable to the view
         */

        $d->widget_areas['mainbody']        = parent::editableAreaTemplate();
        $d->widget_areas['mainbody']->title = 'Main Body';

        // --------------------------------------------------------------------------

        return $d;
    }
}
