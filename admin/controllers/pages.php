<?php

/**
 * This class provides CMS Pages management functionality
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Cms;

class Pages extends \AdminController
{
    /**
     * Announces this controllers methods
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin.cms:0.can_manage_page')) {

            $navGroup = new \Nails\Admin\Nav('CMS');
            $navGroup->addMethod('Manage Pages');
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
        if (!userHasPermission('admin.accounts:0.can_manage_page')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load common items
        $this->load->helper('cms');
        $this->load->model('cms/cms_page_model');
        $this->load->model('routes_model');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse CMS Pages
     * @return void
     */
    public function index()
    {
        //  Page Title
        $this->data['page']->title = 'Manage Pages';

        // --------------------------------------------------------------------------

        //  Fetch all the pages in the DB
        $this->data['pages'] = $this->cms_page_model->get_all();

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.cms.pages.min.js', true);

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new CMS Page
     * @return void
     */
    public function create()
    {
        if (!userHasPermission('admin.cms:0.can_create_page')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Get data
        $this->data['pages_nested_flat'] = $this->cms_page_model->get_all_nested_flat(' &rsaquo; ', false);

        //  Set method info
        $this->data['page']->title  = 'Create New Page';

        //  Get available templates & widgets
        $this->data['templates'] = $this->cms_page_model->get_available_templates('EDITOR');
        $this->data['widgets']  = $this->cms_page_model->get_available_widgets('EDITOR');

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->library('jqueryui');
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.cms.pages.create_edit.min.js', true);

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a CMS Page
     * @return void
     */
    public function edit()
    {
        if (!userHasPermission('admin.cms:0.can_edit_page')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['cmspage'] = $this->cms_page_model->get_by_id($this->uri->segment(5), true);

        if (!$this->data['cmspage']) {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> no page found by that ID');
            redirect('admin/cms/pages');
        }

        // --------------------------------------------------------------------------

        //  Get data
        $this->data['pages_nested_flat'] = $this->cms_page_model->get_all_nested_flat(' &rsaquo; ', false);

        //  Set method info
        $this->data['page']->title = 'Edit Page "' . $this->data['cmspage']->draft->title . '"';

        //  Get available templates & widgets
        $this->data['templates'] = $this->cms_page_model->get_available_templates('EDITOR');
        $this->data['widgets']   = $this->cms_page_model->get_available_widgets('EDITOR');

        //  Get children of this page
        $this->data['page_children'] = $this->cms_page_model->get_ids_of_children($this->data['cmspage']->id);

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->library('jqueryui');
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.cms.pages.create_edit.js', true);

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Publish a CMS Page
     * @return void
     */
    public function publish()
    {
        if (!userHasPermission('admin.cms:0.can_edit_page')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id   = $this->uri->segment(5);
        $page = $this->cms_page_model->get_by_id($id);

        if ($page && !$page->is_deleted) {

            if ($this->cms_page_model->publish($id)) {

                $this->session->set_flashdata('success', '<strong>Success!</strong> Page was published successfully.');

            } else {

                $this->session->set_flashdata('error', '<strong>Sorry,</strong> Could not publish page. ' . $this->cms_page_model->last_error());
            }

        } else {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> invalid page ID.');
        }

        redirect('admin/cms/pages');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a CMS Page
     * @return void
     */
    public function delete()
    {
        if (!userHasPermission('admin.cms:0.can_delete_page')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id   = $this->uri->segment(5);
        $page = $this->cms_page_model->get_by_id($id);

        if ($page && !$page->is_deleted) {

            if ($this->cms_page_model->delete($id)) {

                $this->session->set_flashdata('success', '<strong>Success!</strong> Page was deleted successfully.');

            } else {

                $this->session->set_flashdata('error', '<strong>Sorry,</strong> Could not delete page. ' . $this->cms_page_model->last_error());
            }

        } else {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> invalid page ID.');
        }

        redirect('admin/cms/pages');
    }

    // --------------------------------------------------------------------------

    /**
     * Restore a CMS Page
     * @return void
     */
    public function restore()
    {
        if (!userHasPermission('admin.cms:0.can_restore_page')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id   = $this->uri->segment(5);
        $page = $this->cms_page_model->get_by_id($id);

        if ($page && $page->is_deleted) {

            if ($this->cms_page_model->restore($id)) {

                $this->session->set_flashdata('success', '<strong>Success!</strong> Page was restored successfully. ');

            } else {

                $this->session->set_flashdata('error', '<strong>Sorry,</strong> Could not restore page. ' . $this->cms_page_model->last_error());
            }

        } else {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> invalid page ID.');
        }

        redirect('admin/cms/pages');
    }

    // --------------------------------------------------------------------------

    /**
     * Destroy a CMS Page
     * @return void
     */
    public function destroy()
    {
        if (!userHasPermission('admin.cms:0.can_destroy_page')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id   = $this->uri->segment(5);
        $page = $this->cms_page_model->get_by_id($id);

        if ($page) {

            if ($this->cms_page_model->destroy($id)) {

                $this->session->set_flashdata('success', '<strong>Success!</strong> Page was destroyed successfully. ');

            } else {

                $this->session->set_flashdata('error', '<strong>Sorry,</strong> Could not destroy page. ' . $this->cms_page_model->last_error());
            }

        } else {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> invalid page ID.');
        }

        redirect('admin/cms/pages');
    }
}
