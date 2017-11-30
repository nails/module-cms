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

class Pages extends Base
{
    /**
     * Require the user be authenticated to use any endpoint
     */
    const REQUIRE_AUTH = true;

    // --------------------------------------------------------------------------

    /**
     * Returns the URL for a page preview
     * @return array
     */
    public function postPreview()
    {
        $oInput     = Factory::service('Input');
        $oPageModel = Factory::model('Page', 'nailsapp/module-cms');
        $aPageData  = [
            'title'            => $oInput->post('title'),
            'slug'             => $oInput->post('slug'),
            'parent_id'        => (int) $oInput->post('parent_id') ?: null,
            'template'         => $oInput->post('template'),
            'template_data'    => $oInput->post('template_data'),
            'template_options' => $oInput->post('template_options'),
            'seo_title'        => $oInput->post('seo_title'),
            'seo_description'  => $oInput->post('seo_description'),
            'seo_keywords'     => $oInput->post('seo_keywords'),
        ];

        if (!empty($aPageData['template_options'][$aPageData['template']])) {
            $aPageData['template_options'] = $aPageData['template_options'][$aPageData['template']];
            $aPageData['template_options'] = json_encode($aPageData['template_options']);
        } else {
            $aPageData['template_options'] = null;
        }

        $iPreviewId = $oPageModel->createPreview($aPageData);
        if ($iPreviewId) {
            return ['url' => site_url('cms/render/preview/' . $iPreviewId)];
        } else {
            return ['status' => 500, 'error' => $oPageModel->lastError()];
        }
    }
}
