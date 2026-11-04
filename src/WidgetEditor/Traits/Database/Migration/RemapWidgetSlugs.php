<?php

/**
 * A migration trait which rewrites widget slugs in JSON columns (D14)
 *
 * The caller names the tables and columns it owns; nothing is discovered. Use it in a migration, for example:
 *
 *     $this->remapWidgetSlugs('{{NAILS_DB_PREFIX}}cms_area', 'widget_data', [
 *         'Heading' => 'App.Cms.Widgets.Heading',
 *     ]);
 *
 * It is idempotent: a second run finds nothing to change. Aliases mean a remap is optional, so run it to normalise
 * data, not as a prerequisite for the editor.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Trait
 * @author      Nails Dev Team
 */

namespace Nails\Cms\WidgetEditor\Traits\Database\Migration;

use Nails\Cms\WidgetEditor\Service\Remap;

trait RemapWidgetSlugs
{
    /**
     * Rewrites the widget slugs in one JSON column
     *
     * Rows that cannot be decoded are skipped and reported in `invalid`, so a bad row never stops the migration.
     *
     * @param string               $sTable  The table, with {{NAILS_DB_PREFIX}} if needed
     * @param string               $sColumn The JSON column
     * @param array<string,string> $aMap    Old slug => new slug
     * @param bool                 $bDryRun Count what would change, without writing
     * @param string               $sKey    The primary key column
     *
     * @return array{rows: int, nodes: int, invalid: int} Rows changed, nodes rewritten, and rows skipped
     */
    protected function remapWidgetSlugs(
        string $sTable,
        string $sColumn,
        array $aMap,
        bool $bDryRun = false,
        string $sKey = 'id'
    ): array {
        $sTable  = $this->replaceConstants($sTable);
        $aTotals = ['rows' => 0, 'nodes' => 0, 'invalid' => 0];

        if (empty($aMap)) {
            return $aTotals;
        }

        $oRows = $this->query(sprintf(
            'SELECT `%1$s`, `%2$s` FROM `%3$s` WHERE `%2$s` IS NOT NULL',
            $sKey,
            $sColumn,
            $sTable
        ));

        $oUpdate = $bDryRun
            ? null
            : $this->prepare(sprintf('UPDATE `%s` SET `%s` = :value WHERE `%s` = :key', $sTable, $sColumn, $sKey));

        foreach ($oRows->fetchAll(\PDO::FETCH_ASSOC) as $aRow) {
            $aDecoded = json_decode((string) $aRow[$sColumn], true);
            if (!is_array($aDecoded)) {
                $aTotals['invalid']++;
                continue;
            }

            $aResult = Remap::rewrite($aDecoded, $aMap);
            if ($aResult['nodes'] === 0) {
                continue;
            }

            $aTotals['rows']++;
            $aTotals['nodes'] += $aResult['nodes'];

            $oUpdate?->execute([
                ':value' => json_encode($aResult['value']),
                ':key'   => $aRow[$sKey],
            ]);
        }

        return $aTotals;
    }
}
