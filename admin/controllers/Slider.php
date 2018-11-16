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

use Nails\Factory;
use Nails\Functions;
use Nails\Admin\Helper;
use Nails\Cms\Controller\BaseAdmin;

class Slider extends BaseAdmin
{
    protected $oSliderModel;

    // --------------------------------------------------------------------------

    /**
     * Announces this controller's navGroups
     * @return \Nails\Admin\Nav
     */
    public static function announce()
    {
        if (userHasPermission('admin:cms:slider:manage')) {
            $oNavGroup = Factory::factory('Nav', 'nails/module-admin');
            $oNavGroup->setLabel('CMS');
            $oNavGroup->setIcon('fa-file-text');
            $oNavGroup->addAction('Manage Sliders');
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

        $aPermissions['manage']  = 'Can manage sliders';
        $aPermissions['create']  = 'Can create a new slider';
        $aPermissions['edit']    = 'Can edit an existing slider';
        $aPermissions['delete']  = 'Can delete an existing slider';
        $aPermissions['restore'] = 'Can restore a deleted slider';

        return $aPermissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct()
    {
        parent::__construct();
        $this->oSliderModel = Factory::model('Slider', 'nails/module-cms');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse CMS Sliders
     * @return void
     */
    public function index()
    {
        if (!userHasPermission('admin:cms:slider:manage')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Manage Sliders';

        // --------------------------------------------------------------------------

        //  Get pagination and search/sort variables
        $oInput     = Factory::service('Input');
        $iPage      = (int) $oInput->get('page') ?: 0;
        $iPerPage   = (int) $oInput->get('perPage') ?: 50;
        $sSortOn    = $oInput->get('sortOn') ?: 's.label';
        $sSortOrder = $oInput->get('sortOrder') ?: 'asc';
        $sKeywords  = $oInput->get('keywords') ?: '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns
        $aSortColumns = [
            's.label'    => 'Label',
            's.modified' => 'Modified Date',
        ];

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = [
            'sort'     => [
                [$sSortOn, $sSortOrder],
            ],
            'keywords' => $sKeywords,
        ];

        //  Get the items for the page
        $iTotalRows            = $this->oSliderModel->countAll($data);
        $this->data['sliders'] = $this->oSliderModel->getAll($iPage, $iPerPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = Helper::searchObject(true, $aSortColumns, $sSortOn, $sSortOrder, $iPerPage, $sKeywords);
        $this->data['pagination'] = Helper::paginationObject($iPage, $iPerPage, $iTotalRows);

        //  Add a header button
        if (userHasPermission('admin:cms:slider:create')) {
            Helper::addHeaderButton('admin/cms/slider/create', 'Add New Slider');
        }

        // --------------------------------------------------------------------------

        Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new CMS Slider
     * @return void
     */
    public function create()
    {
        if (!userHasPermission('admin:cms:slider:create')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        $oInput = Factory::service('Input');
        if ($oInput->post()) {

            //  Rebuild sliders
            $aSlideIds  = $oInput->post('slideId') ?: [];
            $aObjectIds = $oInput->post('objectId') ?: [];
            $aCaptions  = $oInput->post('caption') ?: [];
            $aUrls      = $oInput->post('url') ?: [];

            $aSlides = [];
            for ($i = 0; $i < count($aSlideIds); $i++) {
                $aSlides[] = (object) [
                    'id'        => !empty($aSlideIds[$i]) ? $aSlideIds[$i] : null,
                    'object_id' => !empty($aObjectIds[$i]) ? $aObjectIds[$i] : null,
                    'caption'   => !empty($aCaptions[$i]) ? $aCaptions[$i] : null,
                    'url'       => !empty($aUrls[$i]) ? $aUrls[$i] : null,
                ];
            }

            // --------------------------------------------------------------------------

            //  Validate form
            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('label', '', 'trim|required');
            $oFormValidation->set_rules('description', '', 'trim');
            $oFormValidation->set_message('required', lang('fv_required'));

            if ($oFormValidation->run()) {

                $aSliderData = [
                    'label'       => $oInput->post('label'),
                    'description' => strip_tags($oInput->post('description')),
                    'slides'      => $aSlides,
                ];

                if ($this->oSliderModel->create($aSliderData)) {

                    $oSession = Factory::service('Session', 'nails/module-auth');
                    $oSession->setFlashData('success', 'Slider created successfully.');
                    redirect('admin/cms/slider');

                } else {
                    $this->data['error'] = 'Failed to create slider. ';
                    $this->data['error'] .= $this->oSliderModel->lastError();
                }

            } else {
                $this->data['error'] = lang('fv_there_were_errors');
            }

        } else {
            $aSlides = [];
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Create Slider';

        // --------------------------------------------------------------------------

        //  Prep the slides for the view
        foreach ($aSlides as $oSlide) {
            $oSlide->imgSourceUrl = !empty($oSlide->object_id) ? cdnServe($oSlide->object_id) : null;
            $oSlide->imgThumbUrl  = !empty($oSlide->object_id) ? cdnScale($oSlide->object_id, 130, 130) : null;
        }

        // --------------------------------------------------------------------------

        //  Define the manager URL
        $sCdnManagerUrl = cdnManagerUrl(
            'cms-slider',
            ['sliderEdit', 'setImgCallback'],
            null,
            Functions::isPageSecure()
        );

        // --------------------------------------------------------------------------

        //  Assets
        $oCdn   = Factory::service('Cdn', 'nails/module-cdn');
        $oAsset = Factory::service('Asset');
        $oAsset->load('jquery-ui/jquery-ui.min.js', 'NAILS-BOWER');
        $oAsset->library('MUSTACHE');
        $oAsset->load('admin.sliders.edit.min.js', 'nails/module-cms');
        $oAsset->inline('var sliderEdit = new NAILS_Admin_CMS_Sliders_Create_Edit();', 'JS');
        $oAsset->inline('sliderEdit.setScheme("serve", "' . $oCdn->urlServeScheme() . '");', 'JS');
        $oAsset->inline('sliderEdit.setScheme("thumb", "' . $oCdn->urlCropScheme() . '");', 'JS');
        $oAsset->inline('sliderEdit.setManagerUrl("' . $sCdnManagerUrl . '");', 'JS');
        $oAsset->inline('sliderEdit.addSlides(' . json_encode($aSlides) . ');', 'JS');

        // --------------------------------------------------------------------------

        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a CMS Slider
     * @return void
     */
    public function edit()
    {
        if (!userHasPermission('admin:cms:slider:edit')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        $oUri                 = Factory::service('Uri');
        $oSlide               = $this->oSliderModel->getById($oUri->segment(5));
        $this->data['slider'] = $oSlide;

        if (!$oSlide) {
            $oSession = Factory::service('Session', 'nails/module-auth');
            $oSession->setFlashData('error', 'Invalid slider ID.');
            redirect('admin/cms/slider');
        }

        // --------------------------------------------------------------------------

        $oInput = Factory::service('Input');
        if ($oInput->post()) {

            //  Rebuild sliders
            $aSlideIds  = $oInput->post('slideId') ?: [];
            $aObjectIds = $oInput->post('objectId') ?: [];
            $aCaptions  = $oInput->post('caption') ?: [];
            $aUrls      = $oInput->post('url') ?: [];

            $aSlides = [];
            for ($i = 0; $i < count($aSlideIds); $i++) {
                $aSlides[] = (object) [
                    'id'        => !empty($aSlideIds[$i]) ? $aSlideIds[$i] : null,
                    'object_id' => !empty($aObjectIds[$i]) ? $aObjectIds[$i] : null,
                    'caption'   => !empty($aCaptions[$i]) ? $aCaptions[$i] : null,
                    'url'       => !empty($aUrls[$i]) ? $aUrls[$i] : null,
                ];
            }

            // --------------------------------------------------------------------------

            //  Validate form
            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('label', '', 'trim|required');
            $oFormValidation->set_rules('description', '', 'trim');
            $oFormValidation->set_message('required', lang('fv_required'));

            if ($oFormValidation->run()) {

                $aSliderData = [
                    'label'       => $oInput->post('label'),
                    'description' => strip_tags($oInput->post('description')),
                    'slides'      => $aSlides,
                ];

                if ($this->oSliderModel->update($oSlide->id, $aSliderData)) {

                    $oSession = Factory::service('Session', 'nails/module-auth');
                    $oSession->setFlashData('success', 'Sldier updated successfully.');
                    redirect('admin/cms/slider');

                } else {
                    $this->data['error'] = 'Failed to update slider. ';
                    $this->data['error'] .= $this->oSliderModel->lastError();
                }

            } else {
                $this->data['error'] = lang('fv_there_were_errors');
            }

        } else {
            $aSlides = $oSlide->slides;
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Edit Slider &rsaquo; ' . $oSlide->label;

        // --------------------------------------------------------------------------

        //  Prep the slides for the view
        foreach ($aSlides as $oSlide) {
            $oSlide->imgSourceUrl = !empty($oSlide->object_id) ? cdnServe($oSlide->object_id) : null;
            $oSlide->imgThumbUrl  = !empty($oSlide->object_id) ? cdnScale($oSlide->object_id, 130, 130) : null;
        }

        // --------------------------------------------------------------------------

        //  Define the manager URL
        $sCdnManagerUrl = cdnManagerUrl(
            'cms-slider',
            ['sliderEdit', 'setImgCallback'],
            null,
            Functions::isPageSecure()
        );

        // --------------------------------------------------------------------------

        //  Assets
        $oCdn   = Factory::service('Cdn', 'nails/module-cdn');
        $oAsset = Factory::service('Asset');
        $oAsset->load('jquery-ui/jquery-ui.min.js', 'NAILS-BOWER');
        $oAsset->library('MUSTACHE');
        $oAsset->load('admin.sliders.edit.min.js', 'nails/module-cms');
        $oAsset->inline('var sliderEdit = new NAILS_Admin_CMS_Sliders_Create_Edit();', 'JS');
        $oAsset->inline('sliderEdit.setScheme("serve", "' . $oCdn->urlServeScheme() . '");', 'JS');
        $oAsset->inline('sliderEdit.setScheme("thumb", "' . $oCdn->urlCropScheme() . '");', 'JS');
        $oAsset->inline('sliderEdit.setScheme("scale", "' . $oCdn->urlScaleScheme() . '");', 'JS');
        $oAsset->inline('sliderEdit.setManagerUrl("' . $sCdnManagerUrl . '");', 'JS');
        $oAsset->inline('sliderEdit.addSlides(' . json_encode($aSlides) . ');', 'JS');

        // --------------------------------------------------------------------------

        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a CMS Slider
     * @return void
     */
    public function delete()
    {
        if (!userHasPermission('admin:cms:slider:delete')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Fetch and check post
        $oUri      = Factory::service('Uri');
        $iSliderId = $oUri->segment(5);
        $oSlider   = $this->oSliderModel->getById($iSliderId);

        if (!$oSlider) {
            $oSession = Factory::service('Session', 'nails/module-auth');
            $oSession->setFlashData('error', 'No slider by that ID.');
            redirect('admin/cms/slider');
        }

        // --------------------------------------------------------------------------

        if ($this->oSliderModel->delete($oSlider->id)) {
            $sStatus  = 'success';
            $sMessage = 'Slider was deleted successfully.';
        } else {
            $sStatus  = 'error';
            $sMessage = 'Failed to delete that slider. ' . $this->oSliderModel->lastError();
        }

        $oSession = Factory::service('Session', 'nails/module-auth');
        $oSession->setFlashData($sStatus, $sMessage);
        redirect('admin/cms/slider');
    }
}
