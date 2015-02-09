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

class Menus extends \AdminController
{
    /**
     * Announces this controllers methods
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin.cms:0.can_manage_menu')) {

            $navGroup = new \Nails\Admin\Nav('CMS');
            $navGroup->addMethod('Manage Menus');
            return $navGroup;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct()
    {
        parent::__construct();
        if (!userHasPermission('admin.accounts:0.can_manage_menu')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load common items
        $this->load->helper('cms');
        $this->load->model('cms/cms_menu_model');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse CMS Menus
     * @return void
     */
    public function index()
    {
        //  Set method info
        $this->data['page']->title = 'Manage Menus';

        // --------------------------------------------------------------------------

        //  Get pagination and search/sort variables
        $page      = $this->input->get('page')      ? $this->input->get('page')      : 0;
        $perPage   = $this->input->get('perPage')   ? $this->input->get('perPage')   : 50;
        $sortOn    = $this->input->get('sortOn')    ? $this->input->get('sortOn')    : 'm.label';
        $sortOrder = $this->input->get('sortOrder') ? $this->input->get('sortOrder') : 'asc';
        $keywords  = $this->input->get('keywords')  ? $this->input->get('keywords')  : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns
        $sortColumns = array(
            'm.label'    => 'Label',
            'm.modified' => 'Modified'
        );

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = array(
            'sort'  => array(
                'column' => $sortOn,
                'order'  => $sortOrder
            ),
            'keywords' => $keywords
        );

        //  Get the items for the page
        $totalRows           = $this->cms_menu_model->count_all($data);
        $this->data['menus'] = $this->cms_menu_model->get_all($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = \Nails\Admin\Helper::searchObject($sortColumns, $sortOn, $sortOrder, $perPage, $keywords);
        $this->data['pagination'] = \Nails\Admin\Helper::paginationObject($page, $perPage, $totalRows);

        //  Add a header button
        if (userHasPermission('admin.cms:0.can_create_menu')) {

            \Nails\Admin\Helper::addHeaderButton('admin/cms/menus/create', 'Create Menu');
        }

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new CMS Menu
     * @return void
     */
        /**
     * Edit a CMS Menu
     * @return void
     */
    public function create()
    {
        if (!userHasPermission('admin.cms:0.can_create_menu')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            //  Validate form
            $this->load->library('form_validation');
            $this->form_validation->set_rules('label', '', 'xss_clean|trim|required');
            $this->form_validation->set_rules('description', '', 'trim');
            $this->form_validation->set_message('required', lang('fv_required'));

            if ($this->form_validation->run()) {

                //  Prepare the create data
                $itemData                = array();
                $itemData['label']       = $this->input->post('label');
                $itemData['description'] = strip_tags($this->input->post('description'));
                $itemData['items']       = $this->input->post('menuItem');

                if ($this->cms_menu_model->create($itemData)) {

                    $status  = 'success';
                    $message = '<strong>Success!</strong> Menu created successfully.';

                    $this->session->set_flashdata($status, $message);
                    redirect('admin/cms/menus');

                } else {

                    $this->data['error']  = '<strong>Sorry,</strong> failed to create menu. ';
                    $this->data['error'] .= $this->cms_menu_model->last_error();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }

        } else {

            $items = array();
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Create Menu';

        // --------------------------------------------------------------------------

        //  Prepare the menu items
        if ($this->input->post()) {

            $menuItems = (array) json_decode(json_encode($this->input->post('menuItem')));
            $menuItems = array_values($menuItems);

        } else {

            $menuItems = $items;
        }

        // --------------------------------------------------------------------------

        //  Get the CMS Pages
        $this->load->model('cms/cms_page_model');
        $pages = $this->cms_page_model->get_all_nested_flat();
        $this->data['pages'] = array('' => 'Select a CMS Page') + $pages;

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->load('nestedSortable/jquery.ui.nestedSortable.js', 'NAILS-BOWER');
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.cms.menus.createEdit.min.js', 'NAILS');
        $this->asset->inline('var menuEdit = new NAILS_Admin_CMS_Menus_Create_Edit(' . json_encode($menuItems) . ');', 'JS');

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a CMS Menu
     * @return void
     */
    public function edit()
    {
        if (!userHasPermission('admin.cms:0.can_edit_menu')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $menu = $this->cms_menu_model->get_by_id($this->uri->segment(5));
        $this->data['menu'] = $menu;

        if (!$menu) {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> invalid menu ID.');
            redirect('admin/cms/menus');
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            //  Validate form
            $this->load->library('form_validation');
            $this->form_validation->set_rules('label', '', 'xss_clean|trim|required');
            $this->form_validation->set_rules('description', '', 'trim');
            $this->form_validation->set_message('required', lang('fv_required'));

            if ($this->form_validation->run()) {

                //  Prepare the create data
                $itemData                = array();
                $itemData['label']       = $this->input->post('label');
                $itemData['description'] = strip_tags($this->input->post('description'));
                $itemData['items']       = $this->input->post('menuItem');

                if ($this->cms_menu_model->update($menu->id, $itemData)) {

                    $status  = 'success';
                    $message = '<strong>Success!</strong> Menu updated successfully.';

                    $this->session->set_flashdata($status, $message);
                    redirect('admin/cms/menus');

                } else {

                    $this->data['error']  = '<strong>Sorry,</strong> failed to update menu. ';
                    $this->data['error'] .= $this->cms_menu_model->last_error();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }

        } else {

            $items = $menu->items;
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Edit Menu &rsaquo; ' . $menu->label;

        // --------------------------------------------------------------------------

        //  Prepare the menu items
        if ($this->input->post()) {

            $menuItems = (array) json_decode(json_encode($this->input->post('menuItem')));
            $menuItems = array_values($menuItems);

        } else {

            $menuItems = $items;
        }

        // --------------------------------------------------------------------------

        //  Get the CMS Pages
        $this->load->model('cms/cms_page_model');
        $pages = $this->cms_page_model->get_all_nested_flat();
        $this->data['pages'] = array('' => 'Select a CMS Page') + $pages;

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->load('nestedSortable/jquery.ui.nestedSortable.js', 'NAILS-BOWER');
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.cms.menus.createEdit.min.js', 'NAILS');
        $this->asset->inline('var menuEdit = new NAILS_Admin_CMS_Menus_Create_Edit(' . json_encode($menuItems) . ');', 'JS');

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a CMS Menu
     * @return void
     */
    public function delete()
    {
        if (!userHasPermission('admin.cms:0.can_delete_menu')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $menu = $this->cms_menu_model->get_by_id($this->uri->segment(5));

        if (!$menu) {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> invalid menu ID.');
            redirect('admin/cms/blocks');
        }

        // --------------------------------------------------------------------------

        if ($this->cms_menu_model->delete($menu->id)) {

            $status = 'success';
            $msg    = '<strong>Success!</strong> Menu was deleted successfully.';

        } else {

            $status = 'error';
            $msg    = '<strong>Sorry,</strong> failed to delete menu. ';
            $msg   .= $this->cms_menu_model->last_error();
        }

        $this->session->set_flashdata($status, $msg);
        redirect('admin/cms/menus');
    }
}
