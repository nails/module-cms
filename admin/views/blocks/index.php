<div class="group-cms blocks overview">
    <p>
        Blocks allow you to update a single piece of content. Blocks might appear in more than one place so
        any updates will be reflected across all instances. Blocks can be used within the code using the
        <code>cms_render_block()</code> function made available by the CMS helper. Blocks may also be used
        within page content by using the block's slug within the shortcode, e.g., <code>[:example-slug:]</code>
        would render the block whose slug was example-slug.
    </p>
    <hr />
    <?php

        echo \Nails\Admin\Helper::loadSearch($search);
        echo \Nails\Admin\Helper::loadPagination($pagination);

    ?>
    <table>
        <thead>
            <tr>
                <th class="label">Block Title &amp; Description</th>
                <th class="location">Location</th>
                <th class="type">Type</th>
                <th class="default">Value</th>
                <th class="datetime">Modified</th>
                <th class="actions">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php

            if ($blocks) {

                foreach ($blocks as $block) {

                    echo '<tr class="block">';

                        echo '<td class="label">';
                            echo '<strong>' . $block->label . '</strong>';
                            echo '<small>';
                            echo 'Slug: ' . $block->slug . '<br />';
                            echo 'Description: ' . $block->description . '<br />';
                            echo '</small>';
                        echo '</td>';
                        echo '<td class="default">';
                            echo $block->located;
                        echo '</td>';
                        echo '<td class="type">';
                            echo $block_types[$block->type];
                        echo '</td>';
                        echo '<td class="default">';
                            echo character_limiter(strip_tags($block->value), 100);
                        echo '</td>';
                        echo \Nails\Admin\Helper::loadDatetimeCell($block->modified);
                        echo '<td class="actions">';
                            echo anchor('admin/cms/blocks/edit/' . $block->id, 'Edit', 'class="awesome small"');
                            echo anchor('admin/cms/blocks/delete/' . $block->id, 'Delete', 'class="awesome small red confirm" data-title="Are you sure?" data-body="This action cannot be undone."');
                        echo '</td>';

                    echo '</tr>';
                }

            } else {

                echo '<tr>';
                    echo '<td colspan="6" class="no-data">';
                        echo 'No editable blocks found';
                    echo '</td>';
                echo '</tr>';
            }

        ?>
        </tbody>
    </table>
    <?php

        echo \Nails\Admin\Helper::loadPagination($pagination);

    ?>
</div>