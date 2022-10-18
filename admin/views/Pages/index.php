<?php

use Nails\Admin\Helper;

?>
<div class="group-cms pages overview">
    <?=Helper::loadSearch($search)?>
    <?=Helper::loadPagination($pagination)?>
    <table class="table table-striped table-hover table-bordered table-responsive">
        <thead class="table-dark">
            <tr>
                <th class="page-title">Page</th>
                <th class="user">Modified By</th>
                <th class="datetime">Modified</th>
                <th class="actions">Actions</th>
            </tr>
        </thead>
        <tbody class="align-middle">
            <?php

            if ($pages) {
                foreach ($pages as $oPage) {

                    ?>
                    <tr class="page">
                        <td class="page-title indentosaurus <?=$oPage->draft->depth ? 'indented' : ''?>">
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
                                        <strong class="label label-unpublished-changes hint--right"
                                                aria-label="This page is visible on site but changes have been made which have not been published."
                                        >
                                            Unpublished Changes
                                        </strong>
                                        <?php
                                    }

                                } else {
                                    ?>
                                    <strong class="label label-draft hint--right"
                                            aria-label="This page has not been published. It is not available to your site's visitors."
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

                                echo anchor(
                                    \Nails\Cms\Admin\Controller\Pages::url('edit/' . $oPage->id),
                                    $oPage->draft->title
                                );

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
                        <?=Helper::loadUserCell($oPage->modified_by)?>
                        <?=Helper::loadDatetimeCell($oPage->modified)?>
                        <td class="actions">
                            <?php

                            if ($oPage->is_published) {
                                echo anchor(
                                    $oPage->published->url,
                                    lang('action_view'),
                                    'class="btn btn-xs btn-default" target="cms-page-' . $oPage->id . '"'
                                );
                            }

                            if (userHasPermission(\Nails\Cms\Admin\Permission\Page\Edit::class)) {
                                echo anchor(
                                    \Nails\Cms\Admin\Controller\Pages::url('edit/' . $oPage->id),
                                    lang('action_edit'),
                                    'class="btn btn-xs btn-primary"'
                                );

                                if (!$oPage->is_published || $sPublishedHash !== $sDraftHash) {
                                    echo anchor(
                                        \Nails\Cms\Admin\Controller\Pages::url('publish/' . $oPage->id . '?return_to=' . $sReturnTo),
                                        lang('action_publish'),
                                        'data-body="Publish this page immediately?" class="confirm btn btn-xs btn-success"'
                                    );
                                } else {
                                    echo anchor(
                                        \Nails\Cms\Admin\Controller\Pages::url('unpublish/' . $oPage->id . '?return_to=' . $sReturnTo),
                                        'Unpublish',
                                        'class="btn btn-xs btn-warning"'
                                    );
                                }
                            }

                            if (userHasPermission(\Nails\Cms\Admin\Permission\Page\Create::class)) {
                                echo anchor(
                                    \Nails\Cms\Admin\Controller\Pages::url('copy/' . $oPage->id),
                                    'Duplicate',
                                    'class="btn btn-xs btn-default"'
                                );
                            }

                            if (userHasPermission(\Nails\Cms\Admin\Permission\Page\Delete::class)) {
                                echo anchor(
                                    \Nails\Cms\Admin\Controller\Pages::url('delete/' . $oPage->id . '?return_to=' . $sReturnTo),
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
    <?=Helper::loadPagination($pagination)?>
</div>
