<div class="group-cms sliders edit">
    <?=form_open()?>
    <fieldset>
        <legend>Slider Details</legend>
        <?php


            $field                = array();
            $field['key']         = 'label';
            $field['label']       = 'Label';
            $field['placeholder'] = 'Give the slider a title';
            $_field['required']   = true;
            $field['default']     = isset($slider->label) ? $slider->label : '';

            echo form_field($field);

            // --------------------------------------------------------------------------

            $field                = array();
            $field['key']         = 'description';
            $field['label']       = 'Description';
            $field['placeholder'] = 'Describe the purpose of the slider';
            $field['default']     = isset($slider->description) ? $slider->description : '';

            echo form_field($field);

        ?>
    </fieldset>
    <fieldset>
        <legend>Slides</legend>
        <table id="slides">
            <thead>
                <tr>
                    <th class="order">Order</th>
                    <th class="image">Image</th>
                    <th class="caption">Caption</th>
                    <th class="link">Link</th>
                    <th class="remove">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
        <p>
            <a href="#" class="awesome small orange" id="addSlide">
                + Add Slide
            </a>
        </p>
    </fieldset>
    <p>
        <?=form_submit('submit', lang('action_save_changes'), 'class="awesome"');?>
    </p>
    <?=form_close();?>
</div>
<script type="text/template" id="templateSlideRow">
<tr>
    <td class="order sortHandle">
        <b class="fa fa-bars fa-lg"></b>
        <input type="hidden" style="width:20px;" name="slideId[]" value="{{id}}" />
    </td>
    <td class="image">
        {{#object_id}}
            <a href="{{imgSourceUrl}}" class="fancybox">
                <img src="{{imgThumbUrl}}" />
            </a>
        {{/object_id}}
        <a href="#" class="btnSetImg awesome small green">
            Set Image
        </a>
        <a href="#" class="btnRemoveImg awesome small red {{^object_id}}hidden{{/object_id}}">
            Remove Image
        </a>
        <input type="hidden" name="objectId[]" value="{{object_id}}" />
    </td>
    <td class="caption">
        <textarea name="caption[]">{{caption}}</textarea>
    </td>
    <td class="link">
        <input type="text" name="url[]" value="{{url}}" />
    </td>
    <td class="remove">
        <a href="#" class="btnRemoveSlide">
            <b class="fa fa-lg fa-times-circle"></b>
        </a>
    </td>
</tr>
</script>
