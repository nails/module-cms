<div class="group-cms pages overview">
    <p>
        Browse editable pages.
    </p>
    <hr />
    <div class="search noOptions">
        <div class="search-text">
            <input type="text" name="search" value="" autocomplete="off" placeholder="Search page titles by typing in here...">
        </div>
    </div>
    <hr />
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

                foreach ($pages as $page) {

                    $_data = $page->draft;

                    // --------------------------------------------------------------------------

                    echo '<tr class="page" data-title="' . htmlentities($page->draft->title) . '">';
                        echo '<td class="title indentosaurus indent-' . $page->draft->depth . '">';

                            echo str_repeat('<div class="indentor"></div>', $page->draft->depth);

                            echo '<div class="indentor-content">';

                                echo anchor('admin/cms/pages/edit/' . $page->id, $page->draft->title);

                                /**
                                 * A little feedback on the status of the page:
                                 * - If it's in draft state then simply show it's in draft
                                 * - If it's published and there are unpublished changes then indicate that
                                 */

                                if ($page->is_published) {

                                    $_published_hash = ! empty($page->published->hash) ? $page->published->hash    : 'NOHASH' ;
                                    $_draft_hash     = ! empty($page->draft->hash)     ? $page->draft->hash        : 'NOHASH' ;

                                    if ($_published_hash !== $_draft_hash) {

                                        echo anchor('admin/cms/pages/edit/' . $page->id, ' <strong rel="tipsy" title="This page is visible on site but changes have been made which have not been published.">(Unpublished Changes)</strong>');
                                    }

                                } else {

                                    echo anchor('admin/cms/pages/edit/' . $page->id, ' <strong rel="tipsy" title="This page has not been published. It is not available to your site\'s visitors.">(Unpublished)</strong>');
                                }

                                $_breadcrumbs = $page->draft->breadcrumbs;
                                array_pop($_breadcrumbs);

                                echo '<small>';
                                if ($_breadcrumbs) {

                                    $_out = array();

                                    foreach ($_breadcrumbs as $crumb) {

                                        $_out[] = $crumb->title;
                                    }

                                    echo implode(' // ', $_out);

                                } else {

                                    echo 'Top Level Page';
                                }

                                echo '</small>';
                            echo '</div>';
                        echo '</td>';

                        echo \Nails\Admin\Helper::loadUserCell($page->modified_by);
                        echo \Nails\Admin\Helper::loadDatetimeCell($page->modified);

                        echo '<td class="actions">';

                            echo anchor($page->published->url, lang('action_view'), 'class="awesome small" target="cms-page-' . $page->id . '"');

                            if (userHasPermission('admin:cms:pages:edit')) {

                                echo anchor('admin/cms/pages/edit/' . $page->id, lang('action_edit'), 'class="awesome small"');

                                if (! $page->is_published || $_published_hash !== $_draft_hash) {

                                    echo anchor('admin/cms/pages/publish/' . $page->id, lang('action_publish'), 'data-title="Are you sure?" data-body="Publish this page immediately?" class="confirm awesome green small"');
                                }
                            }

                            //echo anchor($page->url . '?is_preview=1', lang('action_preview'), 'target="_blank" class="fancybox awesome small green" data-fancybox-type="iframe" data-width="100%" data-height="100%"');

                            if (userHasPermission('admin:cms:pages:delete')) {

                                echo anchor('admin/cms/pages/delete/' . $page->id, lang('action_delete'), 'data-title="Are you sure?" data-body="This will remove the page, and any of it\'s children, from the site." class="confirm awesome small red"');
                            }

                        echo '</td>';
                    echo '</tr>';
                }

            } else {

                echo '<tr>';
                    echo '<td colspan="4" class="no-data">';
                        echo 'No editable pages found';
                    echo '</td>';
                echo '</tr>';
            }

        ?>
        </tbody>
    </table>
</div>
