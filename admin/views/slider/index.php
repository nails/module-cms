<div class="group-cms sliders overview">
    <p>
        Listed below are all the editable sliders on site.
    </p>
    <?php

        echo adminHelper('loadSearch', $search);
        echo adminHelper('loadPagination', $pagination);

    ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="title">Slider</th>
                    <th class="user">Modified By</th>
                    <th class="datetime">Modified</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php

                if ($sliders) {

                    foreach ($sliders as $slider) {

                        echo '<tr class="slider" data-label="' . $slider->label . '">';
                            echo '<td class="label">';
                                echo $slider->label;
                                echo $slider->description ? '<small>' . $slider->description . '</small>' : '';
                            echo '</td>';

                            echo adminHelper('loadUserCell', $slider->modified_by);
                            echo adminHelper('loadDatetimeCell', $slider->modified);

                            echo '<td class="actions">';

                                if (userHasPermission('admin:cms:slider:edit')) {

                                    echo anchor(
                                        'admin/cms/slider/edit/' . $slider->id,
                                        lang('action_edit'),
                                        'class="awesome small"'
                                    );
                                }

                                if (userHasPermission('admin:cms:slider:delete')) {

                                    echo anchor(
                                        'admin/cms/slider/delete/' . $slider->id,
                                        lang('action_delete'),
                                        'data-body="This will remove the slider from the site. This action can be undone." class="confirm awesome small red"'
                                    );
                                }

                            echo '</td>';
                        echo '</tr>';
                    }

                } else {

                    echo '<tr>';
                        echo '<td colspan="4" class="no-data">';
                            echo 'No editable sliders found';
                        echo '</td>';
                    echo '</tr>';
                }

            ?>
            </tbody>
        </table>
    </div>
</div>