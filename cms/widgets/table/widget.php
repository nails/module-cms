<?php

/**
 * This class is the "Table" CMS widget definition
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

class NAILS_CMS_Widget_table extends NAILS_CMS_Widget
{
    /**
     * Defines the basic widget details object.
     * @return stdClass
     */
    public static function details()
    {
        $d              = parent::details();
        $d->label       = 'Table';
        $d->description = 'Easily build a table';
        $d->keywords    = 'table,tabular data,data';

        return $d;
    }
}
