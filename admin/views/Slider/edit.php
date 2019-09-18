<div class="group-cms sliders edit">
    <?=form_open()?>
    <fieldset>
        <legend>Slider Details</legend>
        <?php

        $field                = [];
        $field['key']         = 'label';
        $field['label']       = 'Label';
        $field['placeholder'] = 'Give the slider a title';
        $_field['required']   = true;
        $field['default']     = isset($slider->label) ? $slider->label : '';

        echo form_field($field);

        // --------------------------------------------------------------------------

        $field                = [];
        $field['key']         = 'description';
        $field['label']       = 'Description';
        $field['placeholder'] = 'Describe the purpose of the slider';
        $field['default']     = isset($slider->description) ? $slider->description : '';

        echo form_field($field);

        ?>
    </fieldset>
    <fieldset>
        <legend>Slides</legend>
        <table class="js-admin-dynamic-table" data-data="<?=htmlspecialchars(json_encode(isset($slider) ? $slider->slides : []))?>">
            <thead>
                <tr>
                    <th width="35"></th>
                    <th width="250">Image</th>
                    <th>Caption</th>
                    <th>URL</th>
                    <th width="35"></th>
                </tr>
            </thead>
            <tbody class="js-admin-dynamic-table__template js-admin-sortable" data-handle=".handle">
                <tr>
                    <td class="text-center">
                        <b class="fa fa-bars handle"></b>
                        <input type="hidden" name="items[{{index}}][id]" value="{{id}}">
                        <input type="hidden" name="items[{{index}}][order]" value="{{order}}" class="js-admin-sortable__order">
                    </td>
                    <td>
                        <?=cdnObjectPicker('items[{{index}}][object_id]', 'gallery', '{{object_id}}')?>
                    </td>
                    <td>
                        <input type="text" class="form-input" name="items[{{index}}][caption]" value="{{caption}}">
                    </td>
                    <td>
                        <input type="text" class="form-input" name="items[{{index}}][url]" value="{{url}}">
                    </td>
                    <td class="text-center">
                        <button class="btn btn-xs btn-danger js-admin-dynamic-table__remove">
                            &times;
                        </button>
                    </td>
                </tr>
            </tbody>
            <tbody>
                <tr>
                    <td colspan="4">
                        <button class="btn btn-xs btn-success js-admin-dynamic-table__add">
                            &plus; Add Item
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </fieldset>
    <p>
        <?=form_submit('submit', lang('action_save_changes'), 'class="btn btn-primary"');?>
    </p>
    <?=form_close();?>
</div>
