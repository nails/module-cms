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

use Nails\Api;
use Nails\Cms\Constants;
use Nails\Common\Exception\NailsException;
use Nails\Factory;

/**
 * Class Widgets
 *
 * @package Nails\Cms\Api\Controller
 */
class Widgets extends Api\Controller\Base
{
    /**
     * Require the user be authenticated to use any endpoint
     *
     * @var bool
     */
    const REQUIRE_AUTH = true;

    // --------------------------------------------------------------------------

    /**
     * Widgets constructor.
     *
     * @param $oApiRouter
     *
     * @throws Api\Exception\ApiException
     */
    public function __construct($oApiRouter)
    {
        parent::__construct($oApiRouter);
        $oHttpCodes = Factory::service('HttpCodes');
        if (!isAdmin()) {
            throw new Api\Exception\ApiException(
                'You do not have permission to access this resource.',
                $oHttpCodes::STATUS_UNAUTHORIZED
            );
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns all available widgets
     *
     * @return array
     */
    public function getIndex()
    {
        /** @var \Nails\Cms\Service\Widget $oWidgetService */
        $oWidgetService = Factory::service('Widget', Constants::MODULE_SLUG);
        /** @var \Nails\Common\Service\Asset $oAsset */
        $oAsset = Factory::service('Asset');

        $aWidgets      = [];
        $aWidgetGroups = $oWidgetService->getAvailable();

        $oAsset->clear();
        $oWidgetService->loadEditorAssets($aWidgetGroups);

        foreach ($aWidgetGroups as $oWidgetGroup) {
            $aWidgets[] = json_decode($oWidgetGroup->toJson());
        }

        arraySortMulti($aWidgets, 'label');
        $aWidgets = array_values($aWidgets);

        return Factory::factory('ApiResponse', Api\Constants::MODULE_SLUG)
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
     *
     * @return array
     */
    public function postEditor()
    {
        $oInput         = Factory::service('Input');
        $sWidgetSlug    = $oInput->post('slug');
        $aWidgetData    = $oInput->post('data') ?: [];
        $oWidgetService = Factory::service('Widget', Constants::MODULE_SLUG);
        $oWidget        = $oWidgetService->getBySlug($sWidgetSlug);

        if (!$oWidget) {
            throw new NailsException('"' . $sWidgetSlug . '" is not a valid widget.', 400);
        }

        return Factory::factory('ApiResponse', Api\Constants::MODULE_SLUG)
            ->setData([
                'editor' => $oWidget->getEditor($aWidgetData),
            ]);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the editors for all POST'ed widgets, pre-populated with data
     *
     * @return array
     */
    public function postEditors()
    {
        $oInput         = Factory::service('Input');
        $aWidgetData    = json_decode((string) $oInput->post('data')) ?: [];
        $oWidgetService = Factory::service('Widget', Constants::MODULE_SLUG);
        $aOut           = [];

        foreach ($aWidgetData as $oData) {

            if (!empty($oData->slug)) {

                $oWidget = $oWidgetService->getBySlug($oData->slug);

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

        return Factory::factory('ApiResponse', Api\Constants::MODULE_SLUG)
            ->setData($aOut);
    }
}
