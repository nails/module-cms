<div class="group-cms sliders edit">
    <?=form_open()?>
    <fieldset>
        <legend>Slider Details</legend>
        <?php


            $field                = array();
            $field['key']         = 'label';
            $field['label']       = 'Label';
            $field['placeholder'] = 'Give the slider a title';
            $field['default']     = isset($slider->label) ? $slider->label : '';

            echo form_field($field);

            // --------------------------------------------------------------------------

            $field                = array();
            $field['key']         = 'description';
            $field['label']       = 'Description';
            $field['placeholder'] = 'Describe the purpose of the slider';
            $field['default']     = isset($slider->description) ? $slider->description : '';
            $field['class']       = 'wysiwyg-basic';

            echo form_field_wysiwyg($field);

        ?>
    </fieldset>
    <fieldset>
        <legend>Slides</legend>
        <table id="slides">
            <thead>
                <tr>
                    <th class="order">&nbsp;</th>
                    <th class="image">Image</th>
                    <th class="caption">Caption</th>
                    <th class="link">Link</th>
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
    <td class="order">
        <b class="sortHandle fa fa-bars fa-lg"></b>
        <input type="text" style="width:20px;" name="slideId[]" value="{{id}}" />
    </td>
    <td class="image">
        {{#object_id}}
            <img src="{{imgThumbUrl}}" />
            <a href="#" class="changeImg awesome small orange">Change</a>
            <a href="{{imgSourceUrl}}" class="fancybox awesome small green">Fullsize</a>
        {{/object_id}}
        {{^object_id}}
            <a href="#" class="setImg awesome small orange">Set Image</a>
        {{/object_id}}
        <input type="text" style="width:20px;" name="objectId[]" value="{{object_id}}" />
    </td>
    <td class="caption">
        <textarea name="caption[]">{{caption}}</textarea>
    </td>
    <td class="link">
        <input type="text" name="url[]" value="{{url}}" />
    </td>
</tr>
</script>
