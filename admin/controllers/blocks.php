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

use Nails\Factory;
use Nails\Admin\Helper;
use Nails\Cms\Controller\BaseAdmin;

class Blocks extends BaseAdmin
{
    protected $oBlockModel;

    // --------------------------------------------------------------------------

    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin:cms:blocks:manage')) {

            $navGroup = new \Nails\Admin\Nav('CMS', 'fa-file-text');
            $navGroup->addAction('Manage Blocks');
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

        $permissions['manage']  = 'Can manage blocks';
        $permissions['create']  = 'Can create a new block';
        $permissions['edit']    = 'Can edit an existing block';
        $permissions['delete']  = 'Can delete an existing block';
        $permissions['restore'] = 'Can restore a deleted block';

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

        //  Load Model
        $this->oBlockModel = Factory::model('Block', 'nailsapp/module-cms');

        // --------------------------------------------------------------------------

        //  Define block types; block types allow for proper validation
        $this->data['blockTypes']              = array();
        $this->data['blockTypes']['plaintext'] = 'Plain Text';
        $this->data['blockTypes']['richtext']  = 'Rich Text';
        $this->data['blockTypes']['image']     = 'Image (*.jpg, *.png, *.gif)';
        $this->data['blockTypes']['file']      = 'File (*.*)';
        $this->data['blockTypes']['number']    = 'Number';
        $this->data['blockTypes']['url']       = 'URL';
        $this->data['blockTypes']['email']     = 'Email';
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

        $tablePrefix = $this->oBlockModel->getTablePrefix();

        // --------------------------------------------------------------------------

        //  Get pagination and search/sort variables
        $page      = $this->input->get('page')      ? $this->input->get('page')      : 0;
        $perPage   = $this->input->get('perPage')   ? $this->input->get('perPage')   : 50;
        $sortOn    = $this->input->get('sortOn')    ? $this->input->get('sortOn')    : $tablePrefix . '.label';
        $sortOrder = $this->input->get('sortOrder') ? $this->input->get('sortOrder') : 'desc';
        $keywords  = $this->input->get('keywords')  ? $this->input->get('keywords')  : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns
        $sortColumns = array(
            $tablePrefix . '.label'    => 'Label',
            $tablePrefix . '.located'  => 'Location',
            $tablePrefix . '.type'     => 'Type',
            $tablePrefix . '.created'  => 'Created',
            $tablePrefix . '.modified' => 'Modified'
        );

        // --------------------------------------------------------------------------

        //  Checkbox filters
        $cbFilters   = array();
        $cbFilters[] = Helper::searchFilterObject(
            $tablePrefix . '.type',
            'Type',
            array()
        );

        foreach ($this->data['blockTypes'] as $slug => $label) {

            $cbFilters[0]->options[] = Helper::searchFilterObjectOption($label, $slug, true);
        }

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = array(
            'sort' => array(
                array($sortOn, $sortOrder)
            ),
            'keywords' => $keywords,
            'cbFilters' => $cbFilters
        );

