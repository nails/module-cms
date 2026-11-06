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

//  A table saved by the WidgetEditor (v2), or converted by Migration13, stores `rows`, `attributes` and
//  `header_rows`. This form replaces the instance's data when it is saved, so it must carry all three.
if (empty($tblData) && !empty($rows) && is_array($rows)) {
    $tblData = json_encode(array_values($rows));
}
if (!isset($tblAttr) && isset($attributes) && is_string($attributes)) {
    $tblAttr = $attributes;
}

?>
<div class="handsontable" style="background: rgb(234, 234, 234);"></div>
<textarea style="display: none;" name="tblData" class="table-data">
<?=!empty($tblData) ? $tblData : $sEmptyTableData?>
</textarea>
<input type="hidden" name="header_rows" value="<?=(int) ($header_rows ?? 0)?>">
<?php

echo form_field([
    'key'         => 'tblAttr',
    'label'       => 'Attributes',
    'default'     => isset($tblAttr) ? $tblAttr : '',
    'placeholder' => 'Any additional attributes to include in the table.',
]);
