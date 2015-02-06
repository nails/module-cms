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
     * Announces this controllers methods
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin.cms:0.can_manage_block')) {

            $navGroup = new \Nails\Admin\Nav('CMS');
            $navGroup->addMethod('Manage Blocks');
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
        if (!userHasPermission('admin.accounts:0.can_manage_block')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load common items
        $this->load->helper('cms');
        $this->load->model('cms/cms_block_model');

        // --------------------------------------------------------------------------

        //  Define block types; block types allow for proper validation
        $this->data['block_types']              = array();
        $this->data['block_types']['plaintext'] = 'Plain Text';
        $this->data['block_types']['richtext']  = 'Rich Text';

        // @TODO: Support these other types of block
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
            'sort'  => array(
                'column' => $sortOn,
                'order'  => $sortOrder
            ),
            'keywords' => $keywords
        );

        //  Get the items for the page
        $totalRows            = $this->cms_block_model->count_all($data);
        $this->data['blocks'] = $this->cms_block_model->get_all($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = \Nails\Admin\Helper::searchObject($sortColumns, $sortOn, $sortOrder, $perPage, $keywords);
        $this->data['pagination'] = \Nails\Admin\Helper::paginationObject($page, $perPage, $totalRows);

        //  Add a header button
        if (userHasPermission('admin.cms:0.can_create_block')) {

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
        if (!userHasPermission('admin.cms:0.can_edit_block')) {

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

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Block updated successfully.');
                    redirect('admin/cms/blocks');

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem updating the new block.';
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
        if (!userHasPermission('admin.cms:0.can_create_block')) {

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

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Block created successfully.');
                    redirect('admin/cms/blocks');

                } else {

                    $this->data['error'] = '<strong>Sorry,</strong> there was a problem creating the new block.';
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
        if (!userHasPermission('admin.cms:0.can_delete_block')) {

            unauthorised();
        }

        $this->session->set_flashdata('message', '<strong>Coming soon!</strong> The ability to delete CMS blocks is on the roadmap.');
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
