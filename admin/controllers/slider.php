<?php

/**
 * This class provides CMS Slider management functionality
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Cms;

class Slider extends \AdminController
{
    /**
     * Announces this controllers methods
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin.cms:0.can_manage_slider')) {

            $navGroup = new \Nails\Admin\Nav('CMS');
            $navGroup->addMethod('Manage Sliders');
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
        if (!userHasPermission('admin.accounts:0.can_manage_slider')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load common items
        $this->load->helper('cms');
        $this->load->model('cms/cms_slider_model');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse CMS Sliders
     * @return void
     */
    public function index()
    {
        //  Set method info
        $this->data['page']->title = 'Manage Sliders';

        // --------------------------------------------------------------------------

        //  Get pagination and search/sort variables
        $page      = $this->input->get('page')      ? $this->input->get('page')      : 0;
        $perPage   = $this->input->get('perPage')   ? $this->input->get('perPage')   : 50;
        $sortOn    = $this->input->get('sortOn')    ? $this->input->get('sortOn')    : 's.label';
        $sortOrder = $this->input->get('sortOrder') ? $this->input->get('sortOrder') : 'asc';
        $keywords  = $this->input->get('keywords')  ? $this->input->get('keywords')  : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns
        $sortColumns = array(
            's.label'    => 'Label',
            's.modified' => 'Modified Date'
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
        $totalRows             = $this->cms_slider_model->count_all($data);
        $this->data['sliders'] = $this->cms_slider_model->get_all($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = \Nails\Admin\Helper::searchObject($sortColumns, $sortOn, $sortOrder, $perPage, $keywords);
        $this->data['pagination'] = \Nails\Admin\Helper::paginationObject($page, $perPage, $totalRows);

        //  Add a header button
        if (userHasPermission('admin.cms:0.can_manage_slider')) {

             \Nails\Admin\Helper::addHeaderButton('admin/cms/slider/create', 'Add New Slider');
        }

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new CMS Slider
     * @return void
     */
    public function create()
    {
        if (!userHasPermission('admin.cms:0.can_create_slider')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post())
        {
            here();
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Create Slider';

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.cms.sliders.createEdit.min.js', true);
        $this->asset->inline('var _slider = new NAILS_Admin_CMS_Sliders_Create_Edit();', 'JS');

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a CMS Slider
     * @return void
     */
    public function edit()
    {
        if (!userHasPermission('admin.cms:0.can_edit_slider')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $slider = $this->cms_slider_model->get_by_id($this->uri->segment(5));
        $this->data['slider'] = $slider;

        if (!$slider) {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> invalid slider ID.');
            redirect('admin/cms/menus');
        }

        // --------------------------------------------------------------------------

        if ($this->input->post())
        {
            //  Prepare the create data
            $sliderData                = array();
            $sliderData['label']       = $this->input->post('label');
            $sliderData['description'] = strip_tags($this->input->post('description'));
            $sliderData['slides']      = array();

            $slideIds  = $this->input->post('slideId');
            $objectIds = $this->input->post('objectId');
            $captions  = $this->input->post('caption');
            $urls      = $this->input->post('url');

            for ($i=0; $i < count($slideIds); $i++) {

                $sliderData['slides'][$i] = array();

                $sliderData['slides'][$i]['id']        = !empty($slideIds[$i]) ? $slideIds[$i] : null;
                $sliderData['slides'][$i]['object_id'] = !empty($objectIds[$i]) ? $objectIds[$i] : null;
                $sliderData['slides'][$i]['caption']   = !empty($captions[$i]) ? $captions[$i] : null;
                $sliderData['slides'][$i]['url']       = !empty($urls[$i]) ? $urls[$i] : null;
                $sliderData['slides'][$i]['order']     = $i;
            }

            if ($this->cms_slider_model->update($slider->id, $sliderData)) {

                $status  = 'success';
                $message = '<strong>Success!</strong> Slider updated successfully.';

                $this->session->set_flashdata($status, $message);
                redirect('admin/cms/slider');

            } else {

                $this->data['error']  = '<strong>Sorry,</strong> failed to update slider';
                $this->data['error'] .= $this->cms_slider_model->last_error();

                //  @todo: Rebuild page based on post data
            }
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Edit Slider &rsaquo; ' . $slider->label;

        // --------------------------------------------------------------------------

        //  Prep the slides for the view
        foreach ($slider->slides as $slide) {

            $slide->imgSourceUrl = !empty($slide->object_id) ? cdn_serve($slide->object_id) : null;
            $slide->imgThumbUrl = !empty($slide->object_id) ? cdn_scale($slide->object_id, 130, 130) : null;
        }

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->load('jquery-ui/jquery-ui.min.js', 'NAILS-BOWER');
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.cms.sliders.createEdit.min.js', true);
        $this->asset->inline('var _slider = new NAILS_Admin_CMS_Sliders_Create_Edit();', 'JS');
        $this->asset->inline('_slider.addSlides(' . json_encode($slider->slides) . ');', 'JS');

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a CMS Slider
     * @return void
     */
    public function delete()
    {
        if (!userHasPermission('admin.cms:0.can_delete_slider')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Fetch and check post
        $sliderId = $this->uri->segment(5);
        $slider   = $this->cms_slider_model->get_by_id($sliderId);

        if (!$slider) {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> I could\'t find a slider by that ID.');
            redirect('admin/cms/slider');
        }

        // --------------------------------------------------------------------------

        if ($this->cms_slider_model->delete($slider->id)) {

            $status  = 'success';
            $message = '<strong>Success!</strong> Slider was deleted successfully.';

        } else {

            $status   = 'error';
            $message  = '<strong>Sorry,</strong> I failed to delete that slider. ';
            $message .= $this->cms_slider_model->last_error();
        }

        $this->session->set_flashdata($status, $message);
        redirect('admin/cms/slider');
    }
}
