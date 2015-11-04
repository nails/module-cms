<div class="group-cms areas overview">
    <p>
        CMS Areas are small sections of CMS'able content which can be embedded into other parts of the site.
    </p>
    <?php

        echo \Nails\Admin\Helper::loadSearch($search);
        echo \Nails\Admin\Helper::loadPagination($pagination);

    ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="title">Label</th>
                    <th class="user">Modified By</th>
                    <th class="datetime">Modified</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php

                if ($areas) {

                    foreach ($areas as $area) {

                        echo '<tr>';
                            echo '<td class="label">';
                                echo $area->label;
                                echo $area->description ? '<small>' . $area->description . '</small>' : '';
                            echo '</td>';

                            echo \Nails\Admin\Helper::loadUserCell($area->modified_by);
                            echo \Nails\Admin\Helper::loadDatetimeCell($area->modified);

                            echo '<td class="actions">';

                                if (userHasPermission('admin:cms:area:edit')) {

                                    echo anchor(
                                        'admin/cms/area/edit/' . $area->id,
                                        lang('action_edit'),
                                        'class="awesome small"'
                                    );
                                }

                                if (userHasPermission('admin:cms:area:delete')) {

                                    echo anchor(
                                        'admin/cms/area/delete/' . $area->id,
                                        lang('action_delete'),
                                        'data-title="Are you sure?" data-body="This will remove the area from the site. This action can be undone." class="confirm awesome small red"'
                                    );
                                }

                            echo '</td>';
                        echo '</tr>';
                    }

                } else {

                    echo '<tr>';
                        echo '<td colspan="4" class="no-data">';
                            echo 'No editable areas found';
                        echo '</td>';
                    echo '</tr>';
                }

            ?>
            </tbody>
        </table>
    </div>
</div>