<?php

/**
 * @var string[] $aAreas
 * @var int|null $iAreaId
 */

if (empty($aAreas)) {
    ?>
    <div class="alert alert-warning">
        <strong>No Areas Available:</strong> Create an area in the "Areas" section of admin.
    </div>
    <?php
} else {
    echo form_field_dropdown([
        'key'     => 'iAreaId',
        'label'   => 'Area',
        'class'   => 'select2',
        'default' => $iAreaId,
        'options' => $aAreas,
    ]);
}
