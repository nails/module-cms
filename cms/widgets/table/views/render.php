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
