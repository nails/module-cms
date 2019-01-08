<div class="group-cms blocks overview">
    <p>
        Blocks allow you to update a single piece of content. Blocks might appear in more than one place so
        any updates will be reflected across all instances.
        <?php
        if (userHasPermission('admin:cms:pages:create') || userHasPermission('admin:cms:pages:edit')) {
            ?>
            Blocks may also be used within page content by using the block\'s slug within a shortcode, e.g.,
            <code>[:block:example-slug:]</code> would render the block whose slug was <code>example-slug</code>.
            <?php
        }
        ?>
    </p>
    <?=adminHelper('loadSearch', $search)?>
    <?=adminHelper('loadPagination', $pagination)?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="label">Block Title &amp; Description</th>
                    <th class="location">Location</th>
                    <th class="type">Type</th>
                    <th class="value">Value</th>
                    <th class="datetime">Modified</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if (!empty($blocks)) {
                foreach ($blocks as $oBlock) {
                    ?>
                    <tr class="block">
                        <td class="label">
                            <strong><?=$oBlock->label?></strong>
                            <small>
                                Slug: <?=$oBlock->slug?>
                                <?=$oBlock->description ? '<br>Description: ' . $oBlock->description : ''?>
                            </small>
                        </td>
                        <td class="value">
                            <?=$oBlock->located?>
                        </td>
                        <td class="type">
                            <?=$blockTypes[$oBlock->type]?>
                        </td>
                        <td class="default">
                            <?php
                            if (!empty($oBlock->value)) {
                                switch ($oBlock->type) {

                                    case 'image':

                                        echo img(cdnCrop($oBlock->value, 50, 50));
                                        break;

                                    case 'file':

                                        echo anchor(cdnServe($oBlock->value, true), 'Download', 'class="btn btn-xs btn-default"');
                                        break;

                                    default:
                                        echo character_limiter(strip_tags($oBlock->value), 100);
                                        break;
                                }
                            } else {
                                echo '<span class="text-muted">';
                                echo '&mdash;';
                                echo '</span>';
                            }
                            ?>
                        </td>
                        <?=adminHelper('loadDatetimeCell', $oBlock->modified)?>
                        <td class="actions">
                            <?php
                            if (userHasPermission('admin:cms:blocks:edit')) {
                                echo anchor(
                                    'admin/cms/blocks/edit/' . $oBlock->id,
                                    'Edit',
                                    'class="btn btn-xs btn-primary"'
                                );
                            }

                            if (userHasPermission('admin:cms:blocks:delete')) {
                                echo anchor(
                                    'admin/cms/blocks/delete/' . $oBlock->id,
                                    'Delete',
                                    'class="btn btn-xs btn-danger confirm" data-body="This action cannot be undone."'
                                );
                            }
                            ?>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="6" class="no-data">
                        No editable blocks found
                    </td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
    </div>
    <?=adminHelper('loadPagination', $pagination)?>
</div>
