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

namespace Nails\Cms\Template;

class TemplateBase
{
    protected static $isDisabled;
    protected static $isDefault;

    protected $label;
    protected $description;
    protected $grouping;
    protected $widget_areas;
    protected $additional_fields;
    protected $manual_config;
    protected $icon;
    protected $slug;
    protected $assets_editor;
    protected $assets_render;
    protected $path;

    // --------------------------------------------------------------------------

    /**
     * Returns whether the template is disabled
     * @return bool
     */
    public static function isDisabled()
    {
        return !empty(static::$isDisabled);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns whether the template is a default template or not
     * @return bool
     */
    public static function isDefault()
    {
        return !empty(static::$isDefault);
    }

    // --------------------------------------------------------------------------

    /**
     * Constructs the template
     */
    public function __construct()
    {
        $this->label             = 'Template';
        $this->description       = '';
        $this->widget_areas      = array();
        $this->additional_fields = array();
        $this->manual_config     = null;
        $this->icon              = null;
        $this->slug              = '';
        $this->assets_editor     = array();
        $this->assets_render     = array();
        $this->path              = '';

        // --------------------------------------------------------------------------

        //  Autodetect some values
        $oReflect = new \ReflectionClass(get_called_class());

        //  Path
        $this->path = dirname($oReflect->getFileName()) . '/';

        //  Icon
        $aExtensions = array('png','jpg','jpeg','gif');

        foreach ($aExtensions as $sExtension) {

            $sIconPath = $this->path  . 'icon.' . $sExtension;

            if (is_file($sIconPath)) {

                if (preg_match('#^' . preg_quote(NAILS_PATH, '#') . '#', $sIconPath)) {

                    //  Nails asset
                    $this->icon = preg_replace('#^' . preg_quote(NAILS_PATH, '#') . '#', NAILS_URL, $sIconPath);

                } elseif (preg_match('#^' . preg_quote(FCPATH . APPPATH, '#') . '#', $sIconPath)) {

                    if (isPageSecure()) {

                        $sPattern   = '#^' . preg_quote(FCPATH . APPPATH, '#') . '#';
                        $this->icon = preg_replace($sPattern, SECURE_BASE_URL . APPPATH . '', $sIconPath);

                    } else {

                        $sPattern   = '#^' . preg_quote(FCPATH . APPPATH, '#') . '#';
                        $this->icon = preg_replace($sPattern, BASE_URL . APPPATH . '', $sIconPath);
                    }
                }
                break;
            }
        }

        //  Slug - this should uniquely identify a type of template
        $this->slug = pathinfo($this->path);
        $this->slug = $this->slug['basename'];
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the template's label
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the template's description
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the template's grouping
     * @return string
     */
    public function getGrouping()
    {
        return $this->grouping;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the template's widget areas
     * @return string
     */
    public function getWidgetAreas()
    {
        return $this->widget_areas;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the template's additional fields
     * @return string
     */
    public function getAdditionalFields()
    {
        return $this->additional_fields;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the template's manual config
     * @return string
     */
    public function getManualConfig()
    {
        return $this->manual_config;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the template's icon
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the template's slug
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the template's path
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the template's assets
     * @return array
     */
    public function getAssets($sType)
    {
        if ($sType == 'EDITOR') {

            return $this->assets_editor;

        } elseif ($sType == 'RENDER') {

            return $this->assets_render;

        } else {

            return array();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Format the template as a JSON object
     * @return string
     */
    public function toJson($iJsonOptions = 0, $iJsonDepth = 512)
    {
        $oObj = new \stdClass();
        $oObj->label = $this->getLabel();
        $oObj->description = $this->getDescription();
        $oObj->description = $this->getDescription();
        $oObj->widget_areas = $this->getWidgetAreas();
        $oObj->additional_fields = $this->getAdditionalFields();
        $oObj->manual_config = $this->getManualConfig();
        $oObj->icon = $this->getIcon();
        $oObj->slug = $this->getSlug();
        $oObj->assets_editor = $this->getAssets('EDITOR');
        $oObj->assets_render = $this->getAssets('RENDER');
        $oObj->path = $this->getPath();

        return json_encode($oObj, $iJsonOptions, $iJsonDepth);
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
        die('todo');
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

                    } catch (\Exception $e) {

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
