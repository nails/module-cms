<?php

/**
 * This class is the "Accordion" CMS widget view
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

$sUuid   = md5(microtime(true)+rand(1,1000));

if (!empty($panels)) {

    //  Developer defined panels
    $aPanels = $panels;

} else {

    //  CMS Defined tabs
    $aTitle  = !empty($title) ? $title : [];
    $aBody   = !empty($body) ? $body : [];
    $aPanels = [];

    for ($i = 0; $i < count($aTitle); $i++) {

        $aPanels[] = [
            'title'     => getFromArray($i, $aTitle),
            'body'      => getFromArray($i, $aBody),
            'collapsed' => $i !== 0,
        ];
    }
}

if (!empty($aPanels)) {

    ?>
    <div class="cms-widget cms-widget-accordion">
        <div class="panel-group" id="<?=$sUuid?>" role="tablist" aria-multiselectable="true">
            <?php

            $iCounter = 0;

            foreach ($aPanels as $aPanel) {

                $sPanelId   = $sUuid . '-' . $iCounter;
                $sCollapsed = $aPanel['collapsed'] ? '' : 'in';

                ?>
                <div class="panel panel-default">
                    <div class="panel-heading" role="tab" id="<?=$sPanelId . '-' . $iCounter?>-heading">
                        <h4 class="panel-title">
                            <a role="button" data-toggle="collapse" data-parent="#<?=$sUuid?>" href="#<?=$sPanelId . '-' . $iCounter?>-body" aria-expanded="<?=$sCollapsed ? 'true' : 'false'?>" aria-controls="<?=$sPanelId . '-' . $iCounter?>-body">
                                <?=$aPanel['title']?>
                            </a>
                        </h4>
                    </div>
                    <div id="<?=$sPanelId . '-' . $iCounter?>-body" class="panel-collapse collapse <?=$sCollapsed?>" role="tabpanel" aria-labelledby="<?=$sPanelId . '-' . $iCounter?>-heading">
                        <div class="panel-body">
                            <?=$aPanel['body']?>
                        </div>
                    </div>
                </div>
                <?php

                $iCounter++;
            }

            ?>
        </div>
    </div>
    <?php

}
