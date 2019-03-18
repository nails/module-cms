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

namespace Nails\Cms\Api\Controller;

use Nails\Api\Controller\Base;
use Nails\Api\Exception\ApiException;
use Nails\Common\Exception\NailsException;
use Nails\Factory;

class Widgets extends Base
{
    /**
     * Require the user be authenticated to use any endpoint
     */
    const REQUIRE_AUTH = true;

    // --------------------------------------------------------------------------

    /**
     * Widgets constructor.
     *
     * @param $oApiRouter
     *
     * @throws ApiException
     */
    public function __construct($oApiRouter)
    {
        parent::__construct($oApiRouter);
        $oHttpCodes = Factory::service('HttpCodes');
        if (!isAdmin()) {
            throw new ApiException(
                'You do not have permission to access this resource.',
                $oHttpCodes::STATUS_UNAUTHORIZED
            );
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns all available widgets
     * @return array
     */
    public function getIndex()
    {
        $oWidgetModel  = Factory::model('Widget', 'nails/module-cms');
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

        arraySortMulti($aWidgets, 'label');
        $aWidgets = array_values($aWidgets);

        return Factory::factory('ApiResponse', 'nails/module-api')
                      ->setData([
                          'assets'  => [
                              'css' => $oAsset->output('CSS', false),
                              'js'  => $oAsset->output('JS', false),
                          ],
                          'widgets' => $aWidgets,
                      ]);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the editor for a particular widget, pre-populated with POST'ed data
     * @return array
     */
    public function postEditor()
    {
        $oInput       = Factory::service('Input');
        $sWidgetSlug  = $oInput->post('slug');
        $aWidgetData  = $oInput->post('data') ?: [];
        $oWidgetModel = Factory::model('Widget', 'nails/module-cms');
        $oWidget      = $oWidgetModel->getBySlug($sWidgetSlug);

        if (!$oWidget) {
            throw new NailsException('"' . $sWidgetSlug . '" is not a valid widget.', 400);
        }

        return Factory::factory('ApiResponse', 'nails/module-api')
                      ->setData([
                          'editor' => $oWidget->getEditor($aWidgetData),
                      ]);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the editors for all POST'ed widgets, pre-populated with data
     * @return array
     */
    public function postEditors()
    {
        $oInput       = Factory::service('Input');
        $aWidgetData  = json_decode($oInput->post('data')) ?: [];
        $oWidgetModel = Factory::model('Widget', 'nails/module-cms');
        $aOut         = [];

        foreach ($aWidgetData as $oData) {

            if (!empty($oData->slug)) {

                $oWidget = $oWidgetModel->getBySlug($oData->slug);

                if ($oWidget) {

                    $aRenderData = !empty($oData->data) ? $oData->data : [];
                    $aOut[]      = [
                        'slug'   => $oData->slug,
                        'editor' => $oWidget->getEditor((array) $aRenderData),
                    ];

                } else {
                    $aOut[] = [
                        'slug'   => $oData->slug,
                        'editor' => '"' . $oData->slug . '" is not a valid widget.',
                        'error'  => true,
                    ];
                }

            } else {
                $aOut[] = [
                    'slug'   => null,
                    'editor' => 'No widget supplied.',
                    'error'  => true,
                ];
            }
        }

        return Factory::factory('ApiResponse', 'nails/module-api')
                      ->setData($aOut);
    }
}
