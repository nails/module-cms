<?php

/**
 * @var string[] $title
 * @var string[] $body
 * @var array[]  $aPanels
 * @var string   $sUuid
 */

?>
<ol class="nails-cms-widget-editor-accordion" data-prefill="<?=htmlentities(json_encode($aPanels), ENT_QUOTES)?>">
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

    echo form_field([
        'key'     => 'title[]',
        'label'   => 'Title',
        'default' => '{{title}}',
    ]);

    echo form_field_wysiwyg([
        'key'     => 'body[]',
        'label'   => 'Body',
        'default' => '{{body}}',
    ]);

    echo form_field_dropdown([
        'key'     => 'state[]',
        'label'   => 'State',
        'options' => [
            'CLOSED' => 'Closed',
            'OPEN'   => 'Open',
        ],
        'class'   => 'select2',
        'data'    => [
            'value' => '{{state}}',
        ],
    ]);

    ?>
</div>
</script>
