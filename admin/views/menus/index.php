<div class="group-cms menus overview">
    <p>
        Listed below are all the editable menus on site.
    </p>
    <?php

        echo adminHelper('loadSearch', $search);
        echo adminHelper('loadPagination', $pagination);

    ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="label">Menu</th>
                    <th class="user">Modified By</th>
                    <th class="datetime">Modified</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php

                if ($menus) {

                    foreach ($menus as $menu) {

                        echo '<tr class="menu" data-label="' . $menu->label . '">';
                            echo '<td class="label">';
                                echo $menu->label;
                                echo $menu->description ? '<small>' . $menu->description . '</small>' : '';
                            echo '</td>';
                            echo adminHelper('loadUserCell', $menu->modified_by);
                            echo adminHelper('loadDatetimeCell', $menu->modified);
                            echo '<td class="actions">';

                                if (userHasPermission('admin:cms:menus:edit')) {

                                    echo anchor(
                                        'admin/cms/menus/edit/' . $menu->id,
                                        lang('action_edit'),
                                        'class="awesome small"'
                                    );
                                }

                                if (userHasPermission('admin:cms:menus:delete')) {

                                    echo anchor(
                                        'admin/cms/menus/delete/' . $menu->id,
                                        lang('action_delete'),
                                        'data-body="This will remove the menu from the site. This action cannot be undone." class="confirm awesome small red"'
                                    );
                                }

                            echo '</td>';
                        echo '</tr>';
                    }

                } else {

                    echo '<tr>';
                        echo '<td colspan="4" class="no-data">';
                            echo 'No editable menus found';
                        echo '</td>';
                    echo '</tr>';
                }

            ?>
            </tbody>
        </table>
    </div>
</div>
