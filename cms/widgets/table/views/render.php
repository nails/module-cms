<?php

/**
 * This class is the "Table" CMS widget view
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

$sTableData = !empty($tblData) ? (string) $tblData : '';
$sTblAttr = !empty($tblAttr) ? $tblAttr : '';

//  A table saved by the WidgetEditor (v2), or converted by Migration13, stores `rows` and `attributes`
if ($sTableData === '' && !empty($rows) && is_array($rows)) {
    $sTableData = json_encode(array_values($rows));
}
if ($sTblAttr === '' && !empty($attributes) && is_string($attributes)) {
    $sTblAttr = $attributes;
}

if (!empty($sTableData)) {
    $sTableData = json_decode($sTableData);
    ?>
    <div class="cms-widget cms-widget-table">
        <table class="table" <?=$sTblAttr?>>
            <tbody>
            <?php
            foreach ($sTableData as $iRowNum => $aColumns) {
                $sOddEven = $iRowNum % 2 ? 'odd' : 'even';
                ?>
                <tr data-row="<?=$iRowNum?>" class="cms-widget-table-row cms-widgettable-row-<?=$sOddEven?>">
                    <?php
                    foreach ($aColumns as $iColNum => $sCellData) {
                        ?>
                        <td data-row="<?=$iColNum?>" class="cms-widget-table-cell">
                            <?=$sCellData?>
                        </td>
                        <?php
                    }
                    ?>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
    </div>
    <?php
}
