<?php

/**
 * Migration: 13
 *
 * Stores v1 Table widgets in the WidgetEditor (v2) shape: `tblData`, a JSON string, becomes `rows`, `tblAttr`
 * becomes `attributes`, and `header_rows` is 0, so each table renders as it did. Pages (draft and published) and
 * areas are covered, including tables in slots. Table::upgradeV1() keeps anything it cannot convert exactly, and a
 * row whose JSON cannot be read is left as it is. Page previews expire, so they are not migrated.
 *
 * Repeatable because `feature/pre-new-admin` has no equivalent migration, so an app arriving from that branch could
 * resume above this number and would never run it. Each run reads only rows that contain a v1 key, and converted
 * data has none, so a run with nothing to convert changes nothing. It also stores tables the v1 editor saved since.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Database Migration
 * @author      Nails Dev Team
 */

namespace Nails\Cms\Database\Migration;

use Nails\Cms\Cms\Widgets\Table;
use Nails\Cms\WidgetEditor\Traits\Database\Migration\UpgradeWidgetData;
use Nails\Common\Interfaces;
use Nails\Common\Traits;

class Migration13 implements Interfaces\Database\Migration\Repeatable
{
    use Traits\Database\Migration;
    use UpgradeWidgetData;

    // --------------------------------------------------------------------------

    public function execute(): void
    {
        $upgrade   = [Table::class, 'upgradeV1'];
        $upgraders = [
            'table'                       => $upgrade,
            'Nails.Cms.Cms.Widgets.Table' => $upgrade,
        ];

        $columns = [
            ['{{NAILS_DB_PREFIX}}cms_page', 'draft_template_data'],
            ['{{NAILS_DB_PREFIX}}cms_page', 'published_template_data'],
            ['{{NAILS_DB_PREFIX}}cms_area', 'widget_data'],
        ];

        foreach ($columns as [$table, $column]) {
            //  Every v1 key starts "tbl", so other rows are not read
            $this->upgradeWidgetData($table, $column, $upgraders, '"tbl');
        }
    }
}
