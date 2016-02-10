<?php

/**
 * This class is the basic CMS widget definition
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Widget;

class WidgetBase
{
    protected static $isDisabled;

    protected $label;
    protected $icon;
    protected $description;
    protected $keywords;
    protected $grouping;
    protected $slug;
    protected $assets_editor;
    protected $assets_render;
    protected $path;
    protected $callbacks;

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
     * Constructs the widgets
     */
    public function __construct()
    {
        $this->label              = 'Widget';
        $this->icon               = 'fa-cube';
        $this->description        = '';
        $this->keywords           = '';
        $this->grouping           = '';
        $this->slug               = '';
        $this->assets_editor      = array();
        $this->assets_render      = array();
        $this->path               = '';
        $this->callbacks          = new \stdClass();
        $this->callbacks->dropped = '';
        $this->callbacks->removed = '';

        //  Autodetect some values
        $oReflect = new \ReflectionClass(get_called_class());

        //  Path
        $this->path = dirname($oReflect->getFileName()) . '/';

        //  Slug - this should uniquely identify the widget
        $this->slug = pathinfo($this->path);
        $this->slug = $this->slug['basename'];

        //  Callbacks - attempt to auto-populate
        foreach ($this->callbacks as $sProperty => &$sCallback) {

            if (is_file($this->path . 'js/' . $sProperty . '.min.js')) {

                $sCallback = file_get_contents($this->path . 'js/' . $sProperty . '.min.js');

            } elseif (is_file($this->path . 'js/' . $sProperty . '.js')) {

                $sCallback = file_get_contents($this->path . 'js/' . $sProperty . '.js');
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's label
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's icon
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's description
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's keywords
     * @return array
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's grouping
     * @return string
     */
    public function getGrouping()
    {
        return $this->grouping;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's slug
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's path
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's assets
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
     * Returns the widget's callbacks
     * @return mixed
     */
    public function getCallbacks($sType = '')
    {
        if (property_exists($this->callbacks, $sType)) {

            return $this->callbacks->{$sType};

        } else {

            return $this->callbacks;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the HTML for the editor view. Any passed data will be used to
     * populate the values of the form elements.
     * @param  array  $aWidgetData A normal widget_data object, prefixed to avoid naming collisions
     * @return string
     */
    public function getEditor($aWidgetData = array())
    {
        if (is_file($this->path . 'views/editor.php')) {

            //  Add a reference to the CI super object, for view loading etc
            $oCi = get_instance();

            //  Extract the variables, so that the view can use them
            if ($aWidgetData) {

                extract($aWidgetData);
            }

            //  Start the buffer, basically copying how CI does it's view loading
            ob_start();

            include $this->path . 'views/editor.php';

            //  Flush buffer
            $sBuffer = ob_get_contents();
            @ob_end_clean();

            //  Return the HTML
            return $sBuffer;
        }

        return '';
    }

    // --------------------------------------------------------------------------

    /**
     * Format the widget as a JSON object
     * @return string
     */
    public function toJson($iJsonOptions = 0, $iJsonDepth = 512)
    {
        $oWidget                           = new \stdClass();
        $oWidget->label                    = $this->getLabel();
        $oWidget->icon                     = $this->getIcon();
        $oWidget->description              = $this->getDescription();
        $oWidget->keywords                 = $this->getKeywords();
        $oWidget->grouping                 = $this->getGrouping();
        $oWidget->slug                     = $this->getSlug();
        $oWidget->assets_editor            = $this->getAssets('EDITOR');
        $oWidget->assets_render            = $this->getAssets('RENDER');
        $oWidget->path                     = $this->getPath();
        $oWidget->callbacks                = $this->getCallbacks();

        return json_encode($oWidget, $iJsonOptions, $iJsonDepth);
    }

    // --------------------------------------------------------------------------

    /**
     * Format the widget as an array
     * @return string
     */
    public function toArray()
    {
        $aWidget                             = array();
        $aWidget['label']                    = $this->getLabel();
        $aWidget['icon']                     = $this->getIcon();
        $aWidget['description']              = $this->getDescription();
        $aWidget['keywords']                 = $this->getKeywords();
        $aWidget['grouping']                 = $this->getGrouping();
        $aWidget['slug']                     = $this->getSlug();
        $aWidget['assets_editor']            = $this->getAssets('EDITOR');
        $aWidget['assets_render']            = $this->getAssets('RENDER');
        $aWidget['path']                     = $this->getPath();
        $aWidget['callbacks']                = $this->getCallbacks();

        return $aWidget;
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the widget with the provided data.
     * @param  array  $aWidgetData The widgets to include in the template
     * @return string
     */
    public function render($aWidgetData = array())
    {
        if (is_file($this->path . 'views/render.php')) {

            //  Add a reference to the CI super object, for view loading etc
            $oCi = get_instance();

            /**
             * Extract data into variables in the local scope so the view can use them.
             * Basically copying how CI does it's view loading/rendering
             */
            $NAILS_CONTROLLER_DATA =& getControllerData();
            if ($NAILS_CONTROLLER_DATA) {
                extract($NAILS_CONTROLLER_DATA);
            }

            if (!is_array($aWidgetData)) {
                $aWidgetData = (array) $aWidgetData;
            }

            if ($aWidgetData) {
                extract($aWidgetData);
            }

            ob_start();
            include $this->path . 'views/render.php';
            $sBuffer = ob_get_contents();
            @ob_end_clean();

            return $sBuffer;
        }

        return '';
    }
}
