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

        ?>
    </fieldset>
    <fieldset>
        <legend>Widgets</legend>
        <p>
            <a href="#" id="open-widget-editor" class="btn btn-warning btn-block btn-sm">
                Manage Widgets
            </a>
            <?php

            if ($this->input->post('widget_data')) {

                $sDefault = $this->input->post('widget_data');

            } elseif (isset($area->widget_data)) {

                $sDefault = json_encode($area->widget_data);

            } else {

                $sDefault = '';
            }

            ?>
            <input type="hidden" name="widget_data" id="widget-data" value="<?=htmlentities($sDefault)?>" />
        </p>
    </fieldset>
    <hr />
    <?=form_submit('submit', lang('action_save_changes'), 'class="btn btn-primary"')?>
    <?=form_close()?>
</div>