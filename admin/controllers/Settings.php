<?php

/**
 * This class registers some handlers for Invoicing & Payment settings
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Cms;

use Nails\Factory;
use Nails\Admin\Helper;
use Nails\Cms\Controller\BaseAdmin;

class Settings extends BaseAdmin
{
    /**
     * Announces this controller's navGroups
     * @return \Nails\Admin\Nav
     */
    public static function announce()
    {
        $oNavGroup = Factory::factory('Nav', 'nails/module-admin');
        $oNavGroup->setLabel('Settings');
        $oNavGroup->setIcon('fa-wrench');

        if (userHasPermission('admin:cms:settings:*')) {
            $oNavGroup->addAction('CMS');
        }

        return $oNavGroup;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of permissions which can be configured for the user
     * @return array
     */
    public static function permissions()
    {
        $aPermissions = parent::permissions();

        $aPermissions['homepage'] = 'Can set/change the page used as the homepage';

        return $aPermissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Manage Email settings
     * @return void
     */
    public function index()
    {
        //  Process POST
        if ($this->input->post()) {

            $aSettings = [
                //  General Settings
                'homepage' => (int) $this->input->post('homepage'),
            ];

            // --------------------------------------------------------------------------

            //  Validation
            $oFormValidation = Factory::service('FormValidation');

            $oFormValidation->set_rules('homepage', '', 'required|is_natural_no_zero');

            $oFormValidation->set_message('required', lang('fv_required'));
            $oFormValidation->set_message('is_natural_no_zero', lang('fv_required'));

            if ($oFormValidation->run()) {

                $oDb = Factory::service('Database');

                $oDb->trans_begin();

                $bRollback        = false;
                $oAppSettingModel = Factory::model('AppSetting');

                //  Normal settings
                if (!$oAppSettingModel->set($aSettings, 'nails/module-cms')) {

                    $sError    = $oAppSettingModel->lastError();
                    $bRollback = true;
                }

                if (empty($bRollback)) {

                    $oDb->trans_commit();
                    $this->data['success'] = 'CMS settings were saved.';

                } else {

                    $oDb->trans_rollback();
                    $this->data['error'] = 'There was a problem saving settings. ' . $sError;
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Get data
        $this->data['settings'] = appSetting(null, 'nails/module-cms', true);

        //  Get Published pages
        $oPagesModel                  = Factory::model('Page', 'nails/module-cms');
        $this->data['publishedPages'] = $oPagesModel->getAllFlat(
            null,
            null,
            [
                'where' => [
                    ['is_published', true],
                ],
            ]
        );

        Helper::loadView('index');
    }
}
