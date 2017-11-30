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

use Nails\Api\Controller\Base;
use Nails\Factory;

class Widgets extends Base
{
    /**
     * Require the user be authenticated to use any endpoint
     */
    const REQUIRE_AUTH = true;

    // --------------------------------------------------------------------------

    /**
     * Returns all available widgets
     * @return array
     */
    public function getIndex()
    {
        try {

            if (!isAdmin()) {
                throw new \Exception('You do not have permission to view widgets.', 401);
            }

            $oWidgetModel  = Factory::model('Widget', 'nailsapp/module-cms');
            $oAsset        = Factory::service('Asset');
            $aWidgets      = [];
            $aWidgetGroups = $oWidgetModel->getAvailable();

            $oAsset->clear();

            foreach ($aWidgetGroups as $oWidgetGroup) {

                $aWidgets[] = json_decode($oWidgetGroup->toJson());

                foreach ($oWidgetGroup->getWidgets() as $oWidget) {

                    $aWidgetAssets = $oWidget->getAssets('EDITOR');

                    foreach ($aWidgetAssets as $aAsset) {
                        $sAsset    = !empty($aAsset[0]) ? $aAsset[0] : '';
                        $sLocation = !empty($aAsset[1]) ? $aAsset[1] : '';

                        if (!empty($sAsset)) {
                            $oAsset->load($sAsset, $sLocation);
                        }
                    }
                }
            }

            return [
                'assets'  => [
                    'css' => $oAsset->output('CSS', false),
                    'js'  => $oAsset->output('JS', false),
                ],
                'widgets' => $aWidgets,
            ];

        } catch (\Exception $e) {
            return [
                'status' => $e->getCode(),
                'error'  => $e->getMessage(),
            ];
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the editor for a particular widget, pre-populated with POST'ed data
     * @return array
     */
    public function postEditor()
    {
        try {

            if (!isAdmin()) {
                throw new \Exception('You do not have permission to view widgets.', 401);
            }

            $oInput       = Factory::service('Input');
            $sWidgetSlug  = $oInput->post('slug');
            $aWidgetData  = $oInput->post('data') ?: [];
            $oWidgetModel = Factory::model('Widget', 'nailsapp/module-cms');
            $oWidget      = $oWidgetModel->getBySlug($sWidgetSlug);

            if (!$oWidget) {
                throw new \Exception('"' . $sWidgetSlug . '" is not a valid widget.', 400);
            }

            return [
                'editor' => $oWidget->getEditor($aWidgetData),
            ];

        } catch (\Exception $e) {
            return [
                'status' => $e->getCode(),
                'error'  => $e->getMessage(),
            ];
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the editors for all POST'ed widgets, pre-populated with data
     * @return array
     */
    public function postEditors()
    {
        try {

            if (!isAdmin()) {
                throw new \Exception('You do not have permission to view widgets.', 401);
            }

            $oInput       = Factory::service('Input');
            $aWidgetData  = $oInput->post('data') ?: [];
            $oWidgetModel = Factory::model('Widget', 'nailsapp/module-cms');
            $aOut         = ['data' => []];

            foreach ($aWidgetData as $aData) {

                $sRenderSlug = !empty($aData['slug']) ? $aData['slug'] : null;
                if (!empty($sRenderSlug)) {

                    $oWidget = $oWidgetModel->getBySlug($aData['slug']);

                    if ($oWidget) {

                        $aRenderData    = !empty($aData['data']) ? $aData['data'] : [];
                        $aOut['data'][] = [
                            'slug'   => $aData['slug'],
                            'editor' => $oWidget->getEditor($aRenderData),
                        ];

                    } else {

                        $aOut['data'][] = [
                            'slug'   => $aData['slug'],
                            'editor' => '"' . $aData['slug'] . '" is not a valid widget.',
                            'error'  => true,
                        ];
                    }

                } else {
                    $aOut['data'][] = [
                        'slug'   => $aData['slug'],
                        'editor' => 'No widget supplied.',
                        'error'  => true,
                    ];
                }
            }

            return $aOut;

        } catch (\Exception $e) {
            return [
                'status' => $e->getCode(),
                'error'  => $e->getMessage(),
            ];
        }
    }
}
