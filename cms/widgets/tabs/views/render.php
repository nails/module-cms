<?php

$sUid = md5(microtime(true));

if (!empty($tabs)) {

    //  Developer defined tabs
    $aTabs = $tabs;

} else {

    //  CMS Defined tabs
    $aTitle = !empty($title) ? $title : array();
    $aBody  = !empty($body) ? $body : array();
    $aTabs  = array();


    for ($i = 0; $i < count($aTitle); $i++) {

        $aTabs[] = array(
            'title'     => getFromArray($i, $aTitle),
            'body'      => getFromArray($i, $aBody)
        );
    }
}

?>
<div class="cms-widget nails-cms-widget-tabs">
    <!-- Nav tabs -->
    <ul class="nav nav-tabs" role="tablist">
        <?php

        foreach ($aTabs as $iIndex => $aTab) {

            $sActive = $iIndex == 0 ? 'active' : '';

            ?>
            <li class="tab-item <?=$sActive?>" role="presentation">
                <a href="#<?=$sUid . '-' . $iIndex?>" role="tab" data-toggle="tab">
                    <?=$aTab['title']?>
                </a>
            </li>
            <?php
        }

        ?>
    </ul>

    <!-- Tab panes -->
    <div class="tab-content">
        <?php

        foreach ($aTabs as $iIndex => $aTab) {

            $sActive = $iIndex == 0 ? 'active' : '';

            ?>
            <div role="tabpanel" class="tab-pane <?=$sActive?>" id="<?=$sUid . '-' . $iIndex?>">
                <?=$aTab['body']?>
            </div>
            <?php
        }

        ?>
    </div>
</div>