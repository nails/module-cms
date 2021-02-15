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

use Nails\Admin\Helper;
use Nails\Cms\Constants;
use Nails\Cms\Controller\BaseAdmin;
use Nails\Common\Service\Session;
use Nails\Common\Service\Uri;
use Nails\Factory;

/**
 * Class Slider
 *
 * @package Nails\Admin\Cms
 */
class Slider extends BaseAdmin
{
    protected $oSliderModel;

    // --------------------------------------------------------------------------

    /**
     * Announces this controller's navGroups
     *
     * @return \Nails\Admin\Factory\Nav
     */
    public static function announce()
    {
        if (userHasPermission('admin:cms:slider:manage')) {
            $oNavGroup = Factory::factory('Nav', \Nails\Admin\Constants::MODULE_SLUG);
            $oNavGroup->setLabel('CMS');
            $oNavGroup->setIcon('fa-file-alt');
            $oNavGroup->addAction('Manage Sliders');
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
        $this->oSliderModel = Factory::model('Slider', Constants::MODULE_SLUG);
    }

    // --------------------------------------------------------------------------

    /**
     * Browse CMS Sliders
     *
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
     *
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

            //  Validate form
            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('label', '', 'required');
            $oFormValidation->set_rules('description', '', '');
            $oFormValidation->set_message('required', lang('fv_required'));

            if ($oFormValidation->run()) {

                $aSliderData = [
                    'label'       => trim($oInput->post('label')),
                    'description' => trim(strip_tags($oInput->post('description'))),
                    'slides'      => array_map(function (array $aSlide) {
                        return (object) [
                            'id'        => getFromArray('id', $aSlide),
                            'order'     => getFromArray('order', $aSlide),
                            'object_id' => getFromArray('object_id', $aSlide),
                            'caption'   => getFromArray('caption', $aSlide),
                            'url'       => getFromArray('url', $aSlide),
                        ];
                    }, array_filter((array) $oInput->post('items'))),
                ];

                if ($this->oSliderModel->create($aSliderData)) {

                    /** @var Session $oSession */
                    $oSession = Factory::service('Session');
                    $oSession->setFlashData('success', 'Slider created successfully.');
                    redirect('admin/cms/slider');

                } else {
                    $this->data['error'] = 'Failed to create slider. ';
                    $this->data['error'] .= $this->oSliderModel->lastError();
                }

            } else {
                $this->data['error'] = lang('fv_there_were_errors');
            }

        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Create Slider';

        // --------------------------------------------------------------------------

        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a CMS Slider
     *
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
            /** @var Session $oSession */
            $oSession = Factory::service('Session');
            $oSession->setFlashData('error', 'Invalid slider ID.');
            redirect('admin/cms/slider');
        }

        // --------------------------------------------------------------------------

        $oInput = Factory::service('Input');
        if ($oInput->post()) {

            //  Validate form
            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('label', '', 'required');
            $oFormValidation->set_rules('description', '', '');
            $oFormValidation->set_message('required', lang('fv_required'));

            if ($oFormValidation->run()) {

                $aSliderData = [
                    'label'       => trim($oInput->post('label')),
                    'description' => trim(strip_tags($oInput->post('description'))),
                    'slides'      => array_map(function (array $aSlide) {
                        return (object) [
                            'id'        => getFromArray('id', $aSlide),
                            'order'     => getFromArray('order', $aSlide),
                            'object_id' => getFromArray('object_id', $aSlide),
                            'caption'   => getFromArray('caption', $aSlide),
                            'url'       => getFromArray('url', $aSlide),
                        ];
                    }, array_filter((array) $oInput->post('items'))),
                ];

                if ($this->oSliderModel->update($oSlide->id, $aSliderData)) {

                    /** @var Session $oSession */
                    $oSession = Factory::service('Session');
                    $oSession->setFlashData('success', 'Sldier updated successfully.');
                    redirect('admin/cms/slider');

                } else {
                    $this->data['error'] = 'Failed to update slider. ';
                    $this->data['error'] .= $this->oSliderModel->lastError();
                }

            } else {
                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Edit Slider &rsaquo; ' . $oSlide->label;

        // --------------------------------------------------------------------------

        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a CMS Slider
     *
     * @return void
     */
    public function delete()
    {
        if (!userHasPermission('admin:cms:slider:delete')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Fetch and check post
        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');
        /** @var Session $oSession */
        $oSession = Factory::service('Session');

        $iSliderId = $oUri->segment(5);
        $oSlider   = $this->oSliderModel->getById($iSliderId);

        if (!$oSlider) {
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

        $oSession->setFlashData($sStatus, $sMessage);
        redirect('admin/cms/slider');
    }
}
