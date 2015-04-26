<?php

/**
 * This is the "Sidebar Right" CMS template definition
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Template
 * @author      Nails Dev Team
 * @link
 */

class Nails_CMS_Template_sidebar_right extends Nails_CMS_Template
{
    /**
     * Defines the basic template details object.
     * @return stdClass
     */
    static function details()
    {
        //  Base object
        $d = parent::details();

        //  Basic details; describe the template for the user
        $d->label           = 'Sidebar Right';
        $d->description = 'Main body with a sidebar to the right.';

        /**
         * Widget areas; give each a unique index, the index will be passed as the
         * variable to the view
         */

        $d->widget_areas['mainbody']        = parent::editableAreaTemplate();
        $d->widget_areas['mainbody']->title = 'Main Body';
        $d->widget_areas['sidebar']         = parent::editableAreaTemplate();
        $d->widget_areas['sidebar']->title  = 'Sidebar';

        $d->additional_fields[0]            = array();
        $d->additional_fields[0]['type']    = 'dropdown';
        $d->additional_fields[0]['key']     = 'sidebarWidth';
        $d->additional_fields[0]['label']   = 'Sidebar Width';
        $d->additional_fields[0]['class']   = 'select2';
        $d->additional_fields[0]['default'] = '4';
        $d->additional_fields[0]['options'] = array(

            '1' => '1 Column',
            '2' => '2 Columns',
            '3' => '3 Columns',
            '4' => '4 Columns',
            '5' => '5 Columns',
            '6' => '6 Columns',
        );

        // --------------------------------------------------------------------------

        return $d;
    }
}
