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
$aEmptyTableData = array(
    array('','','','',''),
    array('','','','',''),
    array('','','','',''),
    array('','','','',''),
    array('','','','',''),
    array('','','','',''),
    array('','','','',''),
    array('','','','',''),
    array('','','','',''),
    array('','','','',''),
    array('','','','',''),
    array('','','','','')
);
$sEmptyTableData = json_encode($aEmptyTableData);

?>
<div class="handsontable" style="background: rgb(234, 234, 234);"></div>
<textarea style="display: none;" name="tblData" class="table-data">
<?=!empty($tblData) ? $tblData : $sEmptyTableData?>
</textarea>
<?php

$aField                = array();
$aField['key']         = 'tblAttr';
$aField['label']       = 'Attributes';
$aField['default']     = isset(${$aField['key']}) ? ${$aField['key']} : '';
$aField['placeholder'] = 'Any additional attributes to include in the table.';

echo form_field($aField);

// --------------------------------------------------------------------------

$aField                = array();
$aField['key']         = 'tblResponsive';
$aField['label']       = 'Responsive';
$aField['default']     = isset(${$aField['key']}) ? ${$aField['key']} : '';

echo form_field_boolean($aField);

// --------------------------------------------------------------------------

$aField                = array();
$aField['key']         = 'tblStriped';
$aField['label']       = 'Striped';
$aField['default']     = isset(${$aField['key']}) ? ${$aField['key']} : '';

echo form_field_boolean($aField);

// --------------------------------------------------------------------------

$aField                = array();
$aField['key']         = 'tblBordered';
$aField['label']       = 'Bordered';
$aField['default']     = isset(${$aField['key']}) ? ${$aField['key']} : '';

echo form_field_boolean($aField);

// --------------------------------------------------------------------------

$aField                = array();
$aField['key']         = 'tblHover';
$aField['label']       = 'Highlight on Hover';
$aField['default']     = isset(${$aField['key']}) ? ${$aField['key']} : '';

echo form_field_boolean($aField);
