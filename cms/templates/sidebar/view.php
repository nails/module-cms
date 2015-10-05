<?php

/**
 * This is the "Sidebar Left" CMS template view
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Template
 * @author      Nails Dev Team
 * @link
 */

echo $this->load->view('structure/header', getControllerData());

$sidebarWidth = isset($sidebarWidth) ? (int) $sidebarWidth : 4;
$contentWidth = 12 - $sidebarWidth;

    ?>
    <div class="cms-template-sidebar cms-template-sidebar-left">
        <div class="row">
            <div class="cms-sidebar col-md-<?=$sidebarWidth?>">
                <?=$sidebar?>
            </div>
            <div class="cms-body col-md-<?=$contentWidth?>">
                <?=$mainbody?>
            </div>
        </div>
    </div>
    <?php

echo $this->load->view('structure/footer', getControllerData());
