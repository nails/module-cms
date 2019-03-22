<?php

/**
 * This class is the "Table" CMS editor view
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

//  Show some empty rows and columns by default
$aEmptyTableData = [
    ['','','','',''],
    ['','','','',''],
    ['','','','',''],
    ['','','','',''],
    ['','','','',''],
    ['','','','',''],
    ['','','','',''],
    ['','','','',''],
    ['','','','',''],
    ['','','','',''],
    ['','','','',''],
    ['','','','',''],
];
$sEmptyTableData = json_encode($aEmptyTableData);

?>
<div class="handsontable" style="background: rgb(234, 234, 234);"></div>
<textarea style="display: none;" name="tblData" class="table-data">
<?=!empty($tblData) ? $tblData : $sEmptyTableData?>
</textarea>
<?php

echo form_field([
    'key'         => 'tblAttr',
    'label'       => 'Attributes',
    'default'     => isset($tblAttr) ? $tblAttr : '',
    'placeholder' => 'Any additional attributes to include in the table.',
]);
