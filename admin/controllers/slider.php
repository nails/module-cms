<?php

/**
 * This class provides CMS Slider management functionality
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Cms;

class Slider extends \AdminController
{
    /**
     * Announces this controllers methods
     * @return stdClass
     */
    public static function announce()
    {
        $d = parent::announce();
        if (user_has_permission('admin.cms:0.can_manage_slider')) {

            $d[''] = array('Content Management', 'Manage Sliders');
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
        if (!user_has_permission('admin.accounts:0.can_manage_slider')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load common items
        $this->load->helper('cms');
        $this->load->model('cms/cms_slider_model');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse CMS Sliders
     * @return void
     */
    public function index()
    {
        $this->data['page']->title = 'Manage Sliders';

        // --------------------------------------------------------------------------

        //  Fetch all the menus in the DB
        $this->data['sliders'] = $this->cms_slider_model->get_all();

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->load('nails.admin.cms.sliders.min.js', true);

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/cms/sliders/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new CMS Slider
     * @return void
     */
    public function create()
    {
        if (!user_has_permission('admin.cms:0.can_create_slider')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Create Slider';

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->load('nails.admin.cms.sliders.create_edit.min.js', true);

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/cms/sliders/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a CMS Slider
     * @return void
     */
    public function edit()
    {
        if (!user_has_permission('admin.cms:0.can_edit_slider')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['slider'] = $this->cms_slider_model->get_by_id($this->uri->segment(5), true);

        if (!$this->data['slider']) {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> invalid slider ID.');
            redirect('admin/cms/menus');
        }

        $this->data['page']->title = 'Edit Slider "' . $this->data['slider']->label . '"';

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->load('nails.admin.cms.sliders.create_edit.min.js', true);

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/cms/sliders/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a CMS Slider
     * @return void
     */
    public function delete()
    {
        if (!user_has_permission('admin.cms:0.can_delete_slider')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->session->set_flashdata('error', '<strong>Sorry,</strong> slider deletion is a TODO just now.');
        redirect('admin/cms/sliders');
    }
}
