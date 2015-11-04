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

namespace Nails\Cms\Widget;

class Table extends WidgetBase
{
    /**
     * Construct and define the widget
     */
    public function __construct()
    {
        parent::__construct();

        $this->label       = 'Table';
        $this->icon        = 'fa-table';
        $this->description = 'Easily build a table';
        $this->keywords    = 'table,tabular data,data';

        $this->assets_editor[] = array('handsontable/dist/handsontable.full.min.js', 'NAILS-BOWER');
        $this->assets_editor[] = array('handsontable/dist/handsontable.min.css', 'NAILS-BOWER');
    }
}
