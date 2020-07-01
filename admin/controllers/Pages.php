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

use Nails\Admin\Factory\Nav;
use Nails\Admin\Factory\Nav\Alert;
use Nails\Admin\Helper;
use Nails\Cms\Constants;
use Nails\Cms\Controller\BaseAdmin;
use Nails\Cms\Exception\Template\NotFoundException;
use Nails\Cms\Model\Page;
use Nails\Cms\Service\Template;
use Nails\Cms\Service\Widget;
use Nails\Common\Exception\AssetException;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Exception\NailsException;
use Nails\Common\Exception\ValidationException;
use Nails\Common\Resource;
use Nails\Common\Service\Asset;
use Nails\Common\Service\Database;
use Nails\Common\Service\FormValidation;
use Nails\Common\Service\Input;
use Nails\Common\Service\Session;
use Nails\Common\Service\Uri;
use Nails\Components;
use Nails\Config;
use Nails\Factory;
use Nails\Redirect;

/**
 * Class Pages
 *
 * @package Nails\Admin\Cms
 */
class Pages extends BaseAdmin
{
    /** @var Page */
    protected $oPageModel;

    /** @var Widget */
    protected $oWidgetService;

    /** @var Template */
    protected $oTemplateService;

    /** @var int */
    protected $iHomepageId;

    // --------------------------------------------------------------------------

    /**
     * Announces this controller's navGroups
     *
     * @return \Nails\Admin\Factory\Nav
     */
    public static function announce()
    {
        if (userHasPermission('admin:cms:pages:manage')) {

            //  Alerts
            //  Draft pages
            /** @var Database $oDb */
            $oDb = Factory::service('Database');
            $oDb->where('is_published', false);
            $oDb->where('is_deleted', false);
            $iNumDrafts = $oDb->count_all_results(Config::get('NAILS_DB_PREFIX') . 'cms_page');

            /** @var Alert $oAlert */
            $oAlert = Factory::factory('NavAlert', 'nails/module-admin');
            $oAlert->setValue($iNumDrafts);
            $oAlert->setSeverity('danger');
            $oAlert->setLabel('Draft Pages');

            /** @var Nav $oNavGroup */
            $oNavGroup = Factory::factory('Nav', 'nails/module-admin');
            $oNavGroup->setLabel('CMS');
            $oNavGroup->setIcon('fa-file-alt');
            $oNavGroup->addAction('Manage Pages', 'index', [$oAlert]);

            return $oNavGroup;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of permissions which can be configured for the user
     *
     * @return array
     */
    public static function permissions(): array
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
     * Pages constructor.
     *
     * @throws FactoryException
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        //  Load common items
        /** @var Page oPageModel */
        $this->oPageModel = Factory::model('Page', Constants::MODULE_SLUG);
        /** @var Widget oWidgetService */
        $this->oWidgetService = Factory::service('Widget', Constants::MODULE_SLUG);
        /** @var Template oTemplateService */
        $this->oTemplateService = Factory::service('Template', Constants::MODULE_SLUG);

        //  Note the ID of the homepage
        $this->iHomepageId         = appSetting('homepage', Constants::MODULE_SLUG);
        $this->data['iHomepageId'] = $this->iHomepageId;
    }

    // --------------------------------------------------------------------------

    /**
     * Browse CMS Pages
     *
     * @return void
     * @throws FactoryException
     * @throws ModelException
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

        /** @var Input $oInput */
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
        $this->data['sReturnTo']  = urlencode($oInput->server('REQUEST_URI'));

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
     *
     * @return void
     * @throws FactoryException
     * @throws ModelException
     * @throws NotFoundException
     * @throws AssetException
     */
    public function create()
    {
        if (!userHasPermission('admin:cms:pages:create')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        /** @var Input $oInput */
        $oInput = Factory::service('Input');
        if ($oInput->post()) {

            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('title', '', '');
            $oFormValidation->set_rules('slug', '', 'alpha_dash');
            $oFormValidation->set_rules('parent_id', '', 'is_natural');
            $oFormValidation->set_rules('template', '', 'trim|required');
            $oFormValidation->set_rules('template_data', '', 'trim');
            $oFormValidation->set_rules('template_options[]', '', 'is_array');
            $oFormValidation->set_rules('seo_title', '', 'trim|max_length[150]');
            $oFormValidation->set_rules('seo_description', '', 'trim|max_length[300]');
            $oFormValidation->set_rules('seo_keywords', '', 'trim|max_length[150]');
            $oFormValidation->set_rules('seo_image_id', '', 'is_natural');
            $oFormValidation->set_rules('action', '', 'required');

            $oFormValidation->set_message('alpha_dash', lang('fv_alpha_dash'));
            $oFormValidation->set_message('is_natural', 'Please select a valid Parent Page.');
            $oFormValidation->set_message('max_length', 'Exceeds maximum length (%2$s characters)');

            if ($oFormValidation->run()) {

                $aPageData = [
                    'title'            => $oInput->post('title'),
                    'slug'             => $oInput->post('slug'),
                    'parent_id'        => (int) $oInput->post('parent_id') ?: null,
                    'template'         => $oInput->post('template'),
                    'template_data'    => $oInput->post('template_data'),
                    'template_options' => $oInput->post('template_options'),
                    'seo_title'        => $oInput->post('seo_title'),
                    'seo_description'  => $oInput->post('seo_description'),
                    'seo_keywords'     => $oInput->post('seo_keywords'),
                    'seo_image_id'     => (int) $oInput->post('seo_keywords') ?: null,
                ];

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
                        /** @var Session $oSession */
                        $oSession = Factory::service('Session');
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
        $this->data['templates']       = $this->oTemplateService->getAvailable('EDITOR');

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

        /** @var Asset $oAsset */
        $oAsset = Factory::service('Asset');
        $oAsset->load('admin.pages.edit.js', Constants::MODULE_SLUG);
        $oAsset->inline(implode("\n", [
            'var templates = [' . implode(',', $aTemplatesJson) . ']',
            'var pageEdit = new NAILS_Admin_CMS_Pages_CreateEdit(templates);',
        ]), 'JS');

        // --------------------------------------------------------------------------

        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a CMS Page
     *
     * @return void
     * @throws FactoryException
     * @throws ModelException
     * @throws NailsException
     * @throws NotFoundException
     * @throws AssetException
     */
    public function edit()
    {
        if (!userHasPermission('admin:cms:pages:edit')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        /** @var Uri $oUri */
        $oUri  = Factory::service('Uri');
        $oPage = $this->oPageModel->getById($oUri->segment(5));

        if (!$oPage) {
            /** @var Session $oSession */
            $oSession = Factory::service('Session');
            $oSession->setFlashData('error', 'No page found by that ID');
            redirect('admin/cms/pages');
        }

        // --------------------------------------------------------------------------

        /** @var Input $oInput */
        $oInput = Factory::service('Input');
        if ($oInput->post()) {

            /** @var FormValidation $oFormValidation */
            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('title', '', '');
            $oFormValidation->set_rules('slug', '', 'alpha_dash');
            $oFormValidation->set_rules('parent_id', '', 'is_natural');
            $oFormValidation->set_rules('template', '', 'trim|required');
            $oFormValidation->set_rules('template_data', '', 'trim');
            $oFormValidation->set_rules('template_options[]', '', 'is_array');
            $oFormValidation->set_rules('seo_title', '', 'trim|max_length[150]');
            $oFormValidation->set_rules('seo_description', '', 'trim|max_length[300]');
            $oFormValidation->set_rules('seo_keywords', '', 'trim|max_length[150]');
            $oFormValidation->set_rules('seo_image_id', '', 'is_natural');
            $oFormValidation->set_rules('action', '', 'required');

            $oFormValidation->set_message('alpha_dash', lang('fv_alpha_dash'));
            $oFormValidation->set_message('is_natural', 'Please select a valid Parent Page.');
            $oFormValidation->set_message('max_length', 'Exceeds maximum length (%2$s characters)');

            if ($oFormValidation->run()) {

                $aPageData = [
                    'title'            => $oInput->post('title'),
                    'slug'             => $oInput->post('slug'),
                    'parent_id'        => (int) $oInput->post('parent_id') ?: null,
                    'template'         => $oInput->post('template'),
                    'template_data'    => $oInput->post('template_data'),
                    'template_options' => $oInput->post('template_options'),
                    'seo_title'        => $oInput->post('seo_title'),
                    'seo_description'  => $oInput->post('seo_description'),
                    'seo_keywords'     => $oInput->post('seo_keywords'),
                    'seo_image_id'     => (int) $oInput->post('seo_image_id') ?: null,
                ];

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

                        /** @var Session $oSession */
                        $oSession = Factory::service('Session');
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
        $this->data['templates']       = $this->oTemplateService->getAvailable('EDITOR');

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

        /** @var Asset $oAsset */
        $oAsset = Factory::service('Asset');
        $oAsset->load('admin.pages.edit.js', Constants::MODULE_SLUG);
        $oAsset->inline(implode("\n", [
            'var templates = [' . implode(',', $aTemplatesJson) . ']',
            'var pageEdit = new NAILS_Admin_CMS_Pages_CreateEdit( templates);',
        ]), 'JS');

        // --------------------------------------------------------------------------

        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Publish a CMS Page
     *
     * @return void
     * @throws FactoryException
     * @throws ModelException
     */
    public function publish()
    {
        if (!userHasPermission('admin:cms:pages:edit')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');
        /** @var Input $oInput */
        $oInput = Factory::service('Input');
        /** @var Session $oSession */
        $oSession = Factory::service('Session');

        $iId        = $oUri->segment(5);
        $bIsEditing = (bool) $oInput->get('editing');
        $sReturnTo  = $oInput->get('return_to');

        if ($this->oPageModel->publish($iId)) {

            $oPage = $this->oPageModel->getById($iId);
            $oSession->setFlashData(
                'success',
                'Page was published successfully - ' .
                anchor(
                    $oPage->published->url,
                    'View Page <b class="fa fa-external-link-alt"></b>',
                    'target="_blank"'
                )
            );

        } else {
            $oSession->setFlashData('error', 'Could not publish page. ' . $this->oPageModel->lastError());
        }

        if (!empty($sReturnTo)) {
            redirect($sReturnTo);
        } elseif ($bIsEditing) {
            redirect('admin/cms/pages/edit/' . $iId);
        } else {
            redirect('admin/cms/pages');
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Safely unpublishes a page
     *
     * @throws AssetException
     * @throws FactoryException
     * @throws ModelException
     */
    public function unpublish()
    {
        if (!userHasPermission('admin:cms:pages:edit')) {
            unauthorised();
        }

        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');
        /** @var Input $oInput */
        $oInput = Factory::service('Input');

        $iId   = $oUri->segment(5);
        $oPage = $this->oPageModel->getById($iId);
        if (empty($oPage) || !$oPage->is_published) {
            show404();
        }

        if ($oInput->post()) {

            /** @var Database $oDb */
            $oDb = Factory::service('Database');

            try {

                $oDb->trans_begin();

                //  Unpublish the parent page
                if (!$this->oPageModel->unpublish($oPage->id)) {
                    throw new NailsException('Failed to unpublish page. ' . $this->oPageModel->lastError());
                }

                $this
                    ->unpublishHandleChildren(
                        $oPage,
                        $oInput->post('child_behaviour')
                    )
                    ->unpublishDeleteHandleRedirects(
                        $oPage,
                        $oInput->post('redirect_behaviour'),
                        $oInput->post('redirect_url')
                    );

                $oDb->trans_commit();

                /** @var Session $oSession */
                $oSession = Factory::service('Session');
                $oSession->setFlashData('success', 'Page unpublished successfully');

                redirect($oInput->post('return_to') ?: 'admin/cms/pages');

            } catch (NailsException $e) {
                $oDb->trans_rollback();
                $this->data['error'] = $e->getMessage();
            }
        }

        $this->data['sReturnTo']   = $oInput->get('return_to') ?: $oInput->post('return_to');
        $this->data['oPage']       = $oPage;
        $this->data['aChildren']   = $this->oPageModel->getIdsOfChildren($oPage->id);
        $this->data['page']->title = 'Unpublish "' . $oPage->published->title . '"';
        $this->data['aOtherPages'] = $this->oPageModel->getAllFlat();

        unset($this->data['aOtherPages'][$oPage->id]);

        /** @var Asset $oAsset */
        $oAsset = Factory::service('Asset');
        //  @todo (Pablo - 2018-12-01) - Update/Remove/Use minified once JS is refactored to be a module
        $oAsset->load('admin.pages.unpublish.js', Constants::MODULE_SLUG);
        $oAsset->inline(
            'var pageUnpublish = new NAILS_Admin_CMS_Pages_Unpublish();',
            'JS'
        );

        Helper::loadView('unpublish');
    }

    // --------------------------------------------------------------------------

    /**
     * @param Resource $oPage      The page being unpublished
     * @param string   $sBehaviour The children behaviour
     *
     * @return $this
     * @throws ValidationException
     */
    private function unpublishHandleChildren(Resource $oPage, string $sBehaviour): Pages
    {
        $aChildren = $this->oPageModel->getIdsOfChildren($oPage->id);
        if (!empty($aChildren)) {
            switch ($sBehaviour) {
                case 'NONE':
                    break;

                case 'UNPUBLISH':
                    foreach ($aChildren as $iChildId) {
                        $this->oPageModel->unpublish($iChildId);
                    }
                    break;

                default:
                    throw new ValidationException('Invalid child behaviour value.');
                    break;
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * @param Resource $oPage      The page being unpublished or deleted
     * @param string   $sBehaviour The redirect behaviour
     * @param string   $sUrl       The URL to redirect to (if using URL based redirects)
     *
     * @return $this
     * @throws ValidationException
     * @throws FactoryException
     * @throws ModelException
     */
    protected function unpublishDeleteHandleRedirects(Resource $oPage, string $sBehaviour, string $sUrl): Pages
    {
        switch ($sBehaviour) {
            case 'NONE':
                $sUrl = null;
                break;

            case 'URL':
                $sUrl = prep_url($sUrl);
                break;

            default:
                if (!is_numeric($sBehaviour)) {
                    throw new ValidationException(
                        'Invalid redirect behaviour value.' . json_encode($_POST)
                    );
                }

                $oRedirectPage = $this->oPageModel->getById($sBehaviour);
                if (empty($oRedirectPage)) {
                    throw new ValidationException(
                        'Invalid redirect behaviour value. Page does not exist.'
                    );
                }

                if (!$oRedirectPage->is_published) {
                    throw new ValidationException(
                        'Invalid redirect behaviour value. Page is not published.'
                    );
                }

                $sUrl = $oRedirectPage->published->url;
                break;
        }

        if (!empty($sUrl)) {
            /** @var Redirect\Model\Redirect $oModel */
            $oModel = Factory::model('Redirect', Redirect\Constants::MODULE_SLUG);
            $oModel->create([
                'old_url' => $oPage->published->url,
                'new_url' => $sUrl,
            ]);
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Deletes a CMS page
     *
     * @throws AssetException
     * @throws FactoryException
     * @throws ModelException
     */
    public function delete()
    {
        if (!userHasPermission('admin:cms:pages:delete')) {
            unauthorised();
        }

        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');
        /** @var Input $oInput */
        $oInput = Factory::service('Input');

        $iId   = $oUri->segment(5);
        $oPage = $this->oPageModel->getById($iId);
        if (empty($oPage)) {
            show404();
        }
        $oPageData = $oPage->is_published ? $oPage->published : $oPage->draft;

        if ($oInput->post()) {

            /** @var Database $oDb */
            $oDb = Factory::service('Database');

            try {

                $oDb->trans_begin();

                //  Delete the parent page
                if (!$this->oPageModel->delete($oPage->id)) {
                    throw new NailsException('Failed to delete page. ' . $this->oPageModel->lastError());
                }

                $this
                    ->unpublishDeleteHandleRedirects(
                        $oPage,
                        $oInput->post('redirect_behaviour'),
                        $oInput->post('redirect_url')
                    );

                $oDb->trans_commit();

                /** @var Session $oSession */
                $oSession = Factory::service('Session');
                $oSession->setFlashData('success', 'Page deleted successfully');

                redirect($oInput->post('return_to') ?: 'admin/cms/pages');

            } catch (NailsException $e) {
                $oDb->trans_rollback();
                $this->data['error'] = $e->getMessage();
            }
        }

        $this->data['sReturnTo']   = $oInput->get('return_to') ?: $oInput->post('return_to');
        $this->data['oPage']       = $oPage;
        $this->data['oPageData']   = $oPageData;
        $this->data['aChildren']   = $this->oPageModel->getIdsOfChildren($oPage->id);
        $this->data['page']->title = 'Delete "' . $oPageData->title . '"';
        $this->data['aOtherPages'] = $this->oPageModel->getAllFlat();

        unset($this->data['aOtherPages'][$oPage->id]);

        /** @var Asset $oAsset */
        $oAsset = Factory::service('Asset');
        //  @todo (Pablo - 2018-12-01) - Update/Remove/Use minified once JS is refactored to be a module
        $oAsset->load('admin.pages.unpublish.js', Constants::MODULE_SLUG);
        $oAsset->inline(
            'var pageUnpublish = new NAILS_Admin_CMS_Pages_Unpublish();',
            'JS'
        );

        Helper::loadView('delete');
    }

    // --------------------------------------------------------------------------

    /**
     * Restore a CMS Page
     *
     * @return void
     * @throws FactoryException
     * @throws ModelException
     */
    public function restore()
    {
        if (!userHasPermission('admin:cms:pages:restore')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');
        /** @var Session $oSession */
        $oSession = Factory::service('Session');

        $iId   = $oUri->segment(5);
        $oPage = $this->oPageModel->getById($iId);

        if ($oPage && $oPage->is_deleted) {
            if ($this->oPageModel->restore($iId)) {
                $oSession->setFlashData('success', 'Page was restored successfully. ');
            } else {
                $oSession->setFlashData('error', 'Could not restore page. ' . $this->oPageModel->lastError());
            }
        } else {
            $oSession->setFlashData('error', 'Invalid page ID.');
        }

        redirect('admin/cms/pages');
    }

    // --------------------------------------------------------------------------

    /**
     * Duplicate a CMS page
     *
     * @throws FactoryException
     * @throws ModelException
     */
    public function copy()
    {
        if (!userHasPermission('admin:cms:pages:create')) {
            unauthorised();
        }

        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');
        /** @var Session $oSession */
        $oSession = Factory::service('Session');

        $iId   = $oUri->segment(5);
        $oPage = $this->oPageModel->getById($iId);
        if (empty($oPage)) {
            show404();
        }

        try {

            $iNewId = $this->oPageModel->copy($oPage->id);
            if (empty($iNewId)) {
                throw new \Exception($this->oPageModel->lastError());
            }

            $oSession->setFlashData('success', 'Page copied successfully.');
            redirect('admin/cms/pages/edit/' . $iNewId);

        } catch (\Exception $e) {
            $oSession->setFlashData('error', 'Failed to copy item. ' . $e->getMessage());
        }

        redirect('admin/cms/pages');
    }
}
