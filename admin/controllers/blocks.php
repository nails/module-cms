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
        if (user_has_permission('admin.cms:0.can_manage_block')) {

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
        if (!user_has_permission('admin.accounts:0.can_manage_block')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load common items
        $this->load->helper('cms');
        $this->load->model('cms/cms_block_model');
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.cms.blocks.min.js', true);

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

        $this->data['blocks']    = $this->cms_block_model->get_all();
        $this->data['languages'] = $this->language_model->get_all_enabled_flat();

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
        if (!user_has_permission('admin.cms:0.can_edit_block')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['block'] = $this->cms_block_model->get_by_id($this->uri->segment(5), true);

        if (!$this->data['block']) {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> no block found by that ID');
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            //  Loop through and update translations, keep track of translations which have been updated
            $updated = array();

            if ($this->input->post('translation')) {

                foreach ($this->input->post('translation') as $translation) {

                    $this->cms_block_model->update_translation($this->data['block']->id, $translation['language'], $translation['value']);
                    $updated[] = $translation['language'];
                }
            }

            //  Delete translations that weren't updated (they have been removed)
            if ($updated) {

                $this->db->where('block_id', $this->data['block']->id);
                $this->db->where_not_in('language', $updated);
                $this->db->delete(NAILS_DB_PREFIX . 'cms_block_translation');
            }

            //  Loop through and add new translations
            if ($this->input->post('new_translation')) {

                foreach ($this->input->post('new_translation') as $translation) {

                    $this->cms_block_model->create_translation($this->data['block']->id, $translation['language'], $translation['value']);
                }
            }

            // --------------------------------------------------------------------------

            //  Send the user on their merry way
            $this->session->set_flashdata('success', '<strong>Success!</strong> The block was updated successfully!');
            redirect('admin/cms/blocks');
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Edit Block "' . $this->data['block']->title . '"';

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['languages']    = $this->language_model->get_all_enabled_flat();
        $this->data['default_code'] = $this->language_model->get_default_code();

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
        if (!user_has_permission('admin.cms:0.can_create_block')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            //  Form Validation
            $this->load->library('form_validation');

            $this->form_validation->set_rules('slug', '', 'xss_clean|required|callback__callback_block_slug');
            $this->form_validation->set_rules('title', '', 'xss_clean|required');
            $this->form_validation->set_rules('description', '', 'xss_clean');
            $this->form_validation->set_rules('located', '', 'xss_clean');
            $this->form_validation->set_rules('type', '', 'xss_clean|required|callback__callback_block_type');
            $this->form_validation->set_rules('value', '', 'xss_clean');

            $this->form_validation->set_message('required', lang('fv_required'));

            if ($this->form_validation->run($this)) {

                $type  = $this->input->post('type');
                $slug  = $this->input->post('slug');
                $title = $this->input->post('title');
                $desc  = $this->input->post('description');
                $loc   = $this->input->post('located');
                $val   = $this->input->post('value');

                if ($this->cms_block_model->create_block($type, $slug, $title, $desc, $loc, $val)) {

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

        $this->data['languages'] = $this->language_model->get_all_enabled_flat();

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('create');
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
