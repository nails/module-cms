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
     * @return \Nails\Admin\Nav
     */
    public static function announce()
    {
        if (userHasPermission('admin:cms:pages:manage')) {

            //  Alerts
            //  Draft pages
            $oDb = Factory::service('Database');
            $oDb->where('is_published', false);
            $oDb->where('is_deleted', false);
            $iNumDrafts = $oDb->count_all_results(NAILS_DB_PREFIX . 'cms_page');

            $oAlert = Factory::factory('NavAlert', 'nails/module-admin');
            $oAlert->setValue($iNumDrafts);
            $oAlert->setSeverity('danger');
            $oAlert->setLabel('Draft Pages');

            $oNavGroup = Factory::factory('Nav', 'nails/module-admin');
            $oNavGroup->setLabel('CMS');
            $oNavGroup->setIcon('fa-file-text');
            $oNavGroup->addAction('Manage Pages', 'index', [$oAlert]);

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
        $aPermissions = parent::permissions();

        $aPermissions['manage']  = 'Can manage pages';
        $aPermissions['create']  = 'Can create a new page';
        $aPermissions['edit']    = 'Can edit an existing page';
        $aPermissions['preview'] = 'Can preview pages';
        $aPermissions['delete']  = 'Can delete an existing page';
        $aPermissions['restore'] = 'Can restore a deleted page';
        $aPermissions['destroy'] = 'Can permanently delete a page';

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

        //  Load common items
        $this->oPageModel     = Factory::model('Page', 'nails/module-cms');
        $this->oWidgetModel   = Factory::model('Widget', 'nails/module-cms');
        $this->oTemplateModel = Factory::model('Template', 'nails/module-cms');

        //  Note the ID of the homepage
        $this->iHomepageId         = appSetting('homepage', 'nails/module-cms');
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
        $oInput    = Factory::service('Input');
        $page      = $oInput->get('page') ? $oInput->get('page') : 0;
        $perPage   = $oInput->get('perPage') ? $oInput->get('perPage') : 50;
        $sortOn    = $oInput->get('sortOn') ? $oInput->get('sortOn') : $sTableAlias . '.draft_slug';
        $sortOrder = $oInput->get('sortOrder') ? $oInput->get('sortOrder') : 'asc';
        $keywords  = $oInput->get('keywords') ? $oInput->get('keywords') : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns
        $sortColumns = [
            $sTableAlias . '.draft_slug'  => 'Hierarchy',
            $sTableAlias . '.draft_title' => 'Label',
            $sTableAlias . '.modified'    => 'Modified',
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

        $oInput = Factory::service('Input');
        if ($oInput->post()) {

            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('title', '', '');
            $oFormValidation->set_rules('slug', '', 'alpha_dash');
            $oFormValidation->set_rules('parent_id', '', 'is_natural');
            $oFormValidation->set_rules('template', '', 'trim|required');
            $oFormValidation->set_rules('template_data', '', 'trim');
            $oFormValidation->set_rules('template_options', '', 'is_array');
            $oFormValidation->set_rules('seo_title', '', 'trim|max_length[150]');
            $oFormValidation->set_rules('seo_description', '', 'trim|max_length[300]');
            $oFormValidation->set_rules('seo_keywords', '', 'trim|max_length[150]');
            $oFormValidation->set_rules('action', '', 'required');

            $oFormValidation->set_message('alpha_dash', lang('fv_alpha_dash'));
            $oFormValidation->set_message('is_natural', 'Please select a valid Parent Page.');
            $oFormValidation->set_message('max_length', 'Exceeds maximum length (%2$s characters)');

            if ($oFormValidation->run()) {

                $aPageData = [
                    'title'            => $oInput->post('title'),
                    'slug'             => $oInput->post('slug'),
                    'parent_id'        => (int) $oInput->post('parent_id'),
                    'template'         => $oInput->post('template'),
                    'template_data'    => $oInput->post('template_data'),
                    'template_options' => $oInput->post('template_options'),
                    'seo_title'        => $oInput->post('seo_title'),
                    'seo_description'  => $oInput->post('seo_description'),
                    'seo_keywords'     => $oInput->post('seo_keywords'),
                ];

                $aPageData['parent_id'] = !empty($aPageData['parent_id']) ? $aPageData['parent_id'] : null;

                if (!empty($aPageData['template_options'][$aPageData['template']])) {
                    $aPageData['template_options'] = $aPageData['template_options'][$aPageData['template']];
                    $aPageData['template_options'] = json_encode($aPageData['template_options']);
                } else {
                    $aPageData['template_options'] = null;
                }

                $oNewPageId = $this->oPageModel->create($aPageData);
                if ($oNewPageId) {

                    if ($oInput->post('action') == 'PUBLISH') {

                        redirect('admin/cms/pages/publish/' . $oNewPageId . '?editing=1');

                    } else {

                        $sStatus  = 'success';
                        $sMessage = 'Page created successfully!';
                        $oSession = Factory::service('Session', 'nails/module-auth');
                        $oSession->setFlashData($sStatus, $sMessage);
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
        $this->data['page']->title = 'Create Page';

        //  Get data, available templates & widgets
        $this->data['pagesNestedFlat'] = $this->oPageModel->getAllNestedFlat(' &rsaquo; ', false);
        $this->data['templates']       = $this->oTemplateModel->getAvailable('EDITOR');

        $aTemplatesJson = [];
        foreach ($this->data['templates'] as $oTemplateGroup) {
            $aTemplatesJson[] = $oTemplateGroup->getTemplatesAsJson();
        }

        // --------------------------------------------------------------------------

        //  Set the default template; either POST data or the first in the list.
        $this->data['defaultTemplate'] = '';
        if ($oInput->post('template')) {

            $this->data['defaultTemplate'] = $oInput->post('template');

        } else {

            $oFirstGroup = reset($this->data['templates']);
            if (!empty($oFirstGroup)) {

                $aTemplates     = $oFirstGroup->getTemplates();
                $oFirstTemplate = reset($aTemplates);
                if (!empty($oFirstTemplate)) {
                    $this->data['defaultTemplate'] = $oFirstTemplate->getSlug();
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Assets
        $oAsset = Factory::service('Asset');
        $oAsset->library('CMSWIDGETEDITOR');
        //  @todo (Pablo - 2018-12-01) - Update/Remove/Use minified once JS is refactored to be a module
        $oAsset->load('admin.pages.edit.js', 'nails/module-cms');
        $oAsset->inline('var widgetEditor = new NAILS_Admin_CMS_WidgetEditor();', 'JS');
        $oAsset->inline('var templates = [' . implode(',', $aTemplatesJson) . ']', 'JS');
        $oAsset->inline('var pageEdit = new NAILS_Admin_CMS_Pages_CreateEdit(widgetEditor, templates);', 'JS');

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

        $oUri  = Factory::service('Uri');
        $oPage = $this->oPageModel->getById($oUri->segment(5));

        if (!$oPage) {
            $oSession = Factory::service('Session', 'nails/module-auth');
            $oSession->setFlashData('error', 'No page found by that ID');
            redirect('admin/cms/pages');
        }

        // --------------------------------------------------------------------------

        $oInput = Factory::service('Input');
        if ($oInput->post()) {

            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('title', '', '');
            $oFormValidation->set_rules('slug', '', 'alpha_dash');
            $oFormValidation->set_rules('parent_id', '', 'is_natural');
            $oFormValidation->set_rules('template', '', 'trim|required');
            $oFormValidation->set_rules('template_data', '', 'trim');
            $oFormValidation->set_rules('template_options', '', 'is_array');
            $oFormValidation->set_rules('seo_title', '', 'trim|max_length[150]');
            $oFormValidation->set_rules('seo_description', '', 'trim|max_length[300]');
            $oFormValidation->set_rules('seo_keywords', '', 'trim|max_length[150]');
            $oFormValidation->set_rules('action', '', 'required');

            $oFormValidation->set_message('alpha_dash', lang('fv_alpha_dash'));
            $oFormValidation->set_message('is_natural', 'Please select a valid Parent Page.');
            $oFormValidation->set_message('max_length', 'Exceeds maximum length (%2$s characters)');

            if ($oFormValidation->run()) {

                $aPageData = [
                    'title'            => $oInput->post('title'),
                    'slug'             => $oInput->post('slug'),
                    'parent_id'        => (int) $oInput->post('parent_id'),
                    'template'         => $oInput->post('template'),
                    'template_data'    => $oInput->post('template_data'),
                    'template_options' => $oInput->post('template_options'),
                    'seo_title'        => $oInput->post('seo_title'),
                    'seo_description'  => $oInput->post('seo_description'),
                    'seo_keywords'     => $oInput->post('seo_keywords'),
                ];

                $aPageData['parent_id'] = !empty($aPageData['parent_id']) ? $aPageData['parent_id'] : null;

                if (!empty($aPageData['template_options'][$aPageData['template']])) {
                    $aPageData['template_options'] = $aPageData['template_options'][$aPageData['template']];
                    $aPageData['template_options'] = json_encode($aPageData['template_options']);
                } else {
                    $aPageData['template_options'] = null;
                }

                if ($this->oPageModel->update($oPage->id, $aPageData)) {

                    if ($oInput->post('action') == 'PUBLISH') {

                        redirect('admin/cms/pages/publish/' . $oPage->id . '?editing=1');

                    } else {

                        $sStatus  = 'success';
                        $sMessage = 'Page saved successfully!';
                        $oSession = Factory::service('Session', 'nails/module-auth');
                        $oSession->setFlashData($sStatus, $sMessage);
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

        $aTemplatesJson = [];
        foreach ($this->data['templates'] as $oTemplateGroup) {
            $aTemplatesJson[] = substr($oTemplateGroup->getTemplatesAsJson(), 1, -1);
        }

        // --------------------------------------------------------------------------

        //  Set the default template; either POST data or the first in the list.
        $this->data['defaultTemplate'] = '';
        if ($oInput->post('template')) {

            $this->data['defaultTemplate'] = $oInput->post('template');

        } elseif (!empty($oPage->draft->template)) {

            $this->data['defaultTemplate'] = $oPage->draft->template;

        } else {

            $oFirstGroup = reset($this->data['templates']);
            if (!empty($oFirstGroup)) {

                $aTemplates     = $oFirstGroup->getTemplates();
                $oFirstTemplate = reset($aTemplates);
                if (!empty($oFirstTemplate)) {
                    $this->data['defaultTemplate'] = $oFirstTemplate->getSlug();
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Assets
        $oAsset = Factory::service('Asset');
        $oAsset->library('CMSWIDGETEDITOR');
        //  @todo (Pablo - 2018-12-01) - Update/Remove/Use minified once JS is refactored to be a module
        $oAsset->load('admin.pages.edit.js', 'nails/module-cms');
        $oAsset->inline('var widgetEditor = new NAILS_Admin_CMS_WidgetEditor();', 'JS');
        $oAsset->inline('var templates = [' . implode(',', $aTemplatesJson) . ']', 'JS');
        $oAsset->inline('var pageEdit = new NAILS_Admin_CMS_Pages_CreateEdit(widgetEditor, templates);', 'JS');

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

        $oUri       = Factory::service('Uri');
        $oInput     = Factory::service('Input');
        $oSession   = Factory::service('Session', 'nails/module-auth');
        $iId        = $oUri->segment(5);
        $bIsEditing = (bool) $oInput->get('editing');

        if ($this->oPageModel->publish($iId)) {

            $oPage = $this->oPageModel->getById($iId);
            $oSession->setFlashData(
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
            $oSession->setFlashData('error', 'Could not publish page. ' . $this->oPageModel->lastError());
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

        $oUri     = Factory::service('Uri');
        $oSession = Factory::service('Session', 'nails/module-auth');
        $id       = $oUri->segment(5);
        $page     = $this->oPageModel->getById($id);

        if ($page && !$page->is_deleted) {
            if ($this->oPageModel->delete($id)) {
                $oSession->setFlashData('success', 'Page was deleted successfully.');
            } else {
                $oSession->setFlashData('error', 'Could not delete page. ' . $this->oPageModel->lastError());
            }
        } else {
            $oSession->setFlashData('error', 'Invalid page ID.');
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

        $oUri     = Factory::service('Uri');
        $oSession = Factory::service('Session', 'nails/module-auth');
        $id       = $oUri->segment(5);
        $page     = $this->oPageModel->getById($id);

        if ($page && $page->is_deleted) {
            if ($this->oPageModel->restore($id)) {
                $oSession->setFlashData('success', 'Page was restored successfully. ');
            } else {
                $oSession->setFlashData('error', 'Could not restore page. ' . $this->oPageModel->lastError());
            }
        } else {
            $oSession->setFlashData('error', 'Invalid page ID.');
        }

        redirect('admin/cms/pages');
    }
}
