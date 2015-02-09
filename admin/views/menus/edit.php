<div class="group-cms menus edit">
    <?=form_open()?>
    <fieldset>
        <legend>Details</legend>
        <?php

            $field                = array();
            $field['key']         = 'label';
            $field['label']       = 'Label';
            $field['default']     = isset($menu->label) ? $menu->label : '';
            $field['required']    = true;
            $field['placeholder'] = 'The label to give this menu, for easy reference';

            echo form_field($field);

            // --------------------------------------------------------------------------

            $field                = array();
            $field['key']         = 'description';
            $field['label']       = 'Description';
            $field['default']     = isset($menu->description) ? $menu->description : '';
            $field['placeholder'] = 'Describe the purpose of this menu';

            echo form_field($field);


        ?>
    </fieldset>
    <fieldset>
        <legend>Items</legend>
        <div class="nested-sortable">
            <ol class="nested-sortable"></ol>
            <p>
                <a href="#" class="add-item awesome small green">Add Menu Item</a>
            </p>
        </div>
    </fieldset>
    <?php

        echo form_submit('submit', lang('action_save_changes'), 'class="awesome"');
        echo form_close();

    ?>
</div>
<script type="text/template" id="template-item">
    <li class="target target-{{id}}" data-id="{{id}}">
        <div class="item">
            <div class="handle">
                <span class="fa fa-arrows"></span>
            </div>
            <div class="content">
            <?php

                echo '<input type="hidden" name="menuItem[{{counter}}][id]" value="{{id}}" class="input-id" />';
                echo '<input type="hidden" name="menuItem[{{counter}}][parent_id]" value="{{parent_id}}" class="input-parent_id" />';
                echo '<input type="hidden" name="menuItem[{{counter}}][order]" value="{{order}}" class="input-order" />';

                echo '<div class="containerLabel">';
                    echo form_input(
                        'menuItem[{{counter}}][label]',
                        '{{label}}',
                        'placeholder="The label to give this menu item" class="input-label"'
                    );
                echo '</div>';
                echo '<div class="containerUrl">';
                    echo form_input(
                        'menuItem[{{counter}}][url]',
                        '{{url}}',
                        'placeholder="The URL this menu item should link to" class="input-url"'
                    );
                    echo '<div class="or">Or</div>';
                    echo form_dropdown(
                        'menuItem[{{counter}}][page_id]',
                        $pages
                    );
                echo '</div>';

            ?>
            </div>
            <div class="actions">
                <a href="#" class="awesome small red item-remove">Remove</a
            </div>
        </div>
        <ol class="nested-sortable-sub"></ol>
    </li>
</script>