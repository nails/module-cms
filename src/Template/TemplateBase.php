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

use Nails\Factory;

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
        $oTemplate = new \stdClass();
        $oTemplate->label = $this->getLabel();
        $oTemplate->description = $this->getDescription();
        $oTemplate->description = $this->getDescription();
        $oTemplate->widget_areas = $this->getWidgetAreas();
        $oTemplate->additional_fields = $this->getAdditionalFields();
        $oTemplate->manual_config = $this->getManualConfig();
        $oTemplate->icon = $this->getIcon();
        $oTemplate->slug = $this->getSlug();
        $oTemplate->assets_editor = $this->getAssets('EDITOR');
        $oTemplate->assets_render = $this->getAssets('RENDER');
        $oTemplate->path = $this->getPath();

        return json_encode($oTemplate, $iJsonOptions, $iJsonDepth);
    }

    // --------------------------------------------------------------------------

    /**
     * Format the template as an array
     * @return string
     */
    public function toArray()
    {
        $aTemplate = array();
        $aTemplate['label'] = $this->getLabel();
        $aTemplate['description'] = $this->getDescription();
        $aTemplate['description'] = $this->getDescription();
        $aTemplate['widget_areas'] = $this->getWidgetAreas();
        $aTemplate['additional_fields'] = $this->getAdditionalFields();
        $aTemplate['manual_config'] = $this->getManualConfig();
        $aTemplate['icon'] = $this->getIcon();
        $aTemplate['slug'] = $this->getSlug();
        $aTemplate['assets_editor'] = $this->getAssets('EDITOR');
        $aTemplate['assets_render'] = $this->getAssets('RENDER');
        $aTemplate['path'] = $this->getPath();

        return $aTemplate;
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the template with the provided data.
     * @param  array  $aTplWidgets          The widgets to include in the template
     * @param  array  $aTplAdditionalFields Additional data created by the template
     * @return string
     */
    public function render($aTplWidgets = array(), $aTplAdditionalFields = array())
    {
        /**
         * If the template wishes to execute any custom pre/post code then this method
         * should be extended and parent::render($data) called at the appropriate point.
         * But that's obvious, isn't it...?
         */

        // --------------------------------------------------------------------------

        //  Process each widget area and render the HTML
        $aWidgetAreas   = $this->getWidgetAreas();
        $aRenderedAreas = array();
        $oWidgetModel   = Factory::model('Widget', 'nailsapp/module-cms');

        foreach ($aWidgetAreas as $sAreaSlug => $oAreaData) {

            $aRenderedAreas[$sAreaSlug] = '';

            if (!empty($aTplWidgets[$sAreaSlug])) {

                foreach ($aTplWidgets[$sAreaSlug] as $oWidgetData) {

                    $oWidget = $oWidgetModel->getBySlug($oWidgetData->widget, 'RENDER');
                    if ($oWidget) {

                        parse_str($oWidgetData->data, $aWidgetData);

                        $aRenderedAreas[$sAreaSlug] .= $oWidget->render($aWidgetData, $aTplAdditionalFields);
                    }
                }
            }
        }

        // --------------------------------------------------------------------------

        if (is_file($this->path . 'view.php')) {

            //  Add a reference to the CI super object, for view loading etc
            $oCi = get_instance();

            //  Get controller data, so that headers etc behave as expected
            $NAILS_CONTROLLER_DATA =& getControllerData();
            if ($NAILS_CONTROLLER_DATA) {

                extract($NAILS_CONTROLLER_DATA);
            }

            //  If passed, extract any $aTplAdditionalFields
            if ($aTplAdditionalFields) {

                extract($aTplAdditionalFields);
            }

            //  Extract the variables, so that the view can use them
            if ($aRenderedAreas) {

                extract($aRenderedAreas);
            }

            //  Start the buffer, basically copying how CI does it's view loading
            ob_start();

            include $this->path . 'view.php';

            //  Flush buffer
            $buffer = ob_get_contents();
            @ob_end_clean();

            //  Look for blocks
            preg_match_all('/\[:([a-zA-Z0-9\-]+?):\]/', $buffer, $matches);

            if ($matches[0]) {

                //  Get all the blocks which were found
                $oBlockModel = Factory::model('Block', 'nailsapp/module-cms');
                $aBlocks     = $oBlockModel->get_by_slugs($matches[1]);

                //  Swap them in
                if ($aBlocks) {
                    foreach ($aBlocks as $oBlock) {

                        //  Translate some block types
                        switch ($oBlock->type) {
                            case 'file':
                            case 'image':

                                $oBlock->value = cdnServe($oBlock->value);
                                break;
                        }

                        $buffer = str_replace('[:' . $oBlock->slug . ':]', $oBlock->value, $buffer);
                    }
                }

                //  Swap page variables
                $sPageTitle    = !empty($tplAdditionalFields['cmspage']) ? $tplAdditionalFields['cmspage']->title : '';
                $pageShortTags = array(
                    'page-title' => $sPageTitle
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
