<?php

/**
 * This is the "Columns" CMS template view
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Template
 * @author      Nails Dev Team
 * @link
 */

echo $oCi->load->view('structure/header', getControllerData());

$numColumns = isset($numColumns) ? (int) $numColumns : 2;
$breakpoint = isset($breakpoint) ? $breakpoint : 'md';
$eachColumn = 12 / $numColumns;

echo '<div class="row">';
for ($i=1; $i <= $numColumns; $i++) {

	echo '<div class="col-' . $breakpoint . '-' . $eachColumn . '">';
		$colName = 'col' . $i;
		echo !empty($$colName) ? $$colName : '';
	echo '</div>';
}
echo '</div>';

echo $oCi->load->view('structure/footer', getControllerData());
