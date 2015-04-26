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

class Nails_CMS_Template_columns extends Nails_CMS_Template
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
        $d->label       = 'Columns';
        $d->description = 'Up to four evenly spaced columns';

        /**
         * Widget areas; give each a unique index, the index will be passed as the
         * variable to the view
         */

        $d->widget_areas['col1']        = parent::editableAreaTemplate();
        $d->widget_areas['col1']->title = 'First Column';
        $d->widget_areas['col2']        = parent::editableAreaTemplate();
        $d->widget_areas['col2']->title = 'Second Column';
        $d->widget_areas['col3']        = parent::editableAreaTemplate();
        $d->widget_areas['col3']->title = 'Third Column';
        $d->widget_areas['col4']        = parent::editableAreaTemplate();
        $d->widget_areas['col4']->title = 'Fourth Column';

        $d->additional_fields[0]            = array();
        $d->additional_fields[0]['type']    = 'dropdown';
        $d->additional_fields[0]['key']     = 'numColumns';
        $d->additional_fields[0]['label']   = 'Number of columns';
        $d->additional_fields[0]['class']   = 'select2';
        $d->additional_fields[0]['default'] = '2';
        $d->additional_fields[0]['options'] = array(

            '2' => '2 Columns',
            '3' => '3 Columns',
            '4' => '4 Columns'
        );

        // --------------------------------------------------------------------------

        return $d;
    }
}
