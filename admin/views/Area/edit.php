<div class="group-cms area edit">
    <?=form_open()?>
    <fieldset>
        <legend>Details</legend>
        <?php

            $field                = array();
            $field['key']         = 'label';
            $field['label']       = 'Label';
            $field['default']     = isset($area->label) ? $area->label : '';
            $field['required']    = true;
            $field['placeholder'] = 'The label to give this area, for easy reference';

            echo form_field($field);

            // --------------------------------------------------------------------------

            $field                = array();
            $field['key']         = 'slug';
            $field['label']       = 'Slug';
            $field['default']     = isset($area->slug) ? $area->slug : '';
            $field['placeholder'] = 'The slug, leave blank to auto-generate';

            echo form_field($field);

            // --------------------------------------------------------------------------

            $field                = array();
            $field['key']         = 'description';
            $field['label']       = 'Description';
            $field['default']     = isset($area->description) ? $area->description : '';
            $field['placeholder'] = 'Describe the purpose of this area';

            echo form_field($field);

            // --------------------------------------------------------------------------

            $field                = array();
            $field['key']         = 'widget_data';
            $field['label']       = 'Widgets';
            $field['default']     = isset($area->widget_data) ? $area->widget_data : '';

            echo form_field_cms_widgets($field);

        ?>
    </fieldset>
    <hr />
    <?=form_submit('submit', lang('action_save_changes'), 'class="btn btn-primary"')?>
    <?=form_close()?>
</div>
