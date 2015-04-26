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
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin:cms:pages:manage')) {

            //  Alerts
            $alerts = array();
            $ci     =& get_instance();

            //  Draft pages
            $ci->db->where('is_published', false);
            $ci->db->where('is_deleted', false);
            $numDrafts = $ci->db->count_all_results(NAILS_DB_PREFIX . 'cms_page');
            $alerts[]  = \Nails\Admin\Nav::alertObject($numDrafts, 'alert', 'Draft Pages');

            $navGroup = new \Nails\Admin\Nav('CMS', 'fa-file-text');
            $navGroup->addAction('Manage Pages', 'index', $alerts);
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

        $permissions['manage']  = 'Can manage pages';
        $permissions['create']  = 'Can create a new page';
        $permissions['edit']    = 'Can edit an existing page';
        $permissions['preview'] = 'Can preview pages';
        $permissions['delete']  = 'Can delete an existing page';
        $permissions['restore'] = 'Can restore a deleted page';
        $permissions['destroy'] = 'Can permenantly delete a page';

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
        if (!userHasPermission('admin:cms:pages:manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Page Title
        $this->data['page']->title = 'Manage Pages';

        // --------------------------------------------------------------------------

        //  Fetch all the pages in the DB
        $this->data['pages'] = $this->cms_page_model->get_all();

        // --------------------------------------------------------------------------

        //  Add a header button
        if (userHasPermission('admin:cms:pages:create')) {

            \Nails\Admin\Helper::addHeaderButton('admin/cms/pages/create', 'Add New Page');
        }

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.cms.pages.min.js', 'NAILS');
        $this->asset->inline('var CMS_Pages = new NAILS_Admin_CMS_Pages();', 'JS');

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
        if (!userHasPermission('admin:cms:pages:create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Get data
        $this->data['pagesNestedFlat'] = $this->cms_page_model->getAllNestedFlat(' &rsaquo; ', false);

        //  Set method info
        $this->data['page']->title  = 'Create New Page';

        //  Get available templates & widgets
        $this->data['templates'] = $this->cms_page_model->getAvailableTemplates('EDITOR');
        $this->data['widgets']  = $this->cms_page_model->getAvailableWidgets('EDITOR');

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->library('jqueryui');
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.cms.pages.createEdit.min.js', 'NAILS');

        $inlineJs  = 'CMS_PAGES = new NAILS_Admin_CMS_pages_Create_Edit(';
        $inlineJs .= json_encode($this->data['templates']) . ',';
        $inlineJs .= json_encode($this->data['widgets']);
        $inlineJs .= ');';

        $this->asset->inline($inlineJs, 'JS');

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
        if (!userHasPermission('admin:cms:pages:edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['cmspage'] = $this->cms_page_model->get_by_id($this->uri->segment(5));

        if (!$this->data['cmspage']) {

            $this->session->set_flashdata('error', 'No page found by that ID');
            redirect('admin/cms/pages');
        }

        // --------------------------------------------------------------------------

        //  Get data
        $this->data['pagesNestedFlat'] = $this->cms_page_model->getAllNestedFlat(' &rsaquo; ', false);

        //  Set method info
        $this->data['page']->title = 'Edit Page "' . $this->data['cmspage']->draft->title . '"';

        //  Get available templates & widgets
        $this->data['templates'] = $this->cms_page_model->getAvailableTemplates('EDITOR');
        $this->data['widgets']   = $this->cms_page_model->getAvailableWidgets('EDITOR');

        //  Get children of this page
        $this->data['page_children'] = $this->cms_page_model->getIdsOfChildren($this->data['cmspage']->id);

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->library('jqueryui');
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.cms.pages.createEdit.min.js', 'NAILS');

        $inlineJs  = 'CMS_PAGES = new NAILS_Admin_CMS_pages_Create_Edit(';
        $inlineJs .= json_encode($this->data['templates']) . ',';
        $inlineJs .= json_encode($this->data['widgets']). ',';
        $inlineJs .= $this->data['cmspage']->id . ',';
        $inlineJs .= json_encode($this->data['cmspage']->draft->template_data);
        $inlineJs .= ');';

        $this->asset->inline($inlineJs, 'JS');

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
        if (!userHasPermission('admin:cms:pages:edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id   = $this->uri->segment(5);
        $page = $this->cms_page_model->get_by_id($id);

        if ($page && !$page->is_deleted) {

            if ($this->cms_page_model->publish($id)) {

                $this->session->set_flashdata('success', 'Page was published successfully.');

            } else {

                $this->session->set_flashdata('error', 'Could not publish page. ' . $this->cms_page_model->last_error());
            }

        } else {

            $this->session->set_flashdata('error', 'Invalid page ID.');
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
        if (!userHasPermission('admin:cms:pages:delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id   = $this->uri->segment(5);
        $page = $this->cms_page_model->get_by_id($id);

        if ($page && !$page->is_deleted) {

            if ($this->cms_page_model->delete($id)) {

                $this->session->set_flashdata('success', 'Page was deleted successfully.');

            } else {

                $this->session->set_flashdata('error', 'Could not delete page. ' . $this->cms_page_model->last_error());
            }

        } else {

            $this->session->set_flashdata('error', 'Invalid page ID.');
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
        if (!userHasPermission('admin:cms:pages:restore')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id   = $this->uri->segment(5);
        $page = $this->cms_page_model->get_by_id($id);

        if ($page && $page->is_deleted) {

            if ($this->cms_page_model->restore($id)) {

                $this->session->set_flashdata('success', 'Page was restored successfully. ');

            } else {

                $this->session->set_flashdata('error', 'Could not restore page. ' . $this->cms_page_model->last_error());
            }

        } else {

            $this->session->set_flashdata('error', 'Invalid page ID.');
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
        if (!userHasPermission('admin:cms:pages:destroy')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id   = $this->uri->segment(5);
        $page = $this->cms_page_model->get_by_id($id);

        if ($page) {

            if ($this->cms_page_model->destroy($id)) {

                $this->session->set_flashdata('success', 'Page was destroyed successfully. ');

            } else {

                $this->session->set_flashdata('error', 'Could not destroy page. ' . $this->cms_page_model->last_error());
            }

        } else {

            $this->session->set_flashdata('error', 'Invalid page ID.');
        }

        redirect('admin/cms/pages');
    }
}
