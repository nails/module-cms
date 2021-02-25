<div class="group-cms menus edit">
    <?=form_open()?>
    <fieldset>
        <legend>Details</legend>
        <?php

        echo form_field([
            'key'         => 'label',
            'label'       => 'Label',
            'default'     => $oItem->label ?? '',
            'required'    => true,
            'placeholder' => 'The label to give this menu, for easy reference',
        ]);

        echo form_field([
            'key'         => 'description',
            'label'       => 'Description',
            'default'     => $oItem->description ?? '',
            'placeholder' => 'Describe the purpose of this menu',
        ]);

        ?>
    </fieldset>
    <fieldset>
        <legend>Items</legend>
        <div class="nested-sortable">
            <ol class="nested-sortable"></ol>
            <p>
                <a href="#" class="add-item btn btn-xs btn-success">Add Menu Item</a>
            </p>
        </div>
    </fieldset>
    <?=form_submit('submit', lang('action_save_changes'), 'class="btn btn-primary"')?>
    <?=form_close()?>
</div>
<script type="text/template" id="template-item">
<li class="target target-{{id}}" data-id="{{id}}">
    <div class="item">
        <div class="handle">
            <span class="fa fa-arrows"></span>
        </div>
        <div class="content">
            <input type="hidden" name="items[id][]" value="{{id}}" class="input-id" />
            <input type="hidden" name="items[parent_id][]" value="{{parent_id}}" class="input-parent_id" />
            <div class="container-label">
                <?=form_input(
                    'items[label][]',
                    '{{label}}',
                    'placeholder="The label to give this menu item" class="input-label"'
                )?>
            </div>
            <div class="container-url">
                <?=form_input(
                    'items[url][]',
                    '{{url}}',
                    'placeholder="The URL this menu item should link to" class="input-url"'
                )?>
                <div class="or">Or</div>
                <?=form_dropdown(
                    'items[page_id][]',
                    $aPages
                )?>
            </div>
        </div>
        <div class="actions">
            <a href="#" class="btn btn-xs btn-danger item-remove">&times;</a
        </div>
    </div>
    <ol class="nested-sortable-sub"></ol>
</li>
</script>
