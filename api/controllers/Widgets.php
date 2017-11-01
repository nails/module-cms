<?php

/**
 * Admin API end points: Pages
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Api\Cms;

use Nails\Factory;

class Widgets extends \Nails\Api\Controller\Base
{
    /**
     * Require the user be authenticated to use any endpoint
     */
    const REQUIRE_AUTH = true;

    // --------------------------------------------------------------------------

    public function getIndex()
    {
        if (userHasPermission('admin:cms:pages:*') || userHasPermission('admin:cms:area:*')) {

            $oWidgetModel  = Factory::model('Widget', 'nailsapp/module-cms');
            $oAssetLibrary = Factory::service('Asset');
            $aWidgets      = array();
            $aAssets       = array();
            $aWidgetGroups = $oWidgetModel->getAvailable();

            $oAssetLibrary->clear();

            foreach ($aWidgetGroups as $oWidgetGroup) {

                $aWidgets[] = json_decode($oWidgetGroup->toJson());

                foreach ($oWidgetGroup->getWidgets() as $oWidget) {

                    $aWidgetAssets = $oWidget->getAssets('EDITOR');

                    foreach ($aWidgetAssets as $aAsset) {

                        $sAsset    = !empty($aAsset[0]) ? $aAsset[0] : '';
                        $sLocation = !empty($aAsset[1]) ? $aAsset[1] : '';

                        if (!empty($sAsset)) {
                            $oAssetLibrary->load($sAsset, $sLocation);
                        }
                    }
                }
            }

            return array(
                'assets'  => array(
                    'css' => $oAssetLibrary->output('CSS', false),
                    'js' => $oAssetLibrary->output('JS', false)
                ),
                'widgets' => $aWidgets
            );

        } else {

            return array(
                'status' => 401,
                'error'  => 'You do not have permission to view widgets.'
            );
        }
    }

    // --------------------------------------------------------------------------

    public function postEditor()
    {
        if (userHasPermission('admin:cms:pages:*') || userHasPermission('admin:cms:area:*')) {

            $sWidgetSlug  = $this->input->post('slug');
            $aWidgetData  = $this->input->post('data') ?: array();
            $oWidgetModel = Factory::model('Widget', 'nailsapp/module-cms');
            $oWidget      = $oWidgetModel->getBySlug($sWidgetSlug);

            if ($oWidget) {

                return array(
                    'editor' => $oWidget->getEditor($aWidgetData)
                );

            } else {

                return array(
                    'status' => 400,
                    'error'  => '"' . $sWidgetSlug . '" is not a valid widget.'
                );
            }

        } else {

            return array(
                'status' => 401,
                'error'  => 'You do not have permission to view widgets.'
            );
        }
    }

    // --------------------------------------------------------------------------

    public function postEditors()
    {
        if (userHasPermission('admin:cms:pages:*') || userHasPermission('admin:cms:area:*')) {

            $aWidgetData  = $this->input->post('data') ?: array();
            $oWidgetModel = Factory::model('Widget', 'nailsapp/module-cms');
            $aOut         = array('data' => array());

            foreach ($aWidgetData as $aData) {

                $sRenderSlug = !empty($aData['slug']) ? $aData['slug'] : null;
                if (!empty($sRenderSlug)) {

                    $oWidget = $oWidgetModel->getBySlug($aData['slug']);

                    if ($oWidget) {

                        $aRenderData = !empty($aData['data']) ? $aData['data'] : array();
                        $aOut['data'][] = array(
                            'slug'   => $aData['slug'],
                            'editor' => $oWidget->getEditor($aRenderData)
                        );

                    } else {

                        $aOut['data'][] = array(
                            'slug'   => $aData['slug'],
                            'editor' => '"' . $aData['slug'] . '" is not a valid widget.',
                            'error'  => true
                        );
                    }

                } else {

                    $aOut['data'][] = array(
                        'slug'   => $aData['slug'],
                        'editor' => 'No widget supplied.',
                        'error'  => true
                    );
                }
            }

            return $aOut;

        } else {

            return array(
                'status' => 401,
                'error'  => 'You do not have permission to view widgets.'
            );
        }
    }
}
