<?php

namespace Nails\Api\Cms;

/**
 * Admin API end points: Pages
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class Pages extends \Nails\Api\Controllers\Base
{
    public static $requiresAuthentication = true;

    // --------------------------------------------------------------------------

    /**
     * Save CMS pages
     * @return array
     */
    public function postSave()
    {
        if (!$this->user_model->isAdmin()) {

            return array(
                'status' => 401,
                'error'  => 'You must be an administrator.'
            );

        } else {

            $sPageDataRaw     = $this->input->post('page_data');
            $sPublishAction   = $this->input->post('publish_action');
            $bGeneratePreview = (bool) $this->input->post('generate_preview');

            if (!$sPageDataRaw) {

                return array(
                    'status' => 400,
                    'error'  => '"page_data" is a required parameter.'
                );
            }

            // --------------------------------------------------------------------------

            //  Decode and check
            $oPageData = json_decode($sPageDataRaw);

            if (is_null($oPageData)) {

                log_message(
                    'error',
                    'API: cms/pages/save - Error decoding JSON: ' . $sPageDataRaw
                );
                return array(
                    'status' => 400,
                    'error'  => '"page_data" is a required parameter.'
                );
            }

            if (empty($oPageData->hash)) {

                log_message(
                    'error',
                    'API: cms/pages/save - Empty hash supplied.'
                );
                return array(
                    'status' => 400,
                    'error'  => '"hash" is a required parameter.'
                );
            }

            //  A template must be defined
            if (empty($oPageData->data->template)) {

                return array(
                    'status' => 400,
                    'error'  => '"data.template" is a required parameter.'
                );
            }

            // --------------------------------------------------------------------------

            /**
             * Validate data
             * JSON.stringify doesn't seem to escape forward slashes like PHP does. Check
             * both in case this is a cross browser issue.
             */

            $sHash                   = $oPageData->hash;
            $oCheckObj               = new \stdClass();
            $oCheckObj->data         = $oPageData->data;
            $oCheckObj->widget_areas = $oPageData->widget_areas;
            $sCheckHash1             = md5(json_encode($oCheckObj, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));

            if ($sHash !== $sCheckHash1) {

                $sCheckHash2 = md5(json_encode($oCheckObj));

                if ($sHash !== $sCheckHash2) {

                    log_message(
                        'error',
                        'API: cms/pages/save - Failed to verify hashes. Posted JSON{ ' .  $sPageDataRaw
                    );
                    return array(
                        'status' => 400,
                        'error'  => 'Data failed hash validation. Data might have been modified in transit.'
                    );
                }
            }

            $oPageData->hash = $sHash;

            // --------------------------------------------------------------------------

            /**
             * All seems good, let's process this mofo'ing data. Same format as supplied,
             * just manually specifying things for supreme consistency. Multi-pass?
             */

            $aData                            = array();
            $aData['hash']                    = $oPageData->hash;
            $aData['id']                      = !empty($oPageData->id) ? (int) $oPageData->id : null;
            $aData['data']                    = new \stdClass();
            $aData['data']->title             = !empty($oPageData->data->title) ? $oPageData->data->title : '';
            $aData['data']->parent_id         = !empty($oPageData->data->parent_id) ? (int) $oPageData->data->parent_id : '';
            $aData['data']->seo_title         = !empty($oPageData->data->seo_title) ? $oPageData->data->seo_title : '';
            $aData['data']->seo_description   = !empty($oPageData->data->seo_description) ? $oPageData->data->seo_description : '';
            $aData['data']->seo_keywords      = !empty($oPageData->data->seo_keywords) ? $oPageData->data->seo_keywords : '';
            $aData['data']->template          = $oPageData->data->template;
            $aData['data']->additional_fields = !empty($oPageData->data->additional_fields) ? $oPageData->data->additional_fields : '';
            $aData['widget_areas']            = !empty($oPageData->widget_areas) ? $oPageData->widget_areas : new \stdClass;

            if ($aData['data']->additional_fields) {

                parse_str($aData['data']->additional_fields, $_additional_fields);

                if (!empty($_additional_fields['additional_field'])) {

                    $aData['data']->additional_fields = $_additional_fields['additional_field'];

                } else {

                    $aData['data']->additional_fields = array();
                }

                /**
                 * We're going to encode then decode the additional fields, so they're
                 * consistent with the save objects
                 */

                $aData['data']->additional_fields = json_decode(json_encode($aData['data']->additional_fields));
            }

            // --------------------------------------------------------------------------

            /**
             * Data is set, determine whether we're previewing, saving or creating
             * If an ID is missing then we're creating a new page otherwise we're updating.
             */

            $this->load->model('cms/cms_page_model');

            if (!empty($bGeneratePreview)) {

                if (!userHasPermission('admin:cms:pages:preview')) {

                    return array(
                        'status' => 400,
                        'error'  => 'You do not have permission to preview CMS Pages.'
                    );
                }

                $iId = $this->cms_page_model->createPreview($aData);

                if (!$iId) {

                    return array(
                        'status' => 500,
                        'error'  => 'There was a problem creating the page preview. ' . $this->cms_page_model->last_error()
                    );
                }

                $aOut       = array();
                $aOut['id'] = $iId;

            } else {

                if (empty($aData['id'])) {

                    if (!userHasPermission('admin:cms:pages:create')) {

                        return array(
                            'status' => 400,
                            'error'  => 'You do not have permission to create CMS Pages.'
                        );
                        return;
                    }

                    $iId = $this->cms_page_model->create($aData);

                    if (!$iId) {

                        return array(
                            'status' => 500,
                            'error'  => 'There was a problem saving the page. ' . $this->cms_page_model->last_error()
                        );
                        return;
                    }

                } else {

                    if (!userHasPermission('admin:cms:pages:edit')) {

                        return array(
                            'status' => 400,
                            'error'  => 'You do not have permission to edit CMS Pages.'
                        );
                        return;

                    }

                    if ($this->cms_page_model->update($aData['id'], $aData)) {

                        $iId = $aData['id'];

                    } else {

                        return array(
                            'status' => 500,
                            'error'  => 'There was a problem saving the page. ' . $this->cms_page_model->last_error()
                        );
                        return;
                    }
                }

                // --------------------------------------------------------------------------

                /**
                 * Page has been saved! Any further steps?
                 * - If is_published is defined then we need to consider it's published status.
                 * - If is_published is null then we're leaving it as it is.
                 */

                $aOut       = array();
                $aOut['id'] = $iId;

                switch ($sPublishAction) {

                    case 'PUBLISH':

                        $this->cms_page_model->publish($iId);
                        break;

                    case 'NONE':
                    default:

                        //  Do nothing, absolutely nothing. Go have a margarita.
                        break;
                }
            }

            return $aOut;
        }
    }

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

            $this->load->model('cms/cms_page_model');

            $oRequestedWidget = $this->cms_page_model->getWidget($sRequestedWidget);

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
}
