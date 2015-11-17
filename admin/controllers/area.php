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

use Nails\Factory;
use Nails\Admin\Helper;
use Nails\Cms\Controller\BaseAdmin;

class Area extends BaseAdmin
{
    protected $oAreaModel;

    // --------------------------------------------------------------------------

    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin:cms:area:manage')) {

            $navGroup = new \Nails\Admin\Nav('CMS', 'fa-file-text');
            $navGroup->addAction('Manage Areas');
            return $navGroup;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of permissions which can be configured for the user
     * @return array
     */
    public static function permissions()
    {
        $permissions = parent::permissions();

        $permissions['manage'] = 'Can manage areas';
        $permissions['create'] = 'Can create a new area';
        $permissions['edit']   = 'Can edit an existing area';
        $permissions['delete'] = 'Can delete an existing area';

        return $permissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $this->oAreaModel = Factory::model('Area', 'nailsapp/module-cms');
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

        $sTablePrefix = $this->oAreaModel->getTablePrefix();

        //  Get pagination and search/sort variables
        $page      = $this->input->get('page')      ? $this->input->get('page')      : 0;
        $perPage   = $this->input->get('perPage')   ? $this->input->get('perPage')   : 50;
        $sortOn    = $this->input->get('sortOn')    ? $this->input->get('sortOn')    : $sTablePrefix . '.label';
        $sortOrder = $this->input->get('sortOrder') ? $this->input->get('sortOrder') : 'asc';
        $keywords  = $this->input->get('keywords')  ? $this->input->get('keywords')  : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns
        $sortColumns = array(
            $sTablePrefix . '.label'    => 'Label',
            $sTablePrefix . '.modified' => 'Modified'
        );

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = array(
            'sort' => array(
                array($sortOn, $sortOrder)
            ),
            'keywords' => $keywords
        );

        //  Get the items for the page
        $totalRows           = $this->oAreaModel->count_all($data);
        $this->data['areas'] = $this->oAreaModel->get_all($page, $perPage, $data);

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

        if ($this->input->post()) {

            //  Validate form
            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('label', '', 'xss_clean|trim|required');
            $oFormValidation->set_rules('description', '', 'xss_clean|trim|max_length[255]');
            $oFormValidation->set_rules('widget-data', '', 'trim');

            if ($this->input->post('slug')) {

                $sTable = $this->oAreaModel->getTableName();
                $oFormValidation->set_rules(
                    'slug',
                    '',
                    'xss_clean|trim|alpha_dash|is_unique[' . $sTable . '.slug]'
                );
            }

            $oFormValidation->set_message('required', lang('fv_required'));
            $oFormValidation->set_message('alpha_dash', lang('fv_alpha_dash'));
            $oFormValidation->set_message('is_unique', lang('fv_is_unique'));

            if ($oFormValidation->run()) {

                $aItemData                = array();
                $aItemData['label']       = $this->input->post('label');
                $aItemData['slug']        = $this->input->post('slug');
                $aItemData['description'] = strip_tags($this->input->post('description'));
                $aItemData['widget_data'] = $this->input->post('widget_data');

                if ($this->oAreaModel->create($aItemData)) {

                    $sStatus  = 'success';
                    $sMessage = 'Area created successfully.';

                    $this->session->set_flashdata($sStatus, $sMessage);
                    redirect('admin/cms/area');

                } else {

                    $this->data['error']  = 'Failed to create area. ';
                    $this->data['error'] .= $this->oAreaModel->last_error();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Create Area';

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->library('CMSWIDGETEDITOR');
        $this->asset->load('nails.admin.cms.areas.createEdit.min.js', 'NAILS');
        $this->asset->inline('var widgetEditor = new NAILS_Admin_CMS_WidgetEditor();', 'JS');
        $this->asset->inline('var areaEdit = new NAILS_Admin_CMS_Areas_CreateEdit(widgetEditor);', 'JS');

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

        $area = $this->oAreaModel->get_by_id($this->uri->segment(5));
        $this->data['area'] = $area;

        if (!$area) {

            $this->session->set_flashdata('error', 'Invalid area ID.');
            redirect('admin/cms/area');
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            //  Validate form
            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('label', '', 'xss_clean|trim|required');
            $oFormValidation->set_rules('description', '', 'xss_clean|trim|max_length[255]');
            $oFormValidation->set_rules('widget-data', '', 'trim');

            if ($this->input->post('slug')) {

                $sTable = $this->oAreaModel->getTableName();
                $oFormValidation->set_rules(
                    'slug',
                    '',
                    'xss_clean|trim|alpha_dash|unique_if_diff[' . $sTable . '.slug.' . $this->data['area']->slug . ']'
                );
            }

            $oFormValidation->set_message('required', lang('fv_required'));
            $oFormValidation->set_message('alpha_dash', lang('fv_alpha_dash'));
            $oFormValidation->set_message('is_unique', lang('fv_is_unique'));

            if ($oFormValidation->run()) {

                $aItemData                = array();
                $aItemData['label']       = $this->input->post('label');
                $aItemData['slug']        = $this->input->post('slug');
                $aItemData['description'] = strip_tags($this->input->post('description'));
                $aItemData['widget_data'] = $this->input->post('widget_data');

                if ($this->oAreaModel->update($area->id, $aItemData)) {

                    $sStatus  = 'success';
                    $sMessage = 'Area updated successfully.';

                    $this->session->set_flashdata($sStatus, $sMessage);
                    redirect('admin/cms/area');

                } else {

                    $this->data['error']  = 'Failed to update area. ';
                    $this->data['error'] .= $this->oAreaModel->last_error();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Edit Area &rsaquo; ' . $area->label;

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->library('CMSWIDGETEDITOR');
        $this->asset->load('nails.admin.cms.areas.createEdit.min.js', 'NAILS');
        $this->asset->inline('var widgetEditor = new NAILS_Admin_CMS_WidgetEditor();', 'JS');
        $this->asset->inline('var areaEdit = new NAILS_Admin_CMS_Areas_CreateEdit(widgetEditor);', 'JS');

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

        $area = $this->oAreaModel->get_by_id($this->uri->segment(5));

        if (!$area) {

            $this->session->set_flashdata('error', 'Invalid area ID.');
            redirect('admin/cms/area');
        }

        // --------------------------------------------------------------------------

        if ($this->oAreaModel->delete($area->id)) {

            $sStatus = 'success';
            $msg    = 'Area was deleted successfully.';

        } else {

            $sStatus = 'error';
            $msg    = 'Failed to delete area. ';
            $msg   .= $this->oAreaModel->last_error();
        }

        $this->session->set_flashdata($sStatus, $msg);
        redirect('admin/cms/area');
    }
}
