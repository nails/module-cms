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

class Pages extends \Nails\Api\Controller\Base
{
    public static $requiresAuthentication = true;

    // --------------------------------------------------------------------------

    /**
     * Get the widget's editor, preopulated with any POST'ed data
     * @return array
     */
    public function postWidgetEditor()
    {
        $aOut             = array();
        $sRequestedWidget = $this->input->post('widget');

        if ($sRequestedWidget) {

            parse_str($this->input->post('data'), $aWidgetData);

            $oWidgetModel     = Factory::model('Widget', 'nailsapp/module-cms');
            $oRequestedWidget = $oWidgetModel->getBySlug($sRequestedWidget);

            if ($oRequestedWidget) {

                $aOut['HTML'] = $oRequestedWidget->getEditor($aWidgetData);

                if (empty($aOut['HTML'])) {

                    $aOut['HTML'] = '<p class="static">This widget has no configurable options.</p>';
                }

            } else {

                $aOut['status'] = 400;
                $aOut['error']  = 'Invalid Widget - Error number 2';
            }

        } else {

            $aOut['status'] = 400;
            $aOut['error']  = 'Widget slug must be specified - Error number 1';
        }

        return $aOut;
    }

    // --------------------------------------------------------------------------

    public function postPreview()
    {
        $oPageModel = Factory::model('Page', 'nailsapp/module-cms');
        $aPageData  = array(
            'title' => $this->input->post('title'),
            'slug' => $this->input->post('slug'),
            'parent_id' => (int) $this->input->post('parent_id'),
            'template' => $this->input->post('template'),
            'template_data' => $this->input->post('template_data'),
            'template_options' => $this->input->post('template_options'),
            'seo_title' => $this->input->post('seo_title'),
            'seo_description' => $this->input->post('seo_description'),
            'seo_keywords' => $this->input->post('seo_keywords')
        );

        $aPageData['parent_id'] = !empty($aPageData['parent_id']) ? $aPageData['parent_id'] : null;

        if (!empty($aPageData['template_options'][$aPageData['template']])) {

            $aPageData['template_options'] = $aPageData['template_options'][$aPageData['template']];
            $aPageData['template_options'] = json_encode($aPageData['template_options']);

        } else {

            $aPageData['template_options'] = null;
        }

        $iPreviewId = $oPageModel->createPreview($aPageData);
        if ($iPreviewId) {

            return array('url' => site_url('cms/render/preview/' . $iPreviewId));

        } else {

            return array('status' => 500, 'error' => $oPageModel->last_error());
        }
    }
}