        //  Get the items for the page
        $totalRows            = $this->oBlockModel->count_all($data);
        $this->data['blocks'] = $this->oBlockModel->get_all($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = Helper::searchObject(true, $sortColumns, $sortOn, $sortOrder, $perPage, $keywords, $cbFilters);
        $this->data['pagination'] = Helper::paginationObject($page, $perPage, $totalRows);

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

        $this->data['block'] = $this->oBlockModel->get_by_id($this->uri->segment(5));

        if (!$this->data['block']) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            //  Form Validation
            $this->load->library('form_validation');

            switch ($this->data['block']->type) {
                case 'email':
                    $this->form_validation->set_rules('value', '', 'valid_email|xss_clean');
                    break;

                case 'url':
                    $this->form_validation->set_rules('value', '', 'valid_url|xss_clean');
                    break;

                case 'file':
                case 'image':
                case 'number':
                    $this->form_validation->set_rules('value', '', 'numeric|xss_clean');
                    break;

                default:
                    $this->form_validation->set_rules('value', '', 'xss_clean');
                    break;
            }

            $this->form_validation->set_message('required', lang('fv_required'));

            if ($this->form_validation->run($this)) {

                $aBlockData          = array();
                $aBlockData['value'] = $this->input->post('value');

                if ($this->oBlockModel->update($this->data['block']->id, $aBlockData)) {

                    $this->session->set_flashdata('success', 'Block updated successfully.');
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
        $this->data['languages']    = $this->language_model->getAllEnabledFlat();
        $this->data['default_code'] = $this->language_model->getDefaultCode();

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

        if ($this->input->post()) {

            //  Form Validation
            $this->load->library('form_validation');

            $this->form_validation->set_rules('slug', '', 'xss_clean|required|callback__callback_block_slug');
            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('description', '', 'xss_clean');
            $this->form_validation->set_rules('located', '', 'xss_clean');
            $this->form_validation->set_rules('type', '', 'xss_clean|required|callback__callback_block_type');

            switch ($this->input->post('type')) {
                case 'email':
                    $this->form_validation->set_rules('value', '', 'valid_email|xss_clean');
                    break;

                case 'url':
                    $this->form_validation->set_rules('value', '', 'valid_url|xss_clean');
                    break;

                case 'file':
                case 'image':
                case 'number':
                    $this->form_validation->set_rules('value', '', 'numeric|xss_clean');
                    break;

                default:
                    $this->form_validation->set_rules('value', '', 'xss_clean');
                    break;
            }

            $this->form_validation->set_message('required', lang('fv_required'));

            if ($this->form_validation->run($this)) {

                $aBlockData                = array();
                $aBlockData['type']        = $this->input->post('type');
                $aBlockData['slug']        = $this->input->post('slug');
                $aBlockData['label']       = $this->input->post('label');
                $aBlockData['description'] = $this->input->post('description');
                $aBlockData['located']     = $this->input->post('located');
                $aBlockData['value']       = $this->input->post('value_' . $aBlockData['type']);

                if ($this->oBlockModel->create($aBlockData)) {

                    $this->session->set_flashdata('success', 'Block created successfully.');
                    redirect('admin/cms/blocks');

                } else {

                    $this->data['error']  = 'There was a problem creating the new block. ';
                    $this->data['error'] .= $this->oBlockModel->last_error();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Create Block';

        // --------------------------------------------------------------------------

        $this->asset->load('nails.admin.cms.blocks.create.min.js', true);

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

        $block = $this->oBlockModel->get_by_id($this->uri->segment(5));

        if (!$block) {

            $this->session->set_flashdata('error', 'Invalid block ID.');
            redirect('admin/cms/blocks');
        }

        // --------------------------------------------------------------------------

        if ($this->oBlockModel->delete($block->id)) {

            $status = 'success';
            $msg    = 'Block was deleted successfully.';

        } else {

            $status = 'error';
            $msg    = 'Failed to delete block. ';
            $msg   .= $this->oBlockModel->last_error();
        }

        $this->session->set_flashdata($status, $msg);
        redirect('admin/cms/blocks');
    }

    // --------------------------------------------------------------------------

    /**
     * Form validation callback: Validates a block's slug
     * @param  string &$sSlug The slug to validate/sanitise
     * @return boolean
     */
    public function _callback_block_slug(&$sSlug)
    {
        $sSlug = trim($sSlug);
        $sSlug = strtolower($sSlug);

        //  Check slug's characters are ok
        if (!preg_match('/[^a-z0-9\-\_]/', $sSlug)) {

            $oBlock = $this->oBlockModel->get_by_slug($sSlug);

            if (!$oBlock) {

                //  OK!
                $bResult = true;

            } else {

                $this->form_validation->set_message(
                    '_callback_block_slug',
                    'Must be unique'
                );
                $bResult = false;
            }

        } else {

            $this->form_validation->set_message(
                '_callback_block_slug',
                'Invalid characters: a-z, 0-9, - and _ only, no spaces.'
            );
            $bResult = false;
        }
        return $bResult;
    }

    // --------------------------------------------------------------------------

    /**
     * Form Validation Callback: Validates a block's type
     * @param  string $type The type to validate
     * @return boolean
     */
    public function _callback_block_type($type)
    {
        $type = trim($type);

        if ($type) {

            if (isset($this->data['blockTypes'][$type])) {

                return true;

            } else {

                $this->form_validation->set_message('_callback_block_type', 'Block type not supported.');
                return false;
            }

        } else {

            $this->form_validation->set_message('_callback_block_type', lang('fv_required'));
            return false;
        }
    }
}
