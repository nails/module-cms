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

        echo form_field_dropdown($field, $block_types);

        // --------------------------------------------------------------------------

        $field                = array();
        $field['key']         = 'value';
        $field['label']       = 'Default Value';
        $field['required']    = true;
        $field['id']          = 'default_value';
        $field['placeholder'] = 'Define the default value';

        echo form_field_textarea($field);

        ?>
    </fieldset>
    <p>
        <?=form_submit('submit', lang('action_create'), 'class="awesome"')?>
    </p>
    <?=form_close()?>
</div>