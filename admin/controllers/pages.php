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

use Nails\Factory;
use Nails\Admin\Helper;
use Nails\Cms\Controller\BaseAdmin;

class Pages extends BaseAdmin
{
    protected $oPageModel;
    protected $oWidgetModel;
    protected $oTemplateModel;
    protected $iHomepageId;

    // --------------------------------------------------------------------------

    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin:cms:pages:manage')) {

            //  Alerts
            $oCi =& get_instance();

            //  Draft pages
            $oCi->db->where('is_published', false);
            $oCi->db->where('is_deleted', false);
            $iNumDrafts = $oCi->db->count_all_results(NAILS_DB_PREFIX . 'cms_page');

            $oAlert = Factory::factory('NavAlert', 'nailsapp/module-admin');
            $oAlert->setValue($iNumDrafts);
            $oAlert->setSeverity('danger');
            $oAlert->setLabel('Draft Pages');

            $oNavGroup = Factory::factory('Nav', 'nailsapp/module-admin');
            $oNavGroup->setLabel('CMS');
            $oNavGroup->setIcon('fa-file-text');
            $oNavGroup->addAction('Manage Pages', 'index', array($oAlert));

            return $oNavGroup;
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
        $this->oPageModel     = Factory::model('Page', 'nailsapp/module-cms');
        $this->oWidgetModel   = Factory::model('Widget', 'nailsapp/module-cms');
        $this->oTemplateModel = Factory::model('Template', 'nailsapp/module-cms');

        //  Note the ID of the homepage
        $this->iHomepageId = appSetting('homepage', 'nailsapp/module-cms');
        $this->data['iHomepageId'] = $this->iHomepageId;
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

        $sTableAlias = $this->oPageModel->getTableAlias();

        //  Get pagination and search/sort variables
        $page      = $this->input->get('page')      ? $this->input->get('page')      : 0;
        $perPage   = $this->input->get('perPage')   ? $this->input->get('perPage')   : 50;
        $sortOn    = $this->input->get('sortOn')    ? $this->input->get('sortOn')    : $sTableAlias . '.draft_slug';
        $sortOrder = $this->input->get('sortOrder') ? $this->input->get('sortOrder') : 'asc';
        $keywords  = $this->input->get('keywords')  ? $this->input->get('keywords')  : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns
        $sortColumns = array(
            $sTableAlias . '.draft_slug' => 'Hierarchy',
            $sTableAlias . '.draft_title' => 'Label',
            $sTableAlias . '.modified' => 'Modified'
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
        $totalRows           = $this->oPageModel->countAll($data);
        $this->data['pages'] = $this->oPageModel->getAll($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = Helper::searchObject(true, $sortColumns, $sortOn, $sortOrder, $perPage, $keywords);
        $this->data['pagination'] = Helper::paginationObject($page, $perPage, $totalRows);

        // --------------------------------------------------------------------------

        //  Add a header button
        if (userHasPermission('admin:cms:pages:create')) {

            Helper::addHeaderButton('admin/cms/pages/create', 'Add New Page');
        }

        // --------------------------------------------------------------------------

        Helper::loadView('index');
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

        if ($this->input->post()) {

            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('title', '', 'xss_clean');
            $oFormValidation->set_rules('slug', '', 'xss_clean|alpha_dash');
            $oFormValidation->set_rules('parent_id', '', 'xss_clean|is_natural');
            $oFormValidation->set_rules('template', '', 'xss_clean|trim|required');
            $oFormValidation->set_rules('template_data', '', 'trim');
            $oFormValidation->set_rules('template_options', '', 'is_array');
            $oFormValidation->set_rules('seo_title', '', 'xss_clean|trim|max_length[150]');
            $oFormValidation->set_rules('seo_description', '', 'xss_clean|trim|max_length[300]');
            $oFormValidation->set_rules('seo_keywords', '', 'xss_clean|trim|max_length[150]');
            $oFormValidation->set_rules('action', '', 'required');

            $oFormValidation->set_message('alpha_dash', lang('fv_alpha_dash'));
            $oFormValidation->set_message('is_natural', 'Please select a valid Parent Page.');
            $oFormValidation->set_message('max_length', 'Exceeds maximum length (%2$s characters)');

            if ($oFormValidation->run()) {

                $aPageData = array(
                    'title' => $this->input->post('title'),
                    'slug' => $this->input->post('slug'),
                    'parent_id' => (int) $this->input->post('parent_id'),
                    'template' => $this->input->post('template'),
                    'template_data' => $this->input->post('template_data'),
                    'template_options' => $this->input->post('template_options'),
                    'seo_title' => $this->input->post('seo_title'),
                    'seo_description' => $this->input->post('seo_description'),
                    'seo_keywords' => $this->input->post('seo_keywords')
                );

                $aPageData['parent_id'] = !empty($aPageData['parent_id']) ? $aPageData['parent_id'] : null;

                if (!empty($aPageData['template_options'][$aPageData['template']])) {

                    $aPageData['template_options'] = $aPageData['template_options'][$aPageData['template']];
                    $aPageData['template_options'] = json_encode($aPageData['template_options']);

                } else {

                    $aPageData['template_options'] = null;
                }

                $oNewPageId = $this->oPageModel->create($aPageData);
                if ($oNewPageId) {

                    if ($this->input->post('action') == 'PUBLISH') {

                        redirect('admin/cms/pages/publish/' . $oNewPageId . '?editing=1');

                    } else {

                        $sStatus  = 'success';
                        $sMessage = 'Page created successfully!';
                        $this->session->set_flashdata($sStatus, $sMessage);
                        redirect('admin/cms/pages/edit/' . $oNewPageId);
                    }

                } else {

                    $this->data['error'] = 'Failed to create page. ' . $this->oPageModel->lastError();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title  = 'Create Page';

        //  Get data, available templates & widgets
        $this->data['pagesNestedFlat'] = $this->oPageModel->getAllNestedFlat(' &rsaquo; ', false);
        $this->data['templates']       = $this->oTemplateModel->getAvailable('EDITOR');

        $aTemplatesJson = array();
        foreach ($this->data['templates'] as $oTemplateGroup) {
            $aTemplatesJson[] = $oTemplateGroup->getTemplatesAsJson();
        }

        // --------------------------------------------------------------------------

        //  Set the default template; either POST data or the first in the list.
        $this->data['defaultTemplate'] = '';
        if ($this->input->post('template')) {

            $this->data['defaultTemplate'] = $this->input->post('template');

        } else {

            $oFirstGroup = reset($this->data['templates']);
            if (!empty($oFirstGroup)) {

                $aTemplates = $oFirstGroup->getTemplates();
                $oFirstTemplate = reset($aTemplates);
                if (!empty($oFirstTemplate)) {
                    $this->data['defaultTemplate'] = $oFirstTemplate->getSlug();
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->library('CMSWIDGETEDITOR');
        $this->asset->load('admin.pages.edit.min.js', 'nailsapp/module-cms');
        $this->asset->inline('var widgetEditor = new NAILS_Admin_CMS_WidgetEditor();', 'JS');
        $this->asset->inline('var templates = [' . implode(',', $aTemplatesJson) . ']', 'JS');
        $this->asset->inline('var pageEdit = new NAILS_Admin_CMS_Pages_CreateEdit(widgetEditor, templates);', 'JS');

        // --------------------------------------------------------------------------

        Helper::loadView('edit');
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

        $oPage = $this->oPageModel->getById($this->uri->segment(5));

        if (!$oPage) {

            $this->session->set_flashdata('error', 'No page found by that ID');
            redirect('admin/cms/pages');
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('title', '', 'xss_clean');
            $oFormValidation->set_rules('slug', '', 'xss_clean|alpha_dash');
            $oFormValidation->set_rules('parent_id', '', 'xss_clean|is_natural');
            $oFormValidation->set_rules('template', '', 'xss_clean|trim|required');
            $oFormValidation->set_rules('template_data', '', 'trim');
            $oFormValidation->set_rules('template_options', '', 'is_array');
            $oFormValidation->set_rules('seo_title', '', 'xss_clean|trim|max_length[150]');
            $oFormValidation->set_rules('seo_description', '', 'xss_clean|trim|max_length[300]');
            $oFormValidation->set_rules('seo_keywords', '', 'xss_clean|trim|max_length[150]');
            $oFormValidation->set_rules('action', '', 'required');

            $oFormValidation->set_message('alpha_dash', lang('fv_alpha_dash'));
            $oFormValidation->set_message('is_natural', 'Please select a valid Parent Page.');
            $oFormValidation->set_message('max_length', 'Exceeds maximum length (%2$s characters)');

            if ($oFormValidation->run()) {

                $aPageData = array(
                    'title' => $this->input->post('title'),
                    'slug' => $this->input->post('slug'),
                    'parent_id' => (int) $this->input->post('parent_id'),
                    'template' => $this->input->post('template'),
                    'template_data' => $this->input->post('template_data'),
                    'template_options' => $this->input->post('template_options'),
                    'seo_title' => $this->input->post('seo_title'),
                    'seo_description' => $this->input->post('seo_description'),
                    'seo_keywords' => $this->input->post('seo_keywords')
                );

                $aPageData['parent_id'] = !empty($aPageData['parent_id']) ? $aPageData['parent_id'] : null;

                if (!empty($aPageData['template_options'][$aPageData['template']])) {

                    $aPageData['template_options'] = $aPageData['template_options'][$aPageData['template']];
                    $aPageData['template_options'] = json_encode($aPageData['template_options']);

                } else {

                    $aPageData['template_options'] = null;
                }

                if ($this->oPageModel->update($oPage->id, $aPageData)) {

                    if ($this->input->post('action') == 'PUBLISH') {

                        redirect('admin/cms/pages/publish/' . $oPage->id . '?editing=1');

                    } else {

                        $sStatus  = 'success';
                        $sMessage = 'Page saved successfully!';
                        $this->session->set_flashdata($sStatus, $sMessage);
                        redirect('admin/cms/pages/edit/' . $oPage->id);
                    }

                } else {

                    $this->data['error'] = 'Failed to update page. ' . $this->oPageModel->lastError();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Edit Page "' . $oPage->draft->title . '"';

        //  Get data, available templates & widgets
        $this->data['cmspage']         = $oPage;
        $this->data['pagesNestedFlat'] = $this->oPageModel->getAllNestedFlat(' &rsaquo; ', false);
        $this->data['templates']       = $this->oTemplateModel->getAvailable('EDITOR');

        $aTemplatesJson = array();
        foreach ($this->data['templates'] as $oTemplateGroup) {
            $aTemplatesJson[] = substr($oTemplateGroup->getTemplatesAsJson(), 1, -1);
        }

        // --------------------------------------------------------------------------

        //  Set the default template; either POST data or the first in the list.
        $this->data['defaultTemplate'] = '';
        if ($this->input->post('template')) {

            $this->data['defaultTemplate'] = $this->input->post('template');

        } elseif (!empty($oPage->draft->template)) {

            $this->data['defaultTemplate'] = $oPage->draft->template;

        } else {

            $oFirstGroup = reset($this->data['templates']);
            if (!empty($oFirstGroup)) {

                $aTemplates = $oFirstGroup->getTemplates();
                $oFirstTemplate = reset($aTemplates);
                if (!empty($oFirstTemplate)) {
                    $this->data['defaultTemplate'] = $oFirstTemplate->getSlug();
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->library('CMSWIDGETEDITOR');
        $this->asset->load('admin.pages.edit.min.js', 'nailsapp/module-cms');
        $this->asset->inline('var widgetEditor = new NAILS_Admin_CMS_WidgetEditor();', 'JS');
        $this->asset->inline('var templates = [' . implode(',', $aTemplatesJson) . ']', 'JS');
        $this->asset->inline('var pageEdit = new NAILS_Admin_CMS_Pages_CreateEdit(widgetEditor, templates);', 'JS');

        // --------------------------------------------------------------------------

        Helper::loadView('edit');
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

        $iId        = $this->uri->segment(5);
        $bIsEditing = (bool) $this->input->get('editing');

        if ($this->oPageModel->publish($iId)) {

            $oPage = $this->oPageModel->getById($iId);
            $this->session->set_flashdata(
                'success',
                'Page was published successfully - ' .
                anchor(
                    $oPage->published->url,
                    'View Page <b class="fa fa-external-link"></b>',
                    'target="_blank"'
                )
            );

            if ($bIsEditing) {

                redirect('admin/cms/pages/edit/' . $iId);

            } else {

                redirect('admin/cms/pages');
            }

        } else {

            $this->session->set_flashdata('error', 'Could not publish page. ' . $this->oPageModel->lastError());
        }
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
        $page = $this->oPageModel->getById($id);

        if ($page && !$page->is_deleted) {

            if ($this->oPageModel->delete($id)) {

                $this->session->set_flashdata('success', 'Page was deleted successfully.');

            } else {

                $this->session->set_flashdata('error', 'Could not delete page. ' . $this->oPageModel->lastError());
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
        $page = $this->oPageModel->getById($id);

        if ($page && $page->is_deleted) {

            if ($this->oPageModel->restore($id)) {

                $this->session->set_flashdata('success', 'Page was restored successfully. ');

            } else {

                $this->session->set_flashdata('error', 'Could not restore page. ' . $this->oPageModel->lastError());
            }

        } else {

            $this->session->set_flashdata('error', 'Invalid page ID.');
        }

        redirect('admin/cms/pages');
    }
}
