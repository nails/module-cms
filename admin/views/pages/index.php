<div class="group-cms pages overview">
    <p>
        Browse editable pages.
    </p>
    <?php

        echo adminHelper('loadSearch', $search);
        echo adminHelper('loadPagination', $pagination);

    ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="title">Page</th>
                    <th class="user">Modified By</th>
                    <th class="datetime">Modified</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php

            if ($pages) {

                foreach ($pages as $oPage) {

                    ?>
                    <tr class="page">
                        <td class="title indentosaurus <?=$oPage->draft->depth ? 'indented' : ''?>">
                            <?=str_repeat('<div class="indentor"></div>', $oPage->draft->depth)?>
                            <div class="indentor-content">
                            <?php

                            /**
                             * A little feedback on the status of the page:
                             * - If it's in draft state then simply show it's in draft
                             * - If it's published and there are unpublished changes then indicate that
                             */

                            if ($oPage->is_published) {

                                $sPublishedHash = !empty($oPage->published->hash) ? $oPage->published->hash : 'NOHASH';
                                $sDraftHash     = !empty($oPage->draft->hash) ? $oPage->draft->hash : 'NOHASH' ;

                                if ($sPublishedHash !== $sDraftHash) {

                                    echo '<strong class="label label-unpublished-changes" rel="tipsy" title="This page is visible on site but changes have been made which have not been published.">Unpublished Changes</strong>';
                                }

                            } else {

                                echo '<strong class="label label-draft" rel="tipsy" title="This page has not been published. It is not available to your site\'s visitors.">Draft</strong>';
                            }

                            echo anchor('admin/cms/pages/edit/' . $oPage->id, $oPage->draft->title);

                            $aBreadcrumbs = $oPage->draft->breadcrumbs;
                            array_pop($aBreadcrumbs);

                            echo '<small class="text-muted">';
                            if ($aBreadcrumbs) {

                                $aOut = array();

                                foreach ($aBreadcrumbs as $oCrumb) {

                                    $aOut[] = $oCrumb->title;
                                }

                                echo implode(' // ', $aOut);

                            } else {

                                echo 'Top Level Page';
                            }

                            echo '</small>';

                            ?>
                            </div>
                        </td>
                        <?=adminHelper('loadUserCell', $oPage->modified_by)?>
                        <?=adminHelper('loadDatetimeCell', $oPage->modified)?>
                        <td class="actions">
                            <?php

                            if ($oPage->is_published) {

                                echo anchor(
                                    $oPage->published->url,
                                    lang('action_view'),
                                    'class="btn btn-xs btn-default" target="cms-page-' . $oPage->id . '"'
                                );
                            }

                            if (userHasPermission('admin:cms:pages:edit')) {

                                echo anchor(
                                    'admin/cms/pages/edit/' . $oPage->id,
                                    lang('action_edit'),
                                    'class="btn btn-xs btn-primary"'
                                );

                                if (!$oPage->is_published || $sPublishedHash !== $sDraftHash) {

                                    echo anchor(
                                        'admin/cms/pages/publish/' . $oPage->id,
                                        lang('action_publish'),
                                        'data-body="Publish this page immediately?" class="confirm btn btn-xs btn-success"'
                                    );
                                }
                            }

                            if (userHasPermission('admin:cms:pages:delete')) {

                                echo anchor(
                                    'admin/cms/pages/delete/' . $oPage->id,
                                    lang('action_delete'),
                                    'data-body="This will remove the page, and any of it\'s children, from the site." class="confirm btn btn-xs btn-danger"'
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
                    <td colspan="4" class="no-data">
                        No editable pages found
                    </td>
                </tr>
                <?php

            }

            ?>
            </tbody>
        </table>
    </div>
    <?php

        echo adminHelper('loadPagination', $pagination);

    ?>
</div>
