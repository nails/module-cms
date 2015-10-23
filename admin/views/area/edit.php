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
            $field['key']         = 'description';
            $field['label']       = 'Description';
            $field['default']     = isset($area->description) ? $area->description : '';
            $field['placeholder'] = 'Describe the purpose of this area';

            echo form_field($field);

        ?>
    </fieldset>
    <fieldset>
        <legend>Widgets</legend>
        <p>
            <a href="#" id="open-widget-editor" class="btn btn-warning btn-block btn-sm">
                Manage Widgets
            </a>
            <input type="hidden" id="widget-data" />
        </p>
    </fieldset>
    <hr />
    <?=form_submit('submit', lang('action_save_changes'), 'class="btn btn-primary"')?>
    <?=form_close()?>
</div>