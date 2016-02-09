<?php

$aTitle = !empty($title) ? $title : array();
$aBody  = !empty($body) ? $body : array();
$aTabs  = array();

for ($i = 0; $i < count($aTitle); $i++) {

    $aTabs[] = array(
        'title' => getFromArray($i, $aTitle),
        'body'  => getFromArray($i, $aBody)
    );
}

$sTabs = htmlentities(json_encode($aTabs), ENT_QUOTES);

?>
<ol class="nails-cms-widget-editor-tabs" data-prefill="<?=$sTabs?>">
    <li class="add-tab">
        <a href="#" class="add-tab js-action-add-tab">
            <b class="fa fa-plus"></b>
        </a>
    </li>
</ol>
<section class="nails-cms-widget-editor-tabs"></section>
<script type="text/x-template" class="tpl-tab">
    <li class="tab">
        <a href="#" class="switch-tab js-action-switch-tab" data-index="{{index}}">
            Tab
        </a>
        <a href="#" class="remove-tab js-action-remove-tab" data-index="{{index}}">
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