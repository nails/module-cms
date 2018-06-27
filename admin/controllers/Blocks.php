<?php

/**
 * This class provides CMS Block management functionality
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Cms;

use Nails\Admin\Helper;
use Nails\Cms\Controller\BaseAdmin;
use Nails\Factory;

class Blocks extends BaseAdmin
{
    protected $oBlockModel;

    // --------------------------------------------------------------------------

    /**
     * Announces this controller's navGroups
     * @return \Nails\Admin\Nav
     */
    public static function announce()
    {
        if (userHasPermission('admin:cms:blocks:manage')) {

            $oNavGroup = Factory::factory('Nav', 'nailsapp/module-admin');
            $oNavGroup->setLabel('CMS');
            $oNavGroup->setIcon('fa-file-text');
            $oNavGroup->addAction('Manage Blocks');

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

        $aPermissions['manage']  = 'Can manage blocks';
        $aPermissions['create']  = 'Can create a new block';
        $aPermissions['edit']    = 'Can edit an existing block';
        $aPermissions['delete']  = 'Can delete an existing block';
        $aPermissions['restore'] = 'Can restore a deleted block';

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

        //  Define block types; block types allow for proper validation
        $this->data['blockTypes'] = [
            'plaintext' => 'Plain Text',
            'richtext'  => 'Rich Text',
            'image'     => 'Image (*.jpg, *.png, *.gif)',
            'file'      => 'File (*.*)',
            'number'    => 'Number',
            'url'       => 'URL',
            'email'     => 'Email',
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * Browse CMS Blocks
     * @return void
     */
    public function index()
    {
        if (!userHasPermission('admin:cms:blocks:manage')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Manage Blocks';

        // --------------------------------------------------------------------------

        $oModel      = Factory::model('Block', 'nailsapp/module-cms');
        $oInput      = Factory::service('Input');
        $sTableAlias = $oModel->getTableAlias();

        // --------------------------------------------------------------------------

        //  Get pagination and search/sort variables
        $iPage      = $oInput->get('page') ? $oInput->get('page') : 0;
        $iPerPage   = $oInput->get('perPage') ? $oInput->get('perPage') : 50;
        $sSortOn    = $oInput->get('sortOn') ? $oInput->get('sortOn') : $sTableAlias . '.label';
        $sSortOrder = $oInput->get('sortOrder') ? $oInput->get('sortOrder') : 'desc';
        $sKeywords  = $oInput->get('keywords') ? $oInput->get('keywords') : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns
        $sortColumns = [
            $sTableAlias . '.label'    => 'Label',
            $sTableAlias . '.located'  => 'Location',
            $sTableAlias . '.type'     => 'Type',
            $sTableAlias . '.created'  => 'Created',
            $sTableAlias . '.modified' => 'Modified',
        ];

        // --------------------------------------------------------------------------

        //  Checkbox filters
        $aCbFilters = [
            Helper::searchFilterObject(
                $sTableAlias . '.type',
                'Type',
                []
            ),
        ];

        foreach ($this->data['blockTypes'] as $sSlug => $sLabel) {
            $aCbFilters[0]->addOption($sLabel, $sSlug, true);
        }

        // --------------------------------------------------------------------------

        //  Define the $aData variable for the queries
        $aData = [
            'sort'      => [
                [$sSortOn, $sSortOrder],
            ],
            'keywords'  => $sKeywords,
            'cbFilters' => $aCbFilters,
        ];

        //  Get the items for the page
        $iTotalRows           = $oModel->countAll($aData);
        $this->data['blocks'] = $oModel->getAll($iPage, $iPerPage, $aData);

        //  Set Search and Pagination objects for the view
        $this->data['pagination'] = Helper::paginationObject($iPage, $iPerPage, $iTotalRows);
        $this->data['search']     = Helper::searchObject(
            true,
            $sortColumns,
            $sSortOn,
            $sSortOrder,
            $iPerPage,
            $sKeywords,
            $aCbFilters
        );

        //  Add a header button
        if (userHasPermission('admin:cms:blocks:create')) {
            Helper::addHeaderButton('admin/cms/blocks/create', 'Create Block');
        }

        // --------------------------------------------------------------------------

        Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a CMS Block
     * @return void
     */
    public function edit()
    {
        if (!userHasPermission('admin:cms:blocks:edit')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        $oModel = Factory::model('Block', 'nailsapp/module-cms');
        $oInput = Factory::service('Input');
        $oUri   = Factory::service('Uri');

        $this->data['block'] = $oModel->getById($oUri->segment(5));

        if (!$this->data['block']) {
            show_404();
        }

        // --------------------------------------------------------------------------

        if ($oInput->post()) {

            //  Form Validation
            $oFormValidation = Factory::service('FormValidation');

            switch ($this->data['block']->type) {
                case 'email':
                    $oFormValidation->set_rules('value', '', 'trim|valid_email');
                    break;

                case 'url':
                    $oFormValidation->set_rules('value', '', 'trim|valid_url');
                    break;

                case 'file':
                case 'image':
                case 'number':
                    $oFormValidation->set_rules('value', '', 'numeric');
                    break;

                default:
                    $oFormValidation->set_rules('value', '', 'trim');
                    break;
            }

            $oFormValidation->set_message('required', lang('fv_required'));

            if ($oFormValidation->run()) {

                if ($oModel->update($this->data['block']->id, ['value' => $oInput->post('value')])) {

                    $oSession = Factory::service('Session', 'nailsapp/module-auth');
                    $oSession->setFlashData('success', 'Block updated successfully.');
                    redirect('admin/cms/blocks');

                } else {
                    $this->data['error'] = 'There was a problem updating the new block.';
                }

            } else {
                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Edit Block &rsaquo; ' . $this->data['block']->label;

        // --------------------------------------------------------------------------

        //  Fetch data
        $oLanguageModel = Factory::model('Language');

        $this->data['languages']    = $oLanguageModel->getAllEnabledFlat();
        $this->data['default_code'] = $oLanguageModel->getDefaultCode();

        // --------------------------------------------------------------------------

        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new CMS Block
     * @return void
     */
    public function create()
    {
        if (!userHasPermission('admin:cms:blocks:create')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        $oInput = Factory::service('Input');

        if ($oInput->post()) {

            //  Form Validation
            $oFormValidation = Factory::service('FormValidation');

            $oFormValidation->set_rules('slug', '', 'required|callback_callbackBlockSlug');
            $oFormValidation->set_rules('label', '', 'required');
            $oFormValidation->set_rules('description', '', '');
            $oFormValidation->set_rules('located', '', '');
            $oFormValidation->set_rules('type', '', 'required|callback_callbackBlockType');

            switch ($oInput->post('type')) {
                case 'email':
                    $oFormValidation->set_rules('value', '', 'valid_email');
                    break;

                case 'url':
                    $oFormValidation->set_rules('value', '', 'valid_url');
                    break;

                case 'file':
                case 'image':
                case 'number':
                    $oFormValidation->set_rules('value', '', 'numeric');
                    break;

                default:
                    $oFormValidation->set_rules('value', '', '');
                    break;
            }

            $oFormValidation->set_message('required', lang('fv_required'));

            if ($oFormValidation->run($this)) {

                $aBlockData = [
                    'type'        => $oInput->post('type'),
                    'slug'        => $oInput->post('slug'),
                    'label'       => $oInput->post('label'),
                    'description' => $oInput->post('description'),
                    'located'     => $oInput->post('located'),
                    'value'       => $oInput->post('value_' . $oInput->post('type')),
                ];

                $oModel = Factory::model('Block', 'nailsapp/module-cms');
                if ($oModel->create($aBlockData)) {

                    $oSession = Factory::service('Session', 'nailsapp/module-auth');
                    $oSession->setFlashData('success', 'Block created successfully.');
                    redirect('admin/cms/blocks');

                } else {
                    $this->data['error'] = 'There was a problem creating the new block. ';
                    $this->data['error'] .= $oModel->lastError();
                }

            } else {
                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Create Block';

        // --------------------------------------------------------------------------

        $oAsset = Factory::service('Asset');
        $oAsset->load('admin.blocks.create.min.js', 'nailsapp/module-cms');

        // --------------------------------------------------------------------------

        Helper::loadView('create');
    }

    // --------------------------------------------------------------------------

    public function delete()
    {
        if (!userHasPermission('admin:cms:blocks:delete')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        $oModel   = Factory::model('Block', 'nailsapp/module-cms');
        $oUri     = Factory::service('Uri');
        $oSession = Factory::service('Session', 'nailsapp/module-auth');

        $oBlock = $oModel->getById($oUri->segment(5));

        if (!$oBlock) {
            $oSession->setFlashData('error', 'Invalid block ID.');
            redirect('admin/cms/blocks');
        }

        // --------------------------------------------------------------------------

        if ($oModel->delete($oBlock->id)) {
            $sStatus = 'success';
            $sMsg    = 'Block was deleted successfully.';
        } else {
            $sStatus = 'error';
            $sMsg    = 'Failed to delete block. ';
            $sMsg    .= $oModel->lastError();
        }

        $oSession->setFlashData($sStatus, $sMsg);
        redirect('admin/cms/blocks');
    }

    // --------------------------------------------------------------------------

    /**
     * Form validation callback: Validates a block's slug
     *
     * @param  string &$sSlug The slug to validate/sanitise
     *
     * @return boolean
     */
    public function callbackBlockSlug(&$sSlug)
    {
        $sSlug = trim($sSlug);
        $sSlug = strtolower($sSlug);

        $oFormValidation = Factory::service('FormValidation');
        $oModel          = Factory::model('Block', 'nailsapp/module-cms');

        //  Check slug's characters are ok
        if (!preg_match('/[^a-z0-9\-\_]/', $sSlug)) {

            $oBlock = $oModel->getBySlug($sSlug);

            if (!$oBlock) {
                $bResult = true;
            } else {
                $oFormValidation->set_message(
                    'callbackBlockSlug',
                    'Must be unique'
                );
                $bResult = false;
            }

        } else {
            $oFormValidation->set_message(
                'callbackBlockSlug',
                'Invalid characters: a-z, 0-9, - and _ only, no spaces.'
            );
            $bResult = false;
        }
        return $bResult;
    }

    // --------------------------------------------------------------------------

    /**
     * Form Validation Callback: Validates a block's type
     *
     * @param  string $sType The type to validate
     *
     * @return boolean
     */
    public function callbackBlockType($sType)
    {
        $sType           = trim($sType);
        $oFormValidation = Factory::service('FormValidation');

        if ($sType) {

            if (isset($this->data['blockTypes'][$sType])) {
                return true;
            } else {
                $oFormValidation->set_message('callbackBlockType', 'Block type not supported.');
                return false;
            }

        } else {
            $oFormValidation->set_message('callbackBlockType', lang('fv_required'));
            return false;
        }
    }
}
