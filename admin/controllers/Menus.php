<?php

/**
 * This class provides CMS Menu management functionality
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Cms;

use Nails\Admin\Helper;
use Nails\Auth;
use Nails\Cms\Controller\BaseAdmin;
use Nails\Factory;

class Menus extends BaseAdmin
{
    /**
     * Announces this controller's navGroups
     *
     * @return \Nails\Admin\Factory\Nav
     */
    public static function announce()
    {
        if (userHasPermission('admin:cms:menus:manage')) {
            $oNavGroup = Factory::factory('Nav', 'nails/module-admin');
            $oNavGroup->setLabel('CMS');
            $oNavGroup->setIcon('fa-file-alt');
            $oNavGroup->addAction('Manage Menus');
            return $oNavGroup;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of permissions which can be configured for the user
     *
     * @return array
     */
    public static function permissions(): array
    {
        $aPermissions = parent::permissions();

        $aPermissions['manage']  = 'Can manage menus';
        $aPermissions['create']  = 'Can create a new menu';
        $aPermissions['edit']    = 'Can edit an existing menu';
        $aPermissions['delete']  = 'Can delete an existing menu';
        $aPermissions['restore'] = 'Can restore a deleted menu';

        return $aPermissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Browse CMS Menus
     *
     * @return void
     */
    public function index()
    {
        if (!userHasPermission('admin:cms:menus:manage')) {
            unauthorised();
        }

        $oInput     = Factory::service('Input');
        $oMenuModel = Factory::model('Menu', 'nails/module-cms');

        $sTableAlias  = $oMenuModel->getTableAlias();
        $iPage        = (int) $oInput->get('page') ?: 0;
        $iPerPage     = (int) $oInput->get('perPage') ?: 50;
        $sSortOn      = $oInput->get('sortOn') ?: $sTableAlias . '.label';
        $sSortOrder   = $oInput->get('sortOrder') ?: 'asc';
        $sKeywords    = $oInput->get('keywords') ?: '';
        $aSortColumns = [
            $sTableAlias . '.label'    => 'Label',
            $sTableAlias . '.modified' => 'Modified',
        ];
        $aData        = [
            'sort'     => [
                [$sSortOn, $sSortOrder],
            ],
            'keywords' => $sKeywords,
        ];

        //  Get the items for the page
        $iTotalRows          = $oMenuModel->countAll($aData);
        $this->data['menus'] = $oMenuModel->getAll($iPage, $iPerPage, $aData);

        //  Set Search and Pagination objects for the view
        $this->data['page']->title = 'Manage Menus';
        $this->data['search']      = Helper::searchObject(true, $aSortColumns, $sSortOn, $sSortOrder, $iPerPage, $sKeywords);
        $this->data['pagination']  = Helper::paginationObject($iPage, $iPerPage, $iTotalRows);

        //  Add a header button
        if (userHasPermission('admin:cms:menus:create')) {
            Helper::addHeaderButton('admin/cms/menus/create', 'Create Menu');
        }

        Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new CMS Menu
     *
     * @return void
     */
    public function create()
    {
        if (!userHasPermission('admin:cms:menus:create')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        $oInput = Factory::service('Input');
        if ($oInput->post()) {

            //  Validate form
            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('label', '', 'trim|required');
            $oFormValidation->set_rules('description', '', 'trim');
            $oFormValidation->set_message('required', lang('fv_required'));

            if ($oFormValidation->run()) {

                //  Prepare the create data
                $aItemData                = [];
                $aItemData['label']       = $oInput->post('label');
                $aItemData['description'] = strip_tags($oInput->post('description'));
                $aItemData['items']       = [];

                //  Prepare the menu items
                $aMenuItems = $oInput->post('menuItem');
                $iNumItems  = isset($aMenuItems['id']) ? count($aMenuItems['id']) : 0;

                for ($i = 0; $i < $iNumItems; $i++) {
                    $aItemData['items'][] = [
                        'id'        => isset($aMenuItems['id'][$i]) ? $aMenuItems['id'][$i] : null,
                        'parent_id' => isset($aMenuItems['parent_id'][$i]) ? $aMenuItems['parent_id'][$i] : null,
                        'label'     => isset($aMenuItems['label'][$i]) ? $aMenuItems['label'][$i] : null,
                        'url'       => isset($aMenuItems['url'][$i]) ? $aMenuItems['url'][$i] : null,
                        'page_id'   => isset($aMenuItems['page_id'][$i]) ? $aMenuItems['page_id'][$i] : null,
                    ];
                }

                $oMenuModel = Factory::model('Menu', 'nails/module-cms');
                if ($oMenuModel->create($aItemData)) {

                    $oSession = Factory::service('Session', Auth\Constants::MODULE_SLUG);
                    $oSession->setFlashData('success', 'Menu created successfully.');
                    redirect('admin/cms/menus');

                } else {
                    $this->data['error'] = 'Failed to create menu. ';
                    $this->data['error'] .= $oMenuModel->lastError();
                }

            } else {
                $this->data['error'] = lang('fv_there_were_errors');
            }

        } else {
            $aItems = [];
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Create Menu';

        // --------------------------------------------------------------------------

        //  Prepare the menu items
        if ($oInput->post()) {

            $aMenuItems     = [];
            $aPostMenuItems = $oInput->post('menuItem');
            $iNumItems      = !empty($aPostMenuItems['id']) ? count($aPostMenuItems['id']) : 0;

            for ($i = 0; $i < $iNumItems; $i++) {
                $aMenuItems[] = [
                    'id'        => isset($aPostMenuItems['id'][$i]) ? $aPostMenuItems['id'][$i] : null,
                    'parent_id' => isset($aPostMenuItems['parent_id'][$i]) ? $aPostMenuItems['parent_id'][$i] : null,
                    'label'     => isset($aPostMenuItems['label'][$i]) ? $aPostMenuItems['label'][$i] : null,
                    'url'       => isset($aPostMenuItems['url'][$i]) ? $aPostMenuItems['url'][$i] : null,
                    'page_id'   => isset($aPostMenuItems['page_id'][$i]) ? $aPostMenuItems['page_id'][$i] : null,
                ];
            }

        } else {
            $aMenuItems = $aItems;
        }

        // --------------------------------------------------------------------------

        //  Get the CMS Pages
        $oPageModel          = Factory::model('Page', 'nails/module-cms');
        $aPages              = $oPageModel->getAllNestedFlat();
        $this->data['pages'] = ['' => 'Select a CMS Page'] + $aPages;

        // --------------------------------------------------------------------------

        //  Assets
        $oAsset = Factory::service('Asset');
        $oAsset->load('nestedSortable/jquery.ui.nestedSortable.js', 'NAILS-BOWER');
        $oAsset->library('MUSTACHE');
        //  @todo (Pablo - 2018-12-01) - Update/Remove/Use minified once JS is refactored to be a module
        $oAsset->load('admin.menus.edit.js', 'nails/module-cms');
        $oAsset->inline('var menuEdit = new NAILS_Admin_CMS_Menus_Create_Edit(' . json_encode($aMenuItems) . ');', 'JS');

        // --------------------------------------------------------------------------

        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a CMS Menu
     *
     * @return void
     */
    public function edit()
    {
        if (!userHasPermission('admin:cms:menus:edit')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        $oUri       = Factory::service('Uri');
        $oMenuModel = Factory::model('Menu', 'nails/module-cms');
        $oMenu      = $oMenuModel->getById($oUri->segment(5));

        if (!$oMenu) {
            $oSession = Factory::service('Session', Auth\Constants::MODULE_SLUG);
            $oSession->setFlashData('error', 'Invalid menu ID.');
            redirect('admin/cms/menus');
        }

        // --------------------------------------------------------------------------

        $oInput = Factory::service('Input');
        if ($oInput->post()) {

            //  Validate form
            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('label', '', 'trim|required');
            $oFormValidation->set_rules('description', '', 'trim');
            $oFormValidation->set_message('required', lang('fv_required'));

            if ($oFormValidation->run()) {

                //  Prepare the create data
                $aItemData                = [];
                $aItemData['label']       = $oInput->post('label');
                $aItemData['description'] = strip_tags($oInput->post('description'));
                $aItemData['items']       = [];

                //  Prepare the menu items
                $aMenuItems = $oInput->post('menuItem');
                $iNumItems  = isset($aMenuItems['id']) ? count($aMenuItems['id']) : 0;

                for ($i = 0; $i < $iNumItems; $i++) {

                    $aItemData['items'][$i]              = [];
                    $aItemData['items'][$i]['id']        = isset($aMenuItems['id'][$i]) ? $aMenuItems['id'][$i] : null;
                    $aItemData['items'][$i]['parent_id'] = isset($aMenuItems['parent_id'][$i]) ? $aMenuItems['parent_id'][$i] : null;
                    $aItemData['items'][$i]['label']     = isset($aMenuItems['label'][$i]) ? $aMenuItems['label'][$i] : null;
                    $aItemData['items'][$i]['url']       = isset($aMenuItems['url'][$i]) ? $aMenuItems['url'][$i] : null;
                    $aItemData['items'][$i]['page_id']   = isset($aMenuItems['page_id'][$i]) ? $aMenuItems['page_id'][$i] : null;
                }

                if ($oMenuModel->update($oMenu->id, $aItemData)) {

                    $oSession = Factory::service('Session', Auth\Constants::MODULE_SLUG);
                    $oSession->setFlashData('success', 'Menu updated successfully.');
                    redirect('admin/cms/menus');

                } else {
                    $this->data['error'] = 'Failed to update menu. ';
                    $this->data['error'] .= $oMenuModel->lastError();
                }

            } else {
                $this->data['error'] = lang('fv_there_were_errors');
            }

        } else {
            $aItems = $oMenu->items;
        }

        // --------------------------------------------------------------------------

        if ($oInput->post()) {

            $aMenuItems     = [];
            $aPostMenuItems = $oInput->post('menuItem');
            $iNumItems      = !empty($aPostMenuItems['id']) ? count($aPostMenuItems['id']) : 0;

            for ($i = 0; $i < $iNumItems; $i++) {
                $aMenuItems[] = [
                    'id'        => isset($aPostMenuItems['id'][$i]) ? $aPostMenuItems['id'][$i] : null,
                    'parent_id' => isset($aPostMenuItems['parent_id'][$i]) ? $aPostMenuItems['parent_id'][$i] : null,
                    'label'     => isset($aPostMenuItems['label'][$i]) ? $aPostMenuItems['label'][$i] : null,
                    'url'       => isset($aPostMenuItems['url'][$i]) ? $aPostMenuItems['url'][$i] : null,
                    'page_id'   => isset($aPostMenuItems['page_id'][$i]) ? $aPostMenuItems['page_id'][$i] : null,
                ];
            }

        } else {
            $aMenuItems = $aItems;
        }

        // --------------------------------------------------------------------------

        //  Get the CMS Pages
        $oPageModel = Factory::model('Page', 'nails/module-cms');
        $aPages     = $oPageModel->getAllNestedFlat(null, false);

        // --------------------------------------------------------------------------

        //  Assets
        $oAsset = Factory::service('Asset');
        $oAsset->load('nestedSortable/jquery.ui.nestedSortable.js', 'NAILS-BOWER');
        $oAsset->library('MUSTACHE');
        //  @todo (Pablo - 2018-12-01) - Update/Remove/Use minified once JS is refactored to be a module
        $oAsset->load('admin.menus.edit.js', 'nails/module-cms');
        $oAsset->inline('var menuEdit = new NAILS_Admin_CMS_Menus_Create_Edit(' . json_encode($aMenuItems) . ');', 'JS');

        // --------------------------------------------------------------------------

        $this->data['menu']        = $oMenu;
        $this->data['page']->title = 'Edit Menu &rsaquo; ' . $oMenu->label;
        $this->data['pages']       = ['' => 'Select a CMS Page'] + $aPages;

        // --------------------------------------------------------------------------

        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a CMS Menu
     *
     * @return void
     */
    public function delete()
    {
        if (!userHasPermission('admin:cms:menus:delete')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        $oUri       = Factory::service('Uri');
        $oSession   = Factory::service('Session', Auth\Constants::MODULE_SLUG);
        $oMenuModel = Factory::model('Menu', 'nails/module-cms');
        $oMenu      = $oMenuModel->getById($oUri->segment(5));

        if (!$oMenu) {
            $oSession->setFlashData('error', 'Invalid menu ID.');
            redirect('admin/cms/menus');
        }

        if ($oMenuModel->delete($oMenu->id)) {
            $sStatus  = 'success';
            $sMessage = 'Menu was deleted successfully.';
        } else {
            $sStatus  = 'error';
            $sMessage = 'Failed to delete menu. ';
            $sMessage .= $oMenuModel->lastError();
        }

        $oSession->setFlashData($sStatus, $sMessage);
        redirect('admin/cms/menus');
    }
}
