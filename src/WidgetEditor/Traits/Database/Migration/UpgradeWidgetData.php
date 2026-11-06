<?php

/**
 * A migration trait which saves widgets' data in their current shape, in JSON columns
 *
 * It runs each named widget's upgrade over the stored instances, so content written by an older version (for
 * example a v1 widget) is stored in the shape the widget reads now. The caller names the tables and columns it owns;
 * nothing is discovered. Use it in a migration, for example:
 *
 *     $this->upgradeWidgetData('{{NAILS_DB_PREFIX}}cms_area', 'widget_data', [
 *         'table' => [Table::class, 'upgradeV1'],
 *     ], 'tblData');
 *
 * The upgrade must return data that is already in the current shape unchanged, so a second run finds nothing to
 * change. A widget's upgrade() still runs when it renders, so content saved after the migration is read the same.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Trait
 * @author      Nails Dev Team
 */

namespace Nails\Cms\WidgetEditor\Traits\Database\Migration;

use Nails\Cms\WidgetEditor\Service\Upgrade;

trait UpgradeWidgetData
{
    /**
     * Upgrades the data of the named widgets in one JSON column
     *
     * Rows that cannot be decoded, or encoded again, are skipped and reported in `invalid`, so a bad row is never
     * written and never stops the migration.
     *
     * @param string                                $table     The table, with {{NAILS_DB_PREFIX}} if needed
     * @param string                                $column    The JSON column
     * @param array<string, callable(array): array> $upgraders Slug => upgrade
     * @param string|null                           $contains  Only rows whose column contains this text are read
     * @param bool                                  $dryRun    Count what would change, without writing
     * @param string                                $key       The primary key column
     *
     * @return array{rows: int, nodes: int, invalid: int} Rows changed, nodes upgraded, and rows skipped
     */
    protected function upgradeWidgetData(
        string $table,
        string $column,
        array $upgraders,
        ?string $contains = null,
        bool $dryRun = false,
        string $key = 'id'
    ): array {
        $table  = $this->replaceConstants($table);
        $totals = ['rows' => 0, 'nodes' => 0, 'invalid' => 0];

        if (empty($upgraders)) {
            return $totals;
        }

        $select = $this->prepare(sprintf(
            'SELECT `%1$s`, `%2$s` FROM `%3$s` WHERE `%2$s` IS NOT NULL%4$s',
            $key,
            $column,
            $table,
            $contains === null ? '' : sprintf(' AND INSTR(`%s`, :contains) > 0', $column)
        ));
        $select->execute($contains === null ? [] : [':contains' => $contains]);

        $update = $dryRun
            ? null
            : $this->prepare(sprintf('UPDATE `%s` SET `%s` = :value WHERE `%s` = :key', $table, $column, $key));

        foreach ($select->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $decoded = json_decode((string) $row[$column]);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $totals['invalid']++;
                continue;
            }

            $result = Upgrade::rewrite($decoded, $upgraders);
            if ($result['nodes'] === 0) {
                continue;
            }

            $encoded = json_encode(
                $result['value'],
                JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
            if ($encoded === false) {
                $totals['invalid']++;
                continue;
            }

            $totals['rows']++;
            $totals['nodes'] += $result['nodes'];

            $update?->execute([
                ':value' => $encoded,
                ':key'   => $row[$key],
            ]);
        }

        return $totals;
    }
}
