<?php

/**
 * This class provides CMS Area management functionality
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Cms;

use Nails\Cms\Constants;
use Nails\Common\Service\Session;
use Nails\Factory;
use Nails\Admin\Helper;
use Nails\Cms\Controller\BaseAdmin;

/**
 * Class Area
 *
 * @package Nails\Admin\Cms
 */
class Area extends BaseAdmin
{
    protected $oAreaModel;

    // --------------------------------------------------------------------------

    /**
     * Announces this controller's navGroups
     * @return \Nails\Admin\Factory\Nav
     */
    public static function announce()
    {
        if (userHasPermission('admin:cms:area:manage')) {
            $oNavGroup = Factory::factory('Nav', 'nails/module-admin');
            $oNavGroup->setLabel('CMS');
            $oNavGroup->setIcon('fa-file-alt');
            $oNavGroup->addAction('Manage Areas');
            return $oNavGroup;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of permissions which can be configured for the user
     * @return array
     */
    public static function permissions(): array
    {
        $aPermissions = parent::permissions();

        $aPermissions['manage'] = 'Can manage areas';
        $aPermissions['create'] = 'Can create a new area';
        $aPermissions['edit']   = 'Can edit an existing area';
        $aPermissions['delete'] = 'Can delete an existing area';

        return $aPermissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $this->oAreaModel = Factory::model('Area', Constants::MODULE_SLUG);
    }

    // --------------------------------------------------------------------------

    /**
     * Browse CMS Areas
     * @return void
     */
    public function index()
    {
        if (!userHasPermission('admin:cms:area:manage')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Manage Areas';

        // --------------------------------------------------------------------------

        $sTableAlias = $this->oAreaModel->getTableAlias();

        //  Get pagination and search/sort variables
        $oInput    = Factory::service('Input');
        $page      = $oInput->get('page') ? $oInput->get('page') : 0;
        $perPage   = $oInput->get('perPage') ? $oInput->get('perPage') : 50;
        $sortOn    = $oInput->get('sortOn') ? $oInput->get('sortOn') : $sTableAlias . '.label';
        $sortOrder = $oInput->get('sortOrder') ? $oInput->get('sortOrder') : 'asc';
        $keywords  = $oInput->get('keywords') ? $oInput->get('keywords') : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns
        $sortColumns = [
            $sTableAlias . '.label'    => 'Label',
            $sTableAlias . '.modified' => 'Modified',
        ];

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = [
            'sort'     => [
                [$sortOn, $sortOrder],
            ],
            'keywords' => $keywords,
        ];

        //  Get the items for the page
        $totalRows           = $this->oAreaModel->countAll($data);
        $this->data['areas'] = $this->oAreaModel->getAll($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = Helper::searchObject(true, $sortColumns, $sortOn, $sortOrder, $perPage, $keywords);
        $this->data['pagination'] = Helper::paginationObject($page, $perPage, $totalRows);

        //  Add a header button
        if (userHasPermission('admin:cms:area:create')) {

            Helper::addHeaderButton('admin/cms/area/create', 'Create Area');
        }

        // --------------------------------------------------------------------------

        Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new CMS Area
     * @return void
     */
    public function create()
    {
        if (!userHasPermission('admin:cms:area:create')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        $oInput = Factory::service('Input');
        if ($oInput->post()) {

            //  Validate form
            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('label', '', 'trim|required');
            $oFormValidation->set_rules('description', '', 'trim|max_length[255]');
            $oFormValidation->set_rules('widget-data', '', 'trim');

            if ($oInput->post('slug')) {

                $sTable = $this->oAreaModel->getTableName();
                $oFormValidation->set_rules(
                    'slug',
                    '',
                    'trim|alpha_dash|is_unique[' . $sTable . '.slug]'
                );
            }

            $oFormValidation->set_message('required', lang('fv_required'));
            $oFormValidation->set_message('alpha_dash', lang('fv_alpha_dash'));
            $oFormValidation->set_message('is_unique', lang('fv_is_unique'));

            if ($oFormValidation->run()) {

                $aItemData                = [];
                $aItemData['label']       = $oInput->post('label');
                $aItemData['slug']        = $oInput->post('slug');
                $aItemData['description'] = strip_tags($oInput->post('description'));
                $aItemData['widget_data'] = $oInput->post('widget_data');

                if ($this->oAreaModel->create($aItemData)) {

                    $sStatus  = 'success';
                    $sMessage = 'Area created successfully.';

                    /** @var Session $oSession */
                    $oSession = Factory::service('Session');
                    $oSession->setFlashData($sStatus, $sMessage);
                    redirect('admin/cms/area');

                } else {
                    $this->data['error'] = 'Failed to create area. ';
                    $this->data['error'] .= $this->oAreaModel->lastError();
                }

            } else {
                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Create Area';

        // --------------------------------------------------------------------------

        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a CMS Area
     * @return void
     */
    public function edit()
    {
        if (!userHasPermission('admin:cms:area:edit')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        $oUri               = Factory::service('Uri');
        $area               = $this->oAreaModel->getById($oUri->segment(5));
        $this->data['area'] = $area;

        if (!$area) {
            /** @var Session $oSession */
            $oSession = Factory::service('Session');
            $oSession->setFlashData('error', 'Invalid area ID.');
            redirect('admin/cms/area');
        }

        // --------------------------------------------------------------------------

        $oInput = Factory::service('Input');
        if ($oInput->post()) {

            //  Validate form
            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('label', '', 'trim|required');
            $oFormValidation->set_rules('description', '', 'trim|max_length[255]');
            $oFormValidation->set_rules('widget-data', '', 'trim');

            if ($oInput->post('slug')) {

                $sTable = $this->oAreaModel->getTableName();
                $oFormValidation->set_rules(
                    'slug',
                    '',
                    'trim|alpha_dash|unique_if_diff[' . $sTable . '.slug.' . $this->data['area']->slug . ']'
                );
            }

            $oFormValidation->set_message('required', lang('fv_required'));
            $oFormValidation->set_message('alpha_dash', lang('fv_alpha_dash'));
            $oFormValidation->set_message('is_unique', lang('fv_is_unique'));

            if ($oFormValidation->run()) {

                $aItemData                = [];
                $aItemData['label']       = $oInput->post('label');
                $aItemData['slug']        = $oInput->post('slug');
                $aItemData['description'] = strip_tags($oInput->post('description'));
                $aItemData['widget_data'] = $oInput->post('widget_data');

                if ($this->oAreaModel->update($area->id, $aItemData)) {

                    $sStatus  = 'success';
                    $sMessage = 'Area updated successfully.';

                    /** @var Session $oSession */
                    $oSession = Factory::service('Session');
                    $oSession->setFlashData($sStatus, $sMessage);
                    redirect('admin/cms/area');

                } else {

                    $this->data['error'] = 'Failed to update area. ';
                    $this->data['error'] .= $this->oAreaModel->lastError();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Edit Area &rsaquo; ' . $area->label;

        // --------------------------------------------------------------------------

        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a CMS Area
     * @return void
     */
    public function delete()
    {
        if (!userHasPermission('admin:cms:area:delete')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        $oUri = Factory::service('Uri');
        $area = $this->oAreaModel->getById($oUri->segment(5));

        if (!$area) {
            /** @var Session $oSession */
            $oSession = Factory::service('Session');
            $oSession->setFlashData('error', 'Invalid area ID.');
            redirect('admin/cms/area');
        }

        // --------------------------------------------------------------------------

        if ($this->oAreaModel->delete($area->id)) {

            $sStatus  = 'success';
            $sMessage = 'Area was deleted successfully.';

        } else {

            $sStatus  = 'error';
            $sMessage = 'Failed to delete area. ';
            $sMessage .= $this->oAreaModel->lastError();
        }

        /** @var Session $oSession */
        $oSession = Factory::service('Session');
        $oSession->setFlashData($sStatus, $sMessage);
        redirect('admin/cms/area');
    }
}
