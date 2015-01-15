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

class Nails_CMS_Template_sidebar_left extends Nails_CMS_Template
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
        $d->label       = 'Sidebar Left';
        $d->description = 'Main body with a sidebar to the left.';

        /**
         * Widget areas; give each a unique index, the index will be passed as the
         * variable to the view
         */

        $d->widget_areas['sidebar']         = parent::editableAreaTemplate();
        $d->widget_areas['sidebar']->title  = 'Sidebar';
        $d->widget_areas['mainbody']        = parent::editableAreaTemplate();
        $d->widget_areas['mainbody']->title = 'Main Body';

        // --------------------------------------------------------------------------

        return $d;
    }
}
