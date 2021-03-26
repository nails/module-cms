<?php

/**
 * The Menu Admin controller
 *
 * @package  nails/module-cms
 * @category controller
 */

namespace Nails\Admin\Cms;

use Nails\Admin\Controller\DefaultController;
use Nails\Cms\Constants;
use Nails\Cms\Model\Page;
use Nails\Cms\Resource\Menu\Item;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Exception\NailsException;
use Nails\Common\Exception\ValidationException;
use Nails\Common\Resource;
use Nails\Common\Service\Asset;
use Nails\Common\Service\FormValidation;
use Nails\Common\Service\Input;
use Nails\Factory;

/**
 * Class Menu
 *
 * @package Nails\Admin\Cms
 */
class Menu extends DefaultController
{
    const CONFIG_MODEL_NAME     = 'Menu';
    const CONFIG_MODEL_PROVIDER = Constants::MODULE_SLUG;
    const CONFIG_PERMISSION     = 'cms:menu';
    const CONFIG_SIDEBAR_GROUP  = 'CMS';
    const CONFIG_SIDEBAR_ICON   = 'fa-file-alt';

    // --------------------------------------------------------------------------

    /**
     * Menu constructor.
     *
     * @throws NailsException
     */
    public function __construct()
    {
        parent::__construct();
        $this->aConfig['INDEX_FIELDS']['Label'] = function (\Nails\Cms\Resource\Menu $oMenu) {
            return sprintf(
                '%s<small>%s</small>',
                $oMenu->label,
                $oMenu->description
            );
        };
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    protected function loadEditViewData(Resource $oMenu = null): void
    {
        parent::loadEditViewData($oMenu);

        /** @var Input $oInput */
        $oInput = Factory::service('Input');
        /** @var Asset $oAsset */
        $oAsset = Factory::service('Asset');
        /** @var Page $oPageModel */
        $oPageModel = Factory::model('Page', Constants::MODULE_SLUG);

        $this->data['aPages'] = ['Select a CMS Page'] + $oPageModel->getAllNestedFlat(null, false);

        $aMenuItems = $oInput->post()
            ? $this->compileItemsFromPost()
            : $this->compileItemsFromMenu($oMenu);

        $oAsset
            ->load('admin.min.css', Constants::MODULE_SLUG)
            ->load('https://cdnjs.cloudflare.com/ajax/libs/nestedSortable/1.3.4/jquery.ui.nestedSortable.min.js')
            //  @todo (Pablo - 2018-12-01) - Update/Remove/Use minified once JS is refactored to be a module
            ->load('admin.menus.edit.js', Constants::MODULE_SLUG)
            ->library('MUSTACHE')
            ->inline('var menuEdit = new NAILS_Admin_CMS_Menus_Create_Edit(' . json_encode($aMenuItems) . ');', 'JS');
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    protected function runFormValidation(string $sMode, array $aOverrides = []): void
    {
        parent::runFormValidation($sMode, $aOverrides);

        $aErrors = [];
        foreach ($this->compileItemsFromPost() as $iIndex => $oItem) {
            if (empty($oItem->label)) {
                $aErrors[] = sprintf(
                    'Menu item at position %s requires a label',
                    $iIndex + 1
                );

            }

            if ((empty($oItem->page_id) && empty($oItem->url)) || (!empty($oItem->page_id) && !empty($oItem->url))) {
                $aErrors[] = sprintf(
                    'Menu item %s must define one of: URL, CMS page',
                    empty($oItem->label)
                        ? 'at position ' . ($iIndex + 1)
                        : '"' . $oItem->label . '"'
                );
            }
        }

        if (!empty($aErrors)) {
            throw new ValidationException(
                implode('<br>', $aErrors)
            );
        }
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    protected function afterCreateAndEdit($sMode, Resource $oNewItem, Resource $oOldItem = null): void
    {
        parent::afterCreateAndEdit($sMode, $oNewItem, $oOldItem);

        /** @var \Nails\Cms\Model\Menu\Item $oItemModel */
        $oItemModel = Factory::model('MenuItem', Constants::MODULE_SLUG);

        $aItems   = $this->compileItemsFromPost();
        $aIdMap   = [];
        $iCounter = 0;

        foreach ($aItems as $oItem) {

            $oItem->menu_id = $oNewItem->id;
            $oItem->order   = $iCounter++;

            if (is_numeric($oItem->id)) {

                $aIdMap[$oItem->id] = $oItem->id;

                $oItem->parent_id = $aIdMap[$oItem->parent_id] ?? null;
                $oItem->page_id   = (int) $oItem->page_id ?: null;

                $oItemModel->update($oItem->id, (array) $oItem);

            } else {

                $oItem->parent_id = $aIdMap[$oItem->parent_id] ?? null;
                $oItem->page_id   = (int) $oItem->page_id ?: null;

                $aIdMap[$oItem->id] = $oItemModel->create((array) $oItem);
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Compile the menu items array from POST data
     *
     * @return \stdClass[]
     * @throws FactoryException
     */
    protected function compileItemsFromPost(): array
    {
        /** @var Input $oInput */
        $oInput = Factory::service('Input');
        $aItems = (array) $oInput->post('items');

        $aOut = [];
        foreach ($aItems as $sProperty => $aItem) {
            foreach ($aItem as $iIndex => $sValue) {

                if (!array_key_exists($iIndex, $aOut)) {
                    $aOut[$iIndex] = (object) [];
                }

                $aOut[$iIndex]->{$sProperty} = $sValue;
            }
        }

        return $aOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Compile the menu items array from Menu data
     *
     * @param \Nails\Cms\Resource\Menu|null $oMenu The Menu being edited
     *
     * @return Item[]
     * @throws FactoryException
     * @throws ModelException
     */
    protected function compileItemsFromMenu(?\Nails\Cms\Resource\Menu $oMenu): array
    {
        if (empty($oMenu)) {
            return [];
        }

        /** @var \Nails\Cms\Model\Menu\Item $oItemModel */
        $oItemModel = Factory::model('MenuItem', Constants::MODULE_SLUG);

        $aItems = $oItemModel->getAll([
            'where' => [
                ['parent_id', null],
                ['menu_id', $oMenu->id],
            ],
        ]);

        $aMenuItems = [];
        foreach ($aItems as $oItem) {
            $aMenuItems = array_merge(
                $aMenuItems,
                [$oItem],
                $oItemModel->flattenTree($oItemModel->getChildren($oItem->id, true))
            );
        }

        return $aMenuItems;
    }
}
