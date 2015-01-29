<div class="group-cms menus edit">
    <?=form_open()?>
    <fieldset>
        <legend>Menu Details</legend>
        <p class="system-alert">
            The following details help you identify the purpose and location of this menu in admin.
            They are not used in the front-end.
        </p>
        <?php

            $field                = array();
            $field['key']         = 'label';
            $field['label']       = 'Label';
            $field['default']     = isset($menu->label) ? $menu->label : '';
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
    <hr />
    <p class="system-alert">
        All menu items are shown below. Drag the menu item to nest beneath another item or to change
        the order. A label and a URL is required for each menu item.
    </p>
    <div class="nested-sortable">
        <ol class="nested-sortable"></ol>
        <p>
            <a href="#" class="add-item awesome small green">Add Menu Item</a>
        </p>
    </div>
    <?php

        echo form_submit('submit', lang('action_save_changes'), 'class="awesome"');
        echo form_close();

    ?>
</div>
<?php

    if ($this->input->post()) {

        $menuItems = (array) json_decode(json_encode($this->input->post('menu_item')));
        $menuItems = array_values($menuItems);

    } elseif(isset($menu)) {

        $menuItems = $menu->items;

    } else {

        $menuItems = array();
    }

?>
<script type="text/javascript">
<!--//

    $(function(){

        var CMS_Menus_Create_Edit = new NAILS_Admin_CMS_Menus_Create_Edit;
        CMS_Menus_Create_Edit.init(<?=json_encode($menuItems)?>);

    });

//-->
</script>
<script type="text/template" id="template-item">
    <li class="target target-{{id}}" data-id="{{id}}">
        <div class="item">
            <div class="handle">
                <span class="fa fa-arrows"></span>
            </div>
            <div class="content">
            <?php

                echo '<input type="hidden" name="menu_item[{{counter}}][id]" value="{{id}}" class="input-id" />';
                echo '<input type="hidden" name="menu_item[{{counter}}][parent_id]" value="{{parent_id}}" class="input-parent_id" />';
                echo '<input type="hidden" name="menu_item[{{counter}}][order]" value="{{order}}" class="input-order" />';

                echo form_input('menu_item[{{counter}}][label]', '{{label}}', 'placeholder="The label to give this menu item" class="input-label"');
                echo form_input('menu_item[{{counter}}][url]', '{{url}}', 'placeholder="The URL this menu item should link to" class="input-url"');

            ?>
            </div>
            <div class="actions">
                <a href="#" class="awesome small red item-remove">Remove</a
            </div>
        </div>
        <ol class="nested-sortable-sub"></ol>
    </li>
</script>