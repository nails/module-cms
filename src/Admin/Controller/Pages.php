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

namespace Nails\Cms\Admin\Controller;

use Nails\Admin\Factory\Nav;
use Nails\Admin\Factory\Nav\Alert;
use Nails\Admin\Helper;
use Nails\Cms\Admin\Permission;
use Nails\Cms\Constants;
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
class Pages extends \Nails\Admin\Controller\Base
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
        if (userHasPermission(Permission\Page\Browse::class)) {

            //  Alerts
            /** @var Page $oModel */
            $oModel = Factory::model('Page', Constants::MODULE_SLUG);

            //  Draft pages
            /** @var Database $oDb */
            $oDb = Factory::service('Database');
            $oDb->where('is_published', false);
            $oDb->where($oModel->getColumnIsDeleted(), false);
            $iNumDrafts = $oDb->count_all_results($oModel->getTableName());

            if ($iNumDrafts) {
                /** @var Alert $oAlert */
                $oAlert = Factory::factory('NavAlert', \Nails\Admin\Constants::MODULE_SLUG);
                $oAlert->setValue($iNumDrafts);
                $oAlert->setSeverity('danger');
                $oAlert->setLabel('Draft Pages');
            }

            /** @var Nav $oNavGroup */
            $oNavGroup = Factory::factory('Nav', \Nails\Admin\Constants::MODULE_SLUG);
            $oNavGroup->setLabel('CMS');
            $oNavGroup->setIcon('fa-file-alt');
            $oNavGroup->addAction('Manage Pages', 'index', array_filter([$oAlert ?? null]));

            return $oNavGroup;
        }
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
        $this->oPageModel       = Factory::model('Page', Constants::MODULE_SLUG);
        $this->oWidgetService   = Factory::service('Widget', Constants::MODULE_SLUG);
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
        if (!userHasPermission(Permission\Page\Browse::class)) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->setTitles(['Manage Pages']);

        // --------------------------------------------------------------------------

        $sTableAlias = $this->oPageModel->getTableAlias();

        /** @var Input $oInput */
        $oInput     = Factory::service('Input');
        $iPage      = $oInput->get('page') ? $oInput->get('page') : 0;
        $iPerPage   = $oInput->get('perPage') ? $oInput->get('perPage') : 50;
        $sSortOn    = $oInput->get('sortOn') ? $oInput->get('sortOn') : $sTableAlias . '.draft_slug';
        $sSortOrder = $oInput->get('sortOrder') ? $oInput->get('sortOrder') : 'asc';
        $sKeywords  = $oInput->get('keywords') ? $oInput->get('keywords') : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns
        $aSortColumns = [
            $sTableAlias . '.draft_slug'  => 'Hierarchy',
            $sTableAlias . '.draft_title' => 'Label',
            $sTableAlias . '.modified'    => 'Modified',
        ];

        // --------------------------------------------------------------------------

        //  Define the $aData variable for the queries
        $aData = [
            //  All fields except body (might be very long)
            'select'   => $this->oPageModel->describeFieldsExcludingData(),
            'sort'     => [
                [$sSortOn, $sSortOrder],
            ],
            'keywords' => $sKeywords,
        ];

        //  Get the items for the page
        $totalRows           = $this->oPageModel->countAll($aData);
        $this->data['pages'] = $this->oPageModel->getAll($iPage, $iPerPage, $aData);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = Helper::searchObject(true, $aSortColumns, $sSortOn, $sSortOrder, $iPerPage, $sKeywords);
        $this->data['pagination'] = Helper::paginationObject($iPage, $iPerPage, $totalRows);
        $this->data['sReturnTo']  = urlencode($oInput->server('REQUEST_URI'));

        // --------------------------------------------------------------------------

        //  Add a header button
        if (userHasPermission(Permission\Page\Create::class)) {
            Helper::addHeaderButton(self::url('create'), 'Create');
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
        if (!userHasPermission(Permission\Page\Create::class)) {
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

                        redirect(self::url('publish/' . $oNewPageId . '?editing=1'));

                    } else {

                        $this->oUserFeedback->success('Page created successfully!');
                        redirect(self::url('edit/' . $oNewPageId));
                    }

                } else {
                    $this->oUserFeedback->error('Failed to create page. ' . $this->oPageModel->lastError());
                }

            } else {
                $this->oUserFeedback->error(lang('fv_there_were_errors'));
            }
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->setTitles(['Create Page']);

        //  Get data, available templates & widgets
        $this->data['pagesNestedFlat'] = $this->oPageModel->getAllNestedFlat(' &rsaquo; ', false);
        $this->data['templates']       = $this->oTemplateService->getAvailable();

        $this->oTemplateService->loadEditorAssets($this->data['templates']);

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
        if (!userHasPermission(Permission\Page\Edit::class)) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        /** @var Uri $oUri */
        $oUri  = Factory::service('Uri');
        $oPage = $this->oPageModel->getById($oUri->segment(5));

        if (!$oPage) {
            $this->oUserFeedback->error('No page found by that ID');
            redirect(self::url());
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

                        redirect(self::url('publish/' . $oPage->id . '?editing=1'));

                    } else {

                        $this->oUserFeedback->success('Page saved successfully!');

                        redirect(self::url('edit/' . $oPage->id));
                    }

                } else {
                    $this->oUserFeedback->error('Failed to update page. ' . $this->oPageModel->lastError());
                }

            } else {
                $this->oUserFeedback->error(lang('fv_there_were_errors'));
            }
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->setTitles(['Edit Page "' . $oPage->draft->title . '"']);

        //  Get data, available templates & widgets
        $this->data['cmspage']         = $oPage;
        $this->data['pagesNestedFlat'] = $this->oPageModel->getAllNestedFlat(' &rsaquo; ', false);
        $this->data['templates']       = $this->oTemplateService->getAvailable();

        $this->oTemplateService->loadEditorAssets($this->data['templates']);

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
        if (!userHasPermission(Permission\Page\Edit::class)) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');
        /** @var Input $oInput */
        $oInput = Factory::service('Input');

        $iId        = $oUri->segment(5);
        $bIsEditing = (bool) $oInput->get('editing');
        $sReturnTo  = $oInput->get('return_to');

        if ($this->oPageModel->publish($iId)) {

            $oPage = $this->oPageModel->getById($iId);
            $this->oUserFeedback->success(sprintf(
                'Page was published successfully - %s',
                anchor(
                    $oPage->published->url,
                    'View Page <b class="fa fa-external-link-alt"></b>',
                    'target="_blank"'
                )
            ));

        } else {
            $this->oUserFeedback->error('Could not publish page. ' . $this->oPageModel->lastError());
        }

        if (!empty($sReturnTo)) {
            redirect($sReturnTo);
        } elseif ($bIsEditing) {
            redirect(self::url('edit/' . $iId));
        } else {
            redirect(self::url());
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
        if (!userHasPermission(Permission\Page\Edit::class)) {
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

                $oDb->transaction()->start();

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

                $oDb->transaction()->commit();

                $this->oUserFeedback->success('Page unpublished successfully');

                redirect($oInput->post('return_to') ?: self::url());

            } catch (NailsException $e) {
                $oDb->transaction()->rollback();
                $this->oUserFeedback->error($e->getMessage());
            }
        }

        $this->data['sReturnTo']   = $oInput->get('return_to') ?: $oInput->post('return_to');
        $this->data['oPage']       = $oPage;
        $this->data['aChildren']   = $this->oPageModel->getIdsOfChildren($oPage->id);
        $this->data['aOtherPages'] = $this->oPageModel->getAllFlat();

        $this->setTitles(['Unpublish "' . $oPage->published->title . '"']);

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
        if (!userHasPermission(Permission\Page\Delete::class)) {
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

                $oDb->transaction()->start();

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

                $oDb->transaction()->commit();

                $this->oUserFeedback->success('Page deleted successfully');

                redirect($oInput->post('return_to') ?: self::url());

            } catch (NailsException $e) {
                $oDb->transaction()->rollback();
                $this->oUserFeedback->error($e->getMessage());
            }
        }

        $this->data['sReturnTo']   = $oInput->get('return_to') ?: $oInput->post('return_to');
        $this->data['oPage']       = $oPage;
        $this->data['oPageData']   = $oPageData;
        $this->data['aChildren']   = $this->oPageModel->getIdsOfChildren($oPage->id);
        $this->data['aOtherPages'] = $this->oPageModel->getAllFlat();

        $this->setTitles(['Delete "' . $oPageData->title . '"']);

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
        if (!userHasPermission(Permission\Page\Restore::class)) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');

        $iId   = $oUri->segment(5);
        $oPage = $this->oPageModel->getById($iId);

        if ($oPage && $oPage->is_deleted) {
            if ($this->oPageModel->restore($iId)) {
                $this->oUserFeedback->success('Page was restored successfully. ');
            } else {
                $this->oUserFeedback->error('Could not restore page. ' . $this->oPageModel->lastError());
            }
        } else {
            $this->oUserFeedback->error('Invalid page ID.');
        }

        redirect(self::url());
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
        if (!userHasPermission(Permission\Page\Create::class)) {
            unauthorised();
        }

        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');

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

            $this->oUserFeedback->success('Page copied successfully.');
            redirect(self::url('edit/' . $iNewId));

        } catch (\Exception $e) {
            $this->oUserFeedback->error('Failed to copy item. ' . $e->getMessage());
        }

        redirect(self::url());
    }
}
