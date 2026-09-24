<?php
/**
 * @var \Nails\Cms\Resource\Block $oItem
 * @var string[]                  $aTypes
 */

if (empty($oItem)) {
    require sprintf(
        '%sadmin/views/DefaultController/edit.php',
        \Nails\Components::getBySlug(\Nails\Admin\Constants::MODULE_SLUG)->path
    );
} else {
    ?>
    <div class="group-cms blocks edit">
        <fieldset>
            <legend>Details</legend>
            <table class="table table-striped table-hover table-responsive mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Label</th>
                        <th>Slug</th>
                        <th>Description</th>
                        <th>Located</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?=$oItem->label?></td>
                        <td><?=$oItem->slug?></td>
                        <td><?=$oItem->description?></td>
                        <td><?=$oItem->located?></td>
                        <td><?=$aTypes[$oItem->type] ?? $oItem->type?></td>
                    </tr>
                </tbody>
            </table>
        </fieldset>
        <?=form_open()?>
        <?=form_hidden('mode', 'edit')?>
        <?=form_hidden('type', $oItem->type)?>
        <?php

        $sValue = set_value('value', $oItem->value, false);

        switch ($oItem->type) {

            case \Nails\Cms\Model\Block::TYPE_PLAINTEXT:
                echo form_field_textarea([
                    'key'     => 'value',
                    'label'   => 'Value',
                    'default' => $sValue,
                ]);
                break;

            case \Nails\Cms\Model\Block::TYPE_RICHTEXT:
                echo form_field_wysiwyg([
                    'key'     => 'value',
                    'label'   => 'Value',
                    'default' => $sValue,
                ]);
                break;

            case \Nails\Cms\Model\Block::TYPE_IMAGE:
            case \Nails\Cms\Model\Block::TYPE_FILE:
                echo form_field_cdn_object_picker([
                    'key'     => 'value',
                    'label'   => 'Value',
                    'default' => (int) $oItem->value ?: null,
                ]);
                break;

            case \Nails\Cms\Model\Block::TYPE_EMAIL:
                echo form_field_email([
                    'key'     => 'value',
                    'label'   => 'Value',
                    'default' => $sValue,
                ]);
                break;

            case \Nails\Cms\Model\Block::TYPE_NUMBER:
                echo form_field_number([
                    'key'     => 'value',
                    'label'   => 'Value',
                    'default' => $sValue,
                ]);
                break;

            case \Nails\Cms\Model\Block::TYPE_URL:
                echo form_field_url([
                    'key'     => 'value',
                    'label'   => 'Value',
                    'default' => $sValue,
                ]);
                break;
        }

        echo \Nails\Admin\Helper::floatingControls($CONFIG['FLOATING_CONFIG']);
        echo form_close();

        ?>
    </div>
    <?php
}
