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
                    <td><?=$block_types[$block->type]?></td>
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

            case 'plaintext' :

                echo form_textarea('value', set_value('value', $block->value));
                break;

            case 'richtext' :

                echo form_textarea('value', set_value('value', $block->value), 'class="wysiwyg"');
                break;
        }

        ?>
    </fieldset>
    <p>
        <?=form_submit('submit', lang('action_save_changes'), 'class="awesome"')?>
    </p>
    <?=form_close()?>
</div>
<script type="text/template" id="template-translation">
    <legend>
        <?=form_dropdown('new_translation[{{new_count}}][language]', $languages)?>
        <a href="#" class="remove-translation">Remove Translation</a>
    </legend>
    <div class="system-alert error">
        <strong>Oops!</strong> Please ensure a language and value is set.
    </div>
    <textarea name="new_translation[{{new_count}}][value]" id="translation_{{new_count}}"></textarea>
</script>