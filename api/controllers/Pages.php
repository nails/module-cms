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

class Pages extends \ApiController
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

            $pageDataRaw     = $this->input->post('page_data');
            $publishAction   = $this->input->post('publish_action');
            $generatePreview = $this->input->post('generate_preview');

            if (!$pageDataRaw) {

                return array(
                    'status' => 400,
                    'error'  => '"page_data" is a required parameter.'
                );
            }

            // --------------------------------------------------------------------------

            //  Decode and check
            $pageData = json_decode($pageDataRaw);

            if (is_null($pageData)) {

                log_message(
                    'error',
                    'API: cms/pages/save - Error decoding JSON: ' . $pageDataRaw
                );
                return array(
                    'status' => 400,
                    'error'  => '"page_data" is a required parameter.'
                );
            }

            if (empty($pageData->hash)) {

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
            if (empty($pageData->data->template)) {

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

            $hash                   = $pageData->hash;
            $checkObj               = new \stdClass();
            $checkObj->data         = $pageData->data;
            $checkObj->widget_areas = $pageData->widget_areas;
            $checkHash1             = md5(json_encode($checkObj, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));

            if ($hash !== $checkHash1) {

                $checkHash2 = md5(json_encode($checkObj));

                if ($hash !== $checkHash2) {

                    log_message(
                        'error',
                        'API: cms/pages/save - Failed to verify hashes. Posted JSON{ ' .  $pageDataRaw
                    );
                    return array(
                        'status' => 400,
                        'error'  => 'Data failed hash validation. Data might have been modified in transit.'
                    );
                }
            }

            $pageData->hash = $hash;

            // --------------------------------------------------------------------------

            /**
             * All seems good, let's process this mofo'ing data. Same format as supplied,
             * just manually specifying things for supreme consistency. Multi-pass?
             */

            $data                          = new \stdClass();
            $data->hash                    = $pageData->hash;
            $data->id                      = !empty($pageData->id) ? (int) $pageData->id : null;
            $data->data                    = new \stdClass();
            $data->data->title             = !empty($pageData->data->title) ? $pageData->data->title : '';
            $data->data->parent_id         = !empty($pageData->data->parent_id) ? (int) $pageData->data->parent_id : '';
            $data->data->seo_title         = !empty($pageData->data->seo_title) ? $pageData->data->seo_title : '';
            $data->data->seo_description   = !empty($pageData->data->seo_description) ? $pageData->data->seo_description : '';
            $data->data->seo_keywords      = !empty($pageData->data->seo_keywords) ? $pageData->data->seo_keywords : '';
            $data->data->template          = $pageData->data->template;
            $data->data->additional_fields = !empty($pageData->data->additional_fields) ? $pageData->data->additional_fields : '';
            $data->widget_areas            = !empty($pageData->widget_areas) ? $pageData->widget_areas : new \stdClass;

            if ($data->data->additional_fields) {

                parse_str($data->data->additional_fields, $_additional_fields);

                if (!empty($_additional_fields['additional_field'])) {

                    $data->data->additional_fields = $_additional_fields['additional_field'];

                } else {

                    $data->data->additional_fields = array();
                }

                /**
                 * We're going to encode then decode the additional fields, so they're
                 * consistent with the save objects
                 */

                $data->data->additional_fields = json_decode(json_encode($data->data->additional_fields));
            }

            // --------------------------------------------------------------------------

            /**
             * Data is set, determine whether we're previewing, saving or creating
             * If an ID is missing then we're creating a new page otherwise we're updating.
             */

            $this->load->model('cms/cms_page_model');

            if (!empty($generatePreview)) {

                if (!userHasPermission('admin:cms:pages:preview')) {

                    return array(
                        'status' => 400,
                        'error'  => 'You do not have permission to preview CMS Pages.'
                    );
                }

                $id = $this->cms_page_model->createPreview($data);

                if (!$id) {

                    return array(
                        'status' => 500,
                        'error'  => 'There was a problem creating the page preview. ' . $this->cms_page_model->last_error()
                    );
                }

                $out       = array();
                $out['id'] = $id;

            } else {

                if (empty($data->id)) {

                    if (!userHasPermission('admin:cms:pages:create')) {

                        return array(
                            'status' => 400,
                            'error'  => 'You do not have permission to create CMS Pages.'
                        );
                        return;
                    }

                    $id = $this->cms_page_model->create($data);

                    if (!$id) {

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

                    if ($this->cms_page_model->update($data->id, $data, $this->data)) {

                        $id = $data->id;

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

                $out       = array();
                $out['id'] = $id;

                switch ($publishAction) {

                    case 'PUBLISH':

                        $this->cms_page_model->publish($id);
                        break;

                    case 'NONE':
                    default:

                        //  Do nothing, absolutely nothing. Go have a margarita.
                        break;
                }
            }

            return $out;
        }
    }

    // --------------------------------------------------------------------------

    public function postWidgetEditor()
    {
        $out             = array();
        $requestedWidget = $this->input->post('widget');

        parse_str($this->input->post('data'), $widgetData);

        if ($requestedWidget) {

            $this->load->model('cms/cms_page_model');

            $requestedWidget = $this->cms_page_model->getWidget($requestedWidget);

            if ($requestedWidget) {

                //  Instantiate the widget
                include_once $requestedWidget->path . 'widget.php';

                try {

                    $WIDGET       = new $requestedWidget->iam();
                    $widgetEditor = $WIDGET->get_editor($widgetData);

                    if (!empty($widgetEditor)) {

                        $out['HTML'] = $widgetEditor;

                    } else {

                        $out['HTML'] = '<p class="static">This widget has no configurable options.</p>';
                    }

                } catch (Exception $e) {

                    $out['status'] = 500;
                    $out['error']  = 'This widget has not been configured correctly. Please contact the developer ';
                    $out['error'] .= 'quoting this error message: ';
                    $out['error'] .= '<strong>"#3:' . $requestedWidget->iam . ':GetEditor"</strong>';
                }

            } else {

                $out['status'] = 400;
                $out['error']  = 'Invalid Widget - Error number 2';
            }

        } else {

            $out['status'] = 400;
            $out['error']  = 'Widget slug must be specified - Error number 1';
        }

        return $out;
    }
}