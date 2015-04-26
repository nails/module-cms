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

echo $this->load->view('structure/header', getControllerData());

$numColumns = isset($numColumns) ? (int) $numColumns : 2;

    ?>
    @todo
    <?php

echo $this->load->view('structure/footer', getControllerData());
