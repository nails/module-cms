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

class Blocks extends \AdminController
{
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

        //  Load common items
        $this->load->helper('cms');
        $this->load->model('cms/cms_block_model');

        // --------------------------------------------------------------------------

        //  Define block types; block types allow for proper validation
        $this->data['block_types']              = array();
        $this->data['block_types']['plaintext'] = 'Plain Text';
        $this->data['block_types']['richtext']  = 'Rich Text';

        // @todo: Support these other types of block
        //$this->data['block_types']['image']     = 'Image (*.jpg, *.png, *.gif)';
        //$this->data['block_types']['file']      = 'File (*.*)';
        //$this->data['block_types']['number']    = 'Number';
        //$this->data['block_types']['url']       = 'URL';
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

        //  Get pagination and search/sort variables
        $page      = $this->input->get('page')      ? $this->input->get('page')      : 0;
        $perPage   = $this->input->get('perPage')   ? $this->input->get('perPage')   : 50;
        $sortOn    = $this->input->get('sortOn')    ? $this->input->get('sortOn')    : 'b.label';
        $sortOrder = $this->input->get('sortOrder') ? $this->input->get('sortOrder') : 'desc';
        $keywords  = $this->input->get('keywords')  ? $this->input->get('keywords')  : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns
        $sortColumns = array(
            'b.label'    => 'Label',
            'b.located'  => 'Location',
            'b.type'     => 'Type',
            'b.created'  => 'Created',
            'b.modified' => 'Modified'
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
        $totalRows            = $this->cms_block_model->count_all($data);
        $this->data['blocks'] = $this->cms_block_model->get_all($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = \Nails\Admin\Helper::searchObject(true, $sortColumns, $sortOn, $sortOrder, $perPage, $keywords);
        $this->data['pagination'] = \Nails\Admin\Helper::paginationObject($page, $perPage, $totalRows);

        //  Add a header button
        if (userHasPermission('admin:cms:blocks:create')) {

            \Nails\Admin\Helper::addHeaderButton('admin/cms/blocks/create', 'Create Block');
        }

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('index');
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

        $this->data['block'] = $this->cms_block_model->get_by_id($this->uri->segment(5), true);

        if (!$this->data['block']) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            //  Form Validation
            $this->load->library('form_validation');
            $this->form_validation->set_rules('value', '', 'xss_clean');
            $this->form_validation->set_message('required', lang('fv_required'));

            if ($this->form_validation->run($this)) {

                $blockData          = array();
                $blockData['value'] = $this->input->post('value');

                if ($this->cms_block_model->update($this->data['block']->id, $blockData)) {

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

        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.cms.blocks.edit.min.js', true);

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('edit');
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
            $this->form_validation->set_rules('value', '', 'xss_clean');

            $this->form_validation->set_message('required', lang('fv_required'));

            if ($this->form_validation->run($this)) {

                $blockData                = array();
                $blockData['type']        = $this->input->post('type');
                $blockData['slug']        = $this->input->post('slug');
                $blockData['label']       = $this->input->post('label');
                $blockData['description'] = $this->input->post('description');
                $blockData['located']     = $this->input->post('located');
                $blockData['value']       = $this->input->post('value');

                if ($this->cms_block_model->create($blockData)) {

                    $this->session->set_flashdata('success', 'Block created successfully.');
                    redirect('admin/cms/blocks');

                } else {

                    $this->data['error']  = 'There was a problem creating the new block. ';
                    $this->data['error'] .= $this->cms_block_model->last_error();
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

        \Nails\Admin\Helper::loadView('create');
    }

    // --------------------------------------------------------------------------

    public function delete()
    {
        if (!userHasPermission('admin:cms:blocks:delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $block = $this->cms_block_model->get_by_id($this->uri->segment(5));

        if (!$block) {

            $this->session->set_flashdata('error', 'Invalid block ID.');
            redirect('admin/cms/blocks');
        }

        // --------------------------------------------------------------------------

        if ($this->cms_block_model->delete($block->id)) {

            $status = 'success';
            $msg    = 'Block was deleted successfully.';

        } else {

            $status = 'error';
            $msg    = 'Failed to delete block. ';
            $msg   .= $this->cms_block_model->last_error();
        }

        $this->session->set_flashdata($status, $msg);
        redirect('admin/cms/blocks');
    }

    // --------------------------------------------------------------------------

    /**
     * Form Validation Callback: Validates a block's slug
     * @param  string $slug The slug to validate
     * @return boolean
     */
    public function _callback_block_slug($slug)
    {
        $slug = trim($slug);

        //  Check slug's characters are ok
        if (!preg_match('/[^a-zA-Z0-9\-\_]/', $slug)) {

            $block = $this->cms_block_model->get_by_slug($slug);

            if (!$block) {

                //  OK!
                return true;

            } else {

                $this->form_validation->set_message('_callback_block_slug', 'Must be unique');
                return false;
            }

        } else {

            $this->form_validation->set_message('_callback_block_slug', 'Invalid characters');
            return false;
        }
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

            if (isset($this->data['block_types'][$type])) {

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
