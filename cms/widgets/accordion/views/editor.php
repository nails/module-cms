<?php

$aTitle  = !empty($title) ? $title : array();
$aBody   = !empty($body) ? $body : array();
$aPanels = array();

for ($i = 0; $i < count($aTitle); $i++) {

    $aPanels[] = array(
        'title'     => getFromArray($i, $aTitle),
        'body'      => getFromArray($i, $aBody),
    );
}

$sPanels = htmlentities(json_encode($aPanels), ENT_QUOTES);

?>
<ol class="nails-cms-widget-editor-accordion" data-prefill="<?=$sPanels?>">
    <li class="add-panel">
        <a href="#" class="add-panel js-action-add-panel">
            <b class="fa fa-plus"></b>
        </a>
    </li>
</ol>
<section class="nails-cms-widget-editor-accordion"></section>
<script type="text/x-template" class="tpl-panel">
    <li class="panel">
        <a href="#" class="switch-panel js-action-switch-panel" data-index="{{index}}">
            Panel
        </a>
        <a href="#" class="remove-panel js-action-remove-panel" data-index="{{index}}">
            <b class="fa fa-times"></b>
        </a>
    </li>
</script>
<script type="text/x-template" class="tpl-fieldset">
    <div class="fieldset hidden" data-index="{{index}}">
        <?php

        echo form_field(
            array(
                'key'     => 'title[]',
                'label'   => 'Title',
                'default' => '{{title}}'
            )
        );

        echo form_field_wysiwyg(
            array(
                'key'     => 'body[]',
                'label'   => 'Body',
                'default' => '{{body}}'
            )
        );

        ?>
    </div>
</script>