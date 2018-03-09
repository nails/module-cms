<div class="group-cms blocks edit">
    <fieldset>
        <legend>Details</legend>
        <table>
            <thead>
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
                    <td><?=$block->label?></td>
                    <td><?=$block->slug?></td>
                    <td><?=$block->description?></td>
                    <td><?=$block->located?></td>
                    <td><?=$blockTypes[$block->type]?></td>
                </tr>
            </tbody>
        </table>
    </fieldset>
    <?=form_open()?>
    <fieldset>
        <legend>Value</legend>
        <?php

        //  Render the correct display
        switch ($block->type) {

            case 'plaintext':

                echo form_textarea('value', set_value('value', $block->value));
                break;

            case 'richtext':

                echo form_textarea('value', set_value('value', $block->value, false), 'class="wysiwyg"');
                break;

            case 'image':

                $field            = array();
                $field['key']     = 'value';
                $field['bucket']  = 'cms-block-' . $block->slug;
                $field['default'] = $block->value;

                echo form_field_cdn_object_picker($field);
                break;

            case 'file':

                $field            = array();
                $field['key']     = 'value';
                $field['bucket']  = 'cms-block-' . $block->slug;
                $field['default'] = $block->value;

                echo form_field_cdn_object_picker($field);
                break;

            case 'email':

                echo form_email('value', set_value('value', $block->value));
                break;

            case 'number':

                echo form_number('value', set_value('value', $block->value));
                break;

            case 'url':

                echo form_url('value', set_value('value', $block->value));
                break;
        }

        ?>
    </fieldset>
    <p>
        <?=form_submit('submit', lang('action_save_changes'), 'class="btn btn-primary"')?>
    </p>
    <?=form_close()?>
</div>
<script type="text/template" id="template-translation">
    <legend>
        <?=form_dropdown('new_translation[{{new_count}}][language]', $languages)?>
        <a href="#" class="remove-translation">Remove Translation</a>
    </legend>
    <div class="alert alert-danger">
        <strong>Oops!</strong> Please ensure a language and value is set.
    </div>
    <textarea name="new_translation[{{new_count}}][value]" id="translation_{{new_count}}"></textarea>
</script>
