<?php

/**
 * This is the "Sidebar" CMS template view
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Template
 * @author      Nails Dev Team
 * @link
 */

$oView = \Nails\Factory::service('View');
echo $oView->load('structure/header', getControllerData(), true);

$sidebarSide  = !empty($sidebarSide) ? $sidebarSide : 'LEFT';
$sidebarWidth = !empty($sidebarWidth) ? (int) $sidebarWidth : 4;
$contentWidth = 12 - $sidebarWidth;

    ?>
    <div class="cms-template-sidebar cms-template-sidebar-<?=strtolower($sidebarSide)?>">
        <div class="row">
            <?php

            if ($sidebarSide === 'LEFT') {

                ?>
                <div class="cms-sidebar col-md-<?=$sidebarWidth?>">
                    <?=$sidebar?>
                </div>
                <?php

            }

            ?>
            <div class="cms-body col-md-<?=$contentWidth?>">
                <?=$mainbody?>
            </div>
            <?php

            if ($sidebarSide === 'RIGHT') {

                ?>
                <div class="cms-sidebar col-md-<?=$sidebarWidth?>">
                    <?=$sidebar?>
                </div>
                <?php

            }

            ?>
        </div>
    </div>
    <?php

echo $oView->load('structure/footer', getControllerData(), true);
