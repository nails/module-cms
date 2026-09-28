<?php

use Nails\Admin\Helper;

/**
 * @var \Nails\Cms\Resource\Menu|null $oItem
 * @var array<int|string, string>     $aPages
 * @var object[]                      $aMenuItems
 */

$oItem      = $oItem ?? null;
$aMenuItems = $aMenuItems ?? [];
$aPages     = $aPages ?? [];

$aIds = [];
foreach ($aMenuItems as $oMenuItem) {
    $aIds[(string) $oMenuItem->id] = true;
}

$aByParent = [];
foreach ($aMenuItems as $oMenuItem) {
    $sParent = (string) ($oMenuItem->parent_id ?? '');
    if ($sParent !== '' && empty($aIds[$sParent])) {
        $sParent = '';
    }
    $aByParent[$sParent][] = $oMenuItem;
}

/**
 * One row in the menu. The id token is replaced when a row is added in the browser.
 */
$fnRenderItem = function (object $oMenuItem) use ($aPages, &$fnRenderBranch): void {

    $sItemId   = (string) $oMenuItem->id;
    $sItemAttr = htmlspecialchars($sItemId, ENT_QUOTES, 'UTF-8');
    $sGroup    = htmlspecialchars('cms-menu-link-' . $sItemId, ENT_QUOTES, 'UTF-8');
    $iPageId   = (int) ($oMenuItem->page_id ?? 0);
    $sUrl      = (string) ($oMenuItem->url ?? '');
    $bPage     = $iPageId > 0 || $sUrl === '';

    ?>
    <li class="cms-menu-item" data-id="<?=$sItemAttr?>">
        <div class="cms-menu-item__row">
            <span class="cms-menu-item__handle" aria-hidden="true">
                <span class="fa fa-arrows"></span>
            </span>
            <button type="button" class="cms-menu-item__toggle js-cms-menu-toggle" hidden aria-expanded="true" aria-label="Collapse nested items">
                <span class="fa fa-chevron-down"></span>
            </button>
            <div class="cms-menu-item__fields">
                <?=form_hidden('items[id][]', $sItemId)?>
                <?=form_hidden('items[parent_id][]', (string) ($oMenuItem->parent_id ?? ''), 'class="js-cms-menu-parent"')?>
                <label class="cms-menu-item__field">
                    <span>Label</span>
                    <?=form_input(
                        'items[label][]',
                        (string) ($oMenuItem->label ?? ''),
                        'class="js-cms-menu-label" placeholder="Label shown in the menu"'
                    )?>
                </label>
                <label class="cms-menu-item__field">
                    <span>Links to</span>
                    <select class="js-cms-menu-link select2" data-revealer="<?=$sGroup?>">
                        <option value="page"<?=$bPage ? ' selected' : ''?>>CMS page</option>
                        <option value="url"<?=$bPage ? '' : ' selected'?>>Custom URL</option>
                    </select>
                </label>
                <div class="cms-menu-item__target">
                    <label class="cms-menu-item__field" data-revealer="<?=$sGroup?>" data-reveal-on="page"<?=$bPage ? '' : ' style="display:none"'?>>
                        <span>Page</span>
                        <?=form_dropdown(
                            'items[page_id][]',
                            $aPages,
                            $iPageId > 0 ? (string) $iPageId : '0',
                            'class="js-cms-menu-page select2"'
                        )?>
                    </label>
                    <label class="cms-menu-item__field" data-revealer="<?=$sGroup?>" data-reveal-on="url"<?=$bPage ? ' style="display:none"' : ''?>>
                        <span>URL</span>
                        <?=form_input(
                            'items[url][]',
                            $sUrl,
                            'class="js-cms-menu-url" placeholder="https://"'
                        )?>
                    </label>
                </div>
            </div>
            <button type="button" class="btn btn-danger btn-sm js-cms-menu-remove" aria-label="Remove">
                &times;
            </button>
        </div>
        <ol class="cms-menu__tree">
            <?php

            if ($sItemId !== '%%CMS_MENU_ITEM_ID%%') {
                $fnRenderBranch($sItemId);
            }

            ?>
        </ol>
    </li>
    <?php
};

$fnRenderBranch = function (string $sParentId) use ($aByParent, $fnRenderItem): void {
    foreach ($aByParent[$sParentId] ?? [] as $oMenuItem) {
        $fnRenderItem($oMenuItem);
    }
};

?>
<div class="group-cms menus edit">
    <?php

    if (!empty($oItem)) {
        ?>
        <div class="js-admin-session--also-here" data-prefix="Another user is currently editing this item:"></div>
        <?php
    }

    echo form_open(
        '',
        !empty($CONFIG['FLOATING_CONFIG']['unsaved_changes']) ? 'data-unsaved-changes' : ''
    );

    ob_start();

    ?>
        <fieldset>
            <?php

            if (!empty($oItem?->slug)) {
                echo form_field([
                    'key'      => 'slug',
                    'label'    => 'Slug',
                    'default'  => $oItem->slug,
                    'readonly' => true,
                    'tip'      => 'Used by the site to find this menu. It cannot be changed.',
                ]);
            }

            echo form_field([
                'key'         => 'label',
                'label'       => 'Label',
                'default'     => $oItem?->label ?? '',
                'required'    => true,
                'placeholder' => 'A name for this menu, so editors can find it',
                'max_length'  => 150,
            ]);

            echo form_field([
                'key'         => 'description',
                'label'       => 'Description',
                'default'     => $oItem?->description ?? '',
                'placeholder' => 'Where this menu is used',
                'tip'         => 'For editors. It is not shown on the website.',
                'max_length'  => 500,
            ]);

            ?>
        </fieldset>
    <?php

    $sDetailsTab = ob_get_clean();
    ob_start();

    ?>
        <div class="js-cms-menu" data-no-fieldset>
            <div class="cms-menu-items">
                <div class="cms-menu-toolbar">
                    <p class="cms-menu-help">Drag an item to reorder it, or drop it onto another item to nest it.</p>
                    <div class="cms-menu-toolbar__actions">
                        <button type="button" class="btn btn-default btn-sm js-cms-menu-expand-all">
                            Expand all
                        </button>
                        <button type="button" class="btn btn-default btn-sm js-cms-menu-collapse-all">
                            Collapse all
                        </button>
                    </div>
                </div>
                <p class="js-cms-menu-empty alert alert-info"<?=$aMenuItems ? ' hidden' : ''?>>
                    This menu has no items yet.
                </p>
                <ol class="cms-menu__tree js-cms-menu-tree">
                    <?php $fnRenderBranch(''); ?>
                </ol>
                <p class="cms-menu-add">
                    <button type="button" class="btn btn-primary btn-sm js-cms-menu-add">
                        &plus; Add Item
                    </button>
                </p>
            </div>
            <template id="cms-menu-item-template">
                <?php

                $fnRenderItem((object) [
                    'id'        => '%%CMS_MENU_ITEM_ID%%',
                    'parent_id' => '',
                    'label'     => '',
                    'url'       => '',
                    'page_id'   => '',
                ]);

                ?>
            </template>
        </div>
    <?php

    $sItemsTab = ob_get_clean();

    echo Helper::tabs([
        ['label' => 'Details', 'content' => $sDetailsTab],
        ['label' => 'Items', 'content' => $sItemsTab],
    ]);

    echo Helper::floatingControls($CONFIG['FLOATING_CONFIG']);
    echo form_close();

    ?>
</div>
