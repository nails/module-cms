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
use Nails\Cms\Model\Page\Preview;
use Nails\Common\Service\HttpCodes;
use Nails\Common\Service\Input;
use Nails\Factory;

/**
 * Class Pages
 *
 * @package Nails\Cms\Api\Controller
 */
class Pages extends Api\Controller\Base
{
    /**
     * Require the user be authenticated to use any endpoint
     */
    const REQUIRE_AUTH = true;

    // --------------------------------------------------------------------------

    /**
     * Pages constructor.
     *
     * @param $oApiRouter
     *
     * @throws ApiException
     */
    public function __construct($oApiRouter)
    {
        parent::__construct($oApiRouter);

        /** @var HttpCodes $oHttpCodes */
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
     * Returns the URL for a page preview
     *
     * @return Api\Factory\ApiResponse
     */
    public function postPreview()
    {
        /** @var Input $oInput */
        $oInput = Factory::service('Input');
        /** @var Preview $oPagePreviewModel */
        $oPagePreviewModel = Factory::model('PagePreview', Constants::MODULE_SLUG);

        $aPageData = [
            'title'            => $oInput->post('title'),
            'slug'             => $oInput->post('slug'),
            'parent_id'        => (int) $oInput->post('parent_id') ?: null,
            'template'         => $oInput->post('template'),
            'template_data'    => $oInput->post('template_data'),
            'template_options' => $oInput->post('template_options'),
            'seo_title'        => $oInput->post('seo_title'),
            'seo_description'  => $oInput->post('seo_description'),
            'seo_keywords'     => $oInput->post('seo_keywords'),
            'seo_image_id'     => (int) $oInput->post('seo_image_id') ?: null,
        ];

        if (!empty($aPageData['template_options'][$aPageData['template']])) {
            $aPageData['template_options'] = $aPageData['template_options'][$aPageData['template']];
            $aPageData['template_options'] = json_encode($aPageData['template_options']);
        } else {
            $aPageData['template_options'] = null;
        }

        $iPreviewId = $oPagePreviewModel->create($aPageData);
        if ($iPreviewId) {
            $aOut = ['url' => siteUrl('cms/render/preview/' . $iPreviewId)];
        } else {
            $aOut = ['status' => 500, 'error' => $oPagePreviewModel->lastError()];
        }

        return Factory::factory('ApiResponse', Api\Constants::MODULE_SLUG)
            ->setData($aOut);
    }
}
