<?php
$oInput = \Nails\Factory::service('Input');
?>
<div class="group-cms blocks create">
    <?=form_open()?>
    <fieldset>
        <legend>Block details</legend>
        <?php

        //  Slug
        $field                = array();
        $field['key']         = 'slug';
        $field['label']       = 'Slug';
        $field['default']     = '';
        $field['required']    = true;
        $field['placeholder'] = 'The block\'s unique slug';

        echo form_field($field);

        // --------------------------------------------------------------------------

        //  Title
        $field                = array();
        $field['key']         = 'label';
        $field['label']       = 'Label';
        $field['default']     = '';
        $field['required']    = true;
        $field['placeholder'] = 'The Human friendly block title';

        echo form_field($field);

        // --------------------------------------------------------------------------

        //  Description
        $field                = array();
        $field['key']         = 'description';
        $field['label']       = 'Description';
        $field['default']     = '';
        $field['placeholder'] = 'A description of what this block\'s value should be';

        echo form_field($field);

        // --------------------------------------------------------------------------

        //  Located
        $field                = array();
        $field['key']         = 'located';
        $field['label']       = 'Located';
        $field['default']     = '';
        $field['placeholder'] = 'A brief outline of where this block might be used';

        echo form_field($field);

        // --------------------------------------------------------------------------

        //  Block Type
        $field             = array();
        $field['key']      = 'type';
        $field['label']    = 'Block Type';
        $field['required'] = true;
        $field['class']    = 'select2';

        echo form_field_dropdown($field, $blockTypes);

        // --------------------------------------------------------------------------

        $field                = array();
        $field['key']         = 'value_plaintext';
        $field['label']       = 'Default Value';
        $field['placeholder'] = 'Define the default value';

        $sDisplay = set_value('type') == 'plaintext' || !$oInput->post() ? 'block' : 'none';
        echo '<div id="default-value-plaintext" class="default-value" style="display:' . $sDisplay . ';">';
            echo form_field_textarea($field);
        echo '</div>';

        // --------------------------------------------------------------------------

        $field                = array();
        $field['key']         = 'value_richtext';
        $field['label']       = 'Default Value';
        $field['id']          = 'default-value-richtext-editor';
        $field['placeholder'] = 'Define the default value';

        $sDisplay = set_value('type') == 'richtext' ? 'block' : 'none';
        echo '<div id="default-value-richtext" class="default-value" style="display:' . $sDisplay . ';">';
            echo form_field_textarea($field);
        echo '</div>';

        // --------------------------------------------------------------------------

        $field                = array();
        $field['key']         = 'value_image';
        $field['label']       = 'Default Value';
        $field['placeholder'] = 'Define the default value';
        $field['readonly']    = true;
        $field['info']        = 'Blocks of type "Image" cannot have a default value set.';

        $sDisplay = set_value('type') == 'image' ? 'block' : 'none';
        echo '<div id="default-value-image" class="default-value" style="display:' . $sDisplay . ';">';
            echo form_field($field);
        echo '</div>';

        // --------------------------------------------------------------------------

        $field                = array();
        $field['key']         = 'value_file';
        $field['label']       = 'Default Value';
        $field['placeholder'] = 'Define the default value';
        $field['readonly']    = true;
        $field['info']        = 'Blocks of type "File" cannot have a default value set.';

        $sDisplay = set_value('type') == 'file' ? 'block' : 'none';
        echo '<div id="default-value-file" class="default-value" style="display:' . $sDisplay . ';">';
            echo form_field($field);
        echo '</div>';

        // --------------------------------------------------------------------------

        $field                = array();
        $field['key']         = 'value_number';
        $field['label']       = 'Default Value';
        $field['placeholder'] = 'Define the default value';

        $sDisplay = set_value('type') == 'plaintext' ? 'block' : 'none';
        echo '<div id="default-value-number" class="default-value" style="display:' . $sDisplay . ';">';
            echo form_field_number($field);
        echo '</div>';

        // --------------------------------------------------------------------------

        $field                = array();
        $field['key']         = 'value_url';
        $field['label']       = 'Default Value';
        $field['placeholder'] = 'Define the default value';

        $sDisplay = set_value('type') == 'url' ? 'block' : 'none';
        echo '<div id="default-value-url" class="default-value" style="display:' . $sDisplay . ';">';
            echo form_field_url($field);
        echo '</div>';

        // --------------------------------------------------------------------------

        $field                = array();
        $field['key']         = 'value_email';
        $field['label']       = 'Default Value';
        $field['placeholder'] = 'Define the default value';

        $sDisplay = set_value('type') == 'url' ? 'block' : 'none';
        echo '<div id="default-value-email" class="default-value" style="display:' . $sDisplay . ';">';
            echo form_field_email($field);
        echo '</div>';

        ?>
    </fieldset>
    <p>
        <?=form_submit('submit', lang('action_create'), 'class="btn btn-primary"')?>
    </p>
    <?=form_close()?>
</div>
