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

$tblData       = !empty($tblData) ? $tblData : '';
$tblAttr       = !empty($tblAttr) ? $tblAttr : '';
$tblResponsive = !empty($tblResponsive) ? 'table-responsive' : '';
$tblStriped    = !empty($tblStriped) ? 'table-striped' : '';
$tblBordered   = !empty($tblBordered) ? 'table-bordered' : '';
$tblHover      = !empty($tblHover) ? 'table-hover' : '';

if (!empty($tblData)) {

    $tblData = json_decode($tblData);

    ?>
    <div class="cms-widget cms-widget-table <?=$tblResponsive?>">
        <table class="table <?=$tblStriped?> <?=$tblBordered?> <?=$tblHover?>" <?=$tblAttr?>>
            <tbody>
            <?php

            foreach ($tblData as $iRowNum => $aColumns) {

                $sOddEven = $iRowNum % 2 ? 'odd' : 'even';
                echo '<tr data-row="' . $iRowNum . '" class="cms-widget-table-row cms-widgettable-row-' . $sOddEven . '">';

                    foreach ($aColumns as $iColNum => $sCellData) {

                        echo '<td data-row="' . $iColNum . '" class="cms-widget-table-cell">';
                        echo $sCellData;
                        echo '</td>';
                    }

                echo '</tr>';
            }

            ?>
            </tbody>
        </table>
    </div>
    <?php
}