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
        $d = parent::announce();
        if (user_has_permission('admin.cms:0.can_manage_menu')) {

            $d[''] = array('Content Management', 'Manage Menus');
            return $d;
        }
        return $d;
    }

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct()
    {
        parent::__construct();
        if (!user_has_permission('admin.accounts:0.can_manage_menu')) {

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
        $this->data['page']->title = 'Manage Menus';

        // --------------------------------------------------------------------------

        //  Fetch all the menus in the DB
        $this->data['menus'] = $this->cms_menu_model->get_all();

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->load('nails.admin.cms.menus.min.js', true);

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/cms/menus/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new CMS Menu
     * @return void
     */
    public function create()
    {
        if (!user_has_permission('admin.cms:0.can_create_menu')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Create Menu';

        // --------------------------------------------------------------------------

        $post = $this->input->post();

        if (isset($post['menu_item'])) {

            //  Validate
            $errors = false;
            $this->load->library('form_validation');
            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('description', '', 'xss_clean|required');

            $this->form_validation->set_message('required', lang('fv_required'));

            foreach ($post['menu_item'] as $item) {

                if (empty($item['label']) || empty($item['url'])) {

                    $errors = 'All menu items are required to have a label and a URL.';
                    break;
                }
            }

            //  Execute
            if ($this->form_validation->run() && !$errors) {

                if ($this->cms_menu_model->create($post)) {

                    $status = 'success';
                    $msg    = '<strong>Success!</strong> Menu was created successfully.';
                    $this->session->set_flashdata($status, $msg);

                    redirect('admin/cms/menus');

                } else {

                    $this->data['error']  = '<strong>Sorry,</strong> there were errors. ';
                    $this->data['error'] .= $this->cms_menu_model->last_error();
                }

            } else {

                $this->data['error'] = '<strong>Sorry,</strong> there were errors. ' . $errors;
            }
        }

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->library('jqueryui');
        $this->asset->load('nails.admin.cms.menus.create_edit.min.js', true);
        $this->asset->load('nestedSortable/jquery.ui.nestedSortable.js', 'NAILS-BOWER');
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/cms/menus/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a CMS Menu
     * @return void
     */
    public function edit()
    {
        if (!user_has_permission('admin.cms:0.can_edit_menu')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['menu'] = $this->cms_menu_model->get_by_id($this->uri->segment(5), true, false);

        if (!$this->data['menu']) {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> invalid menu ID.');
            redirect('admin/cms/menus');
        }

        $this->data['page']->title = 'Edit Menu "' . $this->data['menu']->label . '"';

        $post = $this->input->post();

        if (isset($post['menu_item'])) {

            //  Validate
            $errors = false;
            $this->load->library('form_validation');
            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('description', '', 'xss_clean|required');

            $this->form_validation->set_message('required', lang('fv_required'));

            foreach ($post['menu_item'] as $item) {

                if (empty($item['label']) || empty($item['url'])) {

                    $errors = 'All menu items are required to have a label and a URL.';
                    break;
                }
            }

            //  Execute
            if ($this->form_validation->run() && !$errors) {

                if ($this->cms_menu_model->update($this->data['menu']->id, $post)) {

                    $status = 'success';
                    $msg    = '<strong>Success!</strong> Menu was updated successfully.';
                    $this->session->set_flashdata($status, $msg);

                    redirect('admin/cms/menus');

                } else {

                    $this->data['error']  = '<strong>Sorry,</strong> there were errors. ';
                    $this->data['error'] .= $this->cms_menu_model->last_error();
                }

            } else {

                $this->data['error'] = '<strong>Sorry,</strong> there were errors. ' . $errors;
            }
        }

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->library('jqueryui');
        $this->asset->load('nails.admin.cms.menus.create_edit.min.js', true);
        $this->asset->load('nestedSortable/jquery.ui.nestedSortable.js', 'NAILS-BOWER');
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/cms/menus/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a CMS Menu
     * @return void
     */
    public function delete()
    {
        if (!user_has_permission('admin.cms:0.can_delete_menu')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $menu = $this->cms_menu_model->get_by_id($this->uri->segment(5));

        if (!$menu) {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> invalid menu ID.');
            redirect('admin/cms/menus');
        }

        // --------------------------------------------------------------------------

        if ($this->cms_menu_model->delete($menu->id)) {

            $status = 'success';
            $msg    = '<strong>Sorry,</strong> failed to delete menu. ';
            $msg   .= $this->cms_menu_model->last_error();
            $this->session->set_flashdata($status, $msg);

        } else {

            $status = 'error';
            $msg    = '<strong>Sorry,</strong> failed to delete menu. ';
            $msg   .= $this->cms_menu_model->last_error();
            $this->session->set_flashdata($status, $msg);
        }

        redirect('admin/cms/menus');
    }
}
