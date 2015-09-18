<?php

/**
 * This class is the basic CMS template definition
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Template
 * @author      Nails Dev Team
 * @link
 */

class Nails_CMS_Template
{
    protected $details;
    protected $load;
    protected $data;

    // --------------------------------------------------------------------------

    /**
     * Defines the basic template details object. Templates should extend this
     * object and customise to it's own needs.
     * @return stdClass
     */
    public static function details()
    {
        $d      = new stdClass();
        $d->iam = get_called_class();

        $reflect = new ReflectionClass($d->iam);

        //  The human friendly name of this template
        $d->label = 'Widget';

        //  A brief description of the template, optional
        $d->description = '';

        /**
         * Any additional fields to request
         * @TODO: use the form builder library when it exists
         */

        $d->additional_fields = array();

        //  Empty manual_config object
        $d->manual_config = '';

        //  An icon/preview to render
        $d->img       = new stdClass();
        $d->img->icon = '';

        //  Try to detect the icon
        $extensions = array('png','jpg','jpeg','gif');

        $path = $reflect->getFileName();
        $path = dirname($path);

        foreach ($extensions as $ext) {

            $icon = $path . '/icon.' . $ext;

            if (is_file($icon)) {

                $url = '';
                if (preg_match('#^' . preg_quote(NAILS_PATH, '#') . '#', $icon)) {

                    //  Nails asset
                    $d->img->icon = preg_replace('#^' . preg_quote(NAILS_PATH, '#') . '#', NAILS_URL, $icon);

                } elseif (preg_match('#^' . preg_quote(FCPATH . APPPATH, '#') . '#', $icon)) {

                    if (isPageSecure()) {

                        $pattern = '#^' . preg_quote(FCPATH . APPPATH, '#') . '#';
                        $d->img->icon = preg_replace($pattern, SECURE_BASE_URL . APPPATH . '', $icon);

                    } else {

                        $pattern = '#^' . preg_quote(FCPATH . APPPATH, '#') . '#';
                        $d->img->icon = preg_replace($pattern, BASE_URL . APPPATH . '', $icon);
                    }
                }
                break;
            }
        }

        //  An array of the widget-able areas
        $d->widget_areas = array();

        // --------------------------------------------------------------------------

        //  Automatically calculated properties
        $d->slug = '';

        // --------------------------------------------------------------------------

        //  Work out slug - this should uniquely identify a type of template
        $d->slug = $reflect->getFileName();
        $d->slug = pathinfo($d->slug);
        $d->slug = explode('/', $d->slug['dirname']);
        $d->slug = array_pop($d->slug);

        // --------------------------------------------------------------------------

        //  Define any assets need to be loaded by the template
        $d->assets_editor = array();
        $d->assets_render = array();

        // --------------------------------------------------------------------------

        //  Path
        $d->path = dirname($reflect->getFileName()) . '/';

        // --------------------------------------------------------------------------

        //  Return the D
        return $d;
    }

    // --------------------------------------------------------------------------

    /**
     * Defines the base widget area object. Each editable area needs to have certain
     * properties defined. The template clone this object for each area and set the
     * values appropriately.
     * @return stdClass
     */
    protected static function editableAreaTemplate()
    {
        $d              = new stdClass();
        $d->title       = '';
        $d->description = '';
        $d->view        = '';

        return $d;
    }

    // --------------------------------------------------------------------------

    /**
     * Constructs the template and sets the templates details as a class variable
     */
    public function __construct()
    {
        $this->details = $this::details();
        $this->load =& get_instance()->load;
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the template with the provided data.
     * @param  array  $tplWidgets          The widgets to include in the template
     * @param  array  $tplAdditionalFields Additional data created by the template
     * @return string
     */
    public function render($tplWidgets = array(), $tplAdditionalFields = array())
    {
        /**
         * If the template wishes to execute any custom pre/post code then this method
         * should be extended and parent::render($data) called at the appropriate point.
         * But that's obvious, isn't it...?
         */

        get_instance()->load->model('cms/cms_page_model');

        // --------------------------------------------------------------------------

        //  Process each widget area and render the HTML
        $widgetAreas = array();
        foreach ($this->details->widget_areas as $key => $details) {

            $widgetAreas[$key] = '';

            //  Loop through all defined widgets and render each one
            if (!empty($tplWidgets[$key])) {

                foreach ($tplWidgets[$key] as $widget_data) {

                    try {

                        $widget = get_instance()->cms_page_model->getWidget($widget_data->widget, 'RENDER');

                        if ($widget) {

                            parse_str($widget_data->data, $data);

                            $WIDGET = new $widget->iam();
                            $widgetAreas[$key] .= $WIDGET->render($data, $tplAdditionalFields);
                        }

                    } catch (Exception $e) {

                        log_message('error', 'Failed to render widget');
                    }
                }
            }
        }

        // --------------------------------------------------------------------------

        if (is_file($this->details->path . 'view.php')) {

            //  Get controller data, so that headers etc behave as expected
            $NAILS_CONTROLLER_DATA =& getControllerData();
            if ($NAILS_CONTROLLER_DATA) {

                extract($NAILS_CONTROLLER_DATA);
            }

            //  If passed, extract any $tplAdditionalFields
            if ($tplAdditionalFields) {

                extract($tplAdditionalFields);
            }

            //  Extract the variables, so that the view can use them
            if ($widgetAreas) {

                extract($widgetAreas);
            }

            //  Start the buffer, basically copying how CI does it's view loading
            ob_start();

            include $this->details->path . 'view.php';

            //  Flush buffer
            $buffer = ob_get_contents();
            @ob_end_clean();

            //  Look for blocks
            preg_match_all('/\[:([a-zA-Z\-]+?):\]/', $buffer, $matches);

            if ($matches[0]) {

                //  Get all the blocks which were found
                get_instance()->load->model('cms_block_model');
                $blocks = get_instance()->cms_block_model->get_by_slugs($matches[1]);

                //  Swap them in
                if ($blocks) {
                    foreach ($blocks as $block) {

                        //  Translate some block types
                        switch ($block->type) {
                            case 'file':
                            case 'image':

                                get_instance()->load->helper('cdn_helper');
                                $block->value = cdnServe($block->value);
                                break;
                        }

                        $buffer = str_replace('[:' . $block->slug . ':]', $block->value, $buffer);
                    }
                }

                //  Swap page variables
                $pageShortTags = array(
                    'page-title' => $tplAdditionalFields['cmspage']->title
                );

                foreach ($pageShortTags as $shortTag => $value) {

                    $buffer = str_replace('[:' . $shortTag . ':]', $value, $buffer);
                }
            }

            //  Return the HTML
            return $buffer;
        }

        return '';
    }
}
