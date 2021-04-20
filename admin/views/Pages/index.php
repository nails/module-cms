<div class="group-cms pages overview">
    <p>
        Browse editable pages.
    </p>
    <?=adminHelper('loadSearch', $search)?>
    <?=adminHelper('loadPagination', $pagination)?>
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
                                        $sDraftHash     = !empty($oPage->draft->hash) ? $oPage->draft->hash : 'NOHASH';

                                        if ($sPublishedHash !== $sDraftHash) {
                                            ?>
                                            <strong class="label label-unpublished-changes hint--bottom"
                                                    aria-label="This page is visible on site but changes have been made which have not been published."
                                            >
                                                Unpublished Changes
                                            </strong>
                                            <?php
                                        }

                                    } else {
                                        ?>
                                        <strong class="label label-draft hint--bottom"
                                                aria-label="This page has not been published. It is not available to your site\'s visitors."
                                        >
                                            Draft
                                        </strong>
                                        <?php
                                    }

                                    if ($oPage->id == $iHomepageId) {
                                        ?>
                                        <strong class="label label-homepage">
                                            Homepage
                                        </strong>
                                        <?php
                                    }

                                    echo anchor('admin/cms/pages/edit/' . $oPage->id, $oPage->draft->title);

                                    $aBreadcrumbs = $oPage->draft->breadcrumbs;
                                    array_pop($aBreadcrumbs);

                                    ?>
                                    <small class="text-muted">
                                        <?php
                                        if ($aBreadcrumbs) {

                                            $aOut = [];
                                            foreach ($aBreadcrumbs as $oCrumb) {
                                                $aOut[] = $oCrumb->title;
                                            }

                                            echo implode(' // ', $aOut);

                                        } else {
                                            echo 'Top Level Page';
                                        }

                                        ?>
                                    </small>
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
                                            'admin/cms/pages/publish/' . $oPage->id . '?return_to=' . $sReturnTo,
                                            lang('action_publish'),
                                            'data-body="Publish this page immediately?" class="confirm btn btn-xs btn-success"'
                                        );
                                    } else {
                                        echo anchor(
                                            'admin/cms/pages/unpublish/' . $oPage->id . '?return_to=' . $sReturnTo,
                                            'Unpublish',
                                            'class="btn btn-xs btn-warning"'
                                        );
                                    }
                                }

                                if (userHasPermission('admin:cms:pages:create')) {
                                    echo anchor(
                                        'admin/cms/pages/copy/' . $oPage->id,
                                        'Duplicate',
                                        'class="btn btn-xs btn-default"'
                                    );
                                }

                                if (userHasPermission('admin:cms:pages:delete')) {
                                    echo anchor(
                                        'admin/cms/pages/delete/' . $oPage->id . '?return_to=' . $sReturnTo,
                                        lang('action_delete'),
                                        'class="btn btn-xs btn-danger"'
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
    <?=adminHelper('loadPagination', $pagination)?>
</div>
