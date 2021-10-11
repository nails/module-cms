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

namespace Nails\Cms\Cms\Widget;

use Nails\Cms\Widget\WidgetBase;

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

        $this->assets_editor[] = 'https://cdnjs.cloudflare.com/ajax/libs/handsontable/0.20.3/handsontable.full.min.js';
        $this->assets_editor[] = 'https://cdnjs.cloudflare.com/ajax/libs/handsontable/0.20.3/handsontable.min.css';
    }
}
