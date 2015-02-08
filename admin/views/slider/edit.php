<div class="group-cms sliders edit">
    <?=form_open()?>
    <fieldset>
        <legend>Slider Details</legend>
        <?php


            $field                = array();
            $field['key']         = 'label';
            $field['label']       = 'Label';
            $field['required']    = true;
            $field['placeholder'] = 'Give the slider a title';
            $field['default']     = isset($slider->label) ? $slider->label : '';

            echo form_field($field);

            // --------------------------------------------------------------------------

            $field                = array();
            $field['key']         = 'description';
            $field['label']       = 'Description';
            $field['placeholder'] = 'Describe the purpose of the slider';
            $field['default']     = isset($slider->description) ? $slider->description : '';

            echo form_field_textarea($field);

        ?>
    </fieldset>
    <fieldset>
        <legend>Slides</legend>
        <table>
            <thead>
                <tr>
                    <th class="order">&nbsp;</th>
                    <th class="image">Image</th>
                    <th class="caption">Caption</th>
                    <th class="link">Link</th>
                </tr>
            </thead>
            <tbody>
                <?php

                    if (!empty($slider->slides)) {

                        foreach ($slider->slides as $slide) {

                            echo '<tr>';
                                echo '<td class="order">';
                                    echo '<b class="fa fa-bars fa-lg"></b>';
                                echo '</td>';
                                echo '<td class="image">';
                                    if ($slide->object_id) {

                                        echo img(cdn_scale($slide->object_id, 130, 130));
                                        echo '<a href="#" class="awesome small orange">Change</a>';
                                        echo '<a href="' . cdn_serve($slide->object_id) . '" class="awesome small green fancybox">Fullsize</a>';

                                    } else {

                                        echo '<a href="#" class="awesome small orange">Add Image</a>';
                                    }

                                echo '</td>';
                                echo '<td class="caption">';
                                    echo '<textarea name="">' . set_value('', $slide->caption) . '</textarea>';
                                echo '</td>';
                                echo '<td class="link">';
                                    echo form_input('', set_value('', $slide->url));
                                echo '</td>';
                            echo '</tr>';
                        }
                    }
                ?>
            </tbody>
        </table>
        <p>
            <a href="#" class="awesome small orange">
                + Add Slide
            </a>
        </p>
    </fieldset>
    <p>
        <?=form_submit('submit', lang('action_save_changes'), 'class="awesome"');?>
    </p>
    <?=form_close();?>
</div>
<script type="text/template" id="slide-row">
<tr>
    <td class="order">
        [...]
    </td>
    <td class="image">
        [...]
    </td>
    <td class="caption">
        [...]
    </td>
    <td class="link">
        [...]
    </td>
</tr>
</script>
