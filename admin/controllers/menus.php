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
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin:cms:menus:manage')) {

            $navGroup = new \Nails\Admin\Nav('CMS', 'fa-file-text');
            $navGroup->addAction('Manage Menus');
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

        $permissions['manage']  = 'Can manage menus';
        $permissions['create']  = 'Can create a new menu';
        $permissions['edit']    = 'Can edit an existing menu';
        $permissions['delete']  = 'Can delete an existing menu';
        $permissions['restore'] = 'Can restore a deleted menu';

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
        if (!userHasPermission('admin:cms:menus:manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

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
            'sort' => array(
                array($sortOn, $sortOrder)
            ),
            'keywords' => $keywords
        );

        //  Get the items for the page
        $totalRows           = $this->cms_menu_model->count_all($data);
        $this->data['menus'] = $this->cms_menu_model->get_all($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = \Nails\Admin\Helper::searchObject(true, $sortColumns, $sortOn, $sortOrder, $perPage, $keywords);
        $this->data['pagination'] = \Nails\Admin\Helper::paginationObject($page, $perPage, $totalRows);

        //  Add a header button
        if (userHasPermission('admin:cms:menus:create')) {

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
    public function create()
    {
        if (!userHasPermission('admin:cms:menus:create')) {

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
                $itemData['items']       = array();

                //  Prepare the menu items
                $menuItems = $this->input->post('menuItem');
                $numItems  = isset($menuItems['id']) ? count($menuItems['id']) : 0;

                for ($i=0; $i < $numItems; $i++) {

                    $itemData['items'][$i]              = array();
                    $itemData['items'][$i]['id']        = isset($menuItems['id'][$i]) ? $menuItems['id'][$i] : null;
                    $itemData['items'][$i]['parent_id'] = isset($menuItems['parent_id'][$i]) ? $menuItems['parent_id'][$i] : null;
                    $itemData['items'][$i]['label']     = isset($menuItems['label'][$i]) ? $menuItems['label'][$i] : null;
                    $itemData['items'][$i]['url']       = isset($menuItems['url'][$i]) ? $menuItems['url'][$i] : null;
                    $itemData['items'][$i]['page_id']   = isset($menuItems['page_id'][$i]) ? $menuItems['page_id'][$i] : null;
                }

                if ($this->cms_menu_model->create($itemData)) {

                    $status  = 'success';
                    $message = 'Menu created successfully.';

                    $this->session->set_flashdata($status, $message);
                    redirect('admin/cms/menus');

                } else {

                    $this->data['error']  = 'Failed to create menu. ';
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
        $pages = $this->cms_page_model->getAllNestedFlat();
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
        if (!userHasPermission('admin:cms:menus:edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $menu = $this->cms_menu_model->get_by_id($this->uri->segment(5));
        $this->data['menu'] = $menu;

        if (!$menu) {

            $this->session->set_flashdata('error', 'Invalid menu ID.');
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
                $itemData['items']       = array();

                //  Prepare the menu items
                $menuItems = $this->input->post('menuItem');
                $numItems  = isset($menuItems['id']) ? count($menuItems['id']) : 0;

                for ($i=0; $i < $numItems; $i++) {

                    $itemData['items'][$i]              = array();
                    $itemData['items'][$i]['id']        = isset($menuItems['id'][$i]) ? $menuItems['id'][$i] : null;
                    $itemData['items'][$i]['parent_id'] = isset($menuItems['parent_id'][$i]) ? $menuItems['parent_id'][$i] : null;
                    $itemData['items'][$i]['label']     = isset($menuItems['label'][$i]) ? $menuItems['label'][$i] : null;
                    $itemData['items'][$i]['url']       = isset($menuItems['url'][$i]) ? $menuItems['url'][$i] : null;
                    $itemData['items'][$i]['page_id']   = isset($menuItems['page_id'][$i]) ? $menuItems['page_id'][$i] : null;
                }


                if ($this->cms_menu_model->update($menu->id, $itemData)) {

                    $status  = 'success';
                    $message = 'Menu updated successfully.';

                    $this->session->set_flashdata($status, $message);
                    redirect('admin/cms/menus');

                } else {

                    $this->data['error']  = 'Failed to update menu. ';
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
        $pages = $this->cms_page_model->getAllNestedFlat();
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
        if (!userHasPermission('admin:cms:menus:delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $menu = $this->cms_menu_model->get_by_id($this->uri->segment(5));

        if (!$menu) {

            $this->session->set_flashdata('error', 'Invalid menu ID.');
            redirect('admin/cms/blocks');
        }

        // --------------------------------------------------------------------------

        if ($this->cms_menu_model->delete($menu->id)) {

            $status = 'success';
            $msg    = 'Menu was deleted successfully.';

        } else {

            $status = 'error';
            $msg    = 'Failed to delete menu. ';
            $msg   .= $this->cms_menu_model->last_error();
        }

        $this->session->set_flashdata($status, $msg);
        redirect('admin/cms/menus');
    }
}
