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
    protected $description;
    protected $keywords;
    protected $grouping;
    protected $slug;
    protected $restricted_to_template;
    protected $restricted_to_area;
    protected $restricted_from_template;
    protected $restricted_from_area;
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
        $this->label                    = 'Widget';
        $this->description              = '';
        $this->keywords                 = '';
        $this->grouping                 = '';
        $this->slug                     = '';
        $this->restricted_to_template   = array();
        $this->restricted_to_area       = array();
        $this->restricted_from_template = array();
        $this->restricted_from_area     = array();
        $this->assets_editor            = array();
        $this->assets_render            = array();
        $this->path                     = '';
        $this->callbacks                = new \stdClass();
        $this->callbacks->dropped       = '';
        $this->callbacks->sort_start    = '';
        $this->callbacks->sort_stop     = '';
        $this->callbacks->remove_start  = '';
        $this->callbacks->remove_stop   = '';

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
     * @return string
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

            return $this->assets_render;

        } elseif ($sType == 'RENDER') {

            return $this->assets_render;

        } else {

            return array();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the areas or templates this widget is restricted to
     * @return array
     */
    public function getRestrictedTo($sType)
    {
        if ($sType == 'TEMPLATE') {

            return $this->restricted_to_template;

        } elseif ($sType == 'AREA') {

            return $this->restricted_to_area;

        } else {

            return array();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the areas or templates this widget is restrictedfrom
     * @return array
     */
    public function getRestrictedFrom($sType)
    {
        if ($sType == 'TEMPLATE') {

            return $this->restricted_from_template;

        } elseif ($sType == 'AREA') {

            return $this->restricted_from_area;

        } else {

            return array();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's callbacks
     * @return mixed
     */
    public function getCallbacks($sType)
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
     * Renders the widget with the provided data.
     * @param  array  $aWidgetData             The widgets to include in the template
     * @param  array  $aTemplateAdditionalData Additional data created by the template
     * @return string
     */
    public function render($aWidgetData = array(), $aTemplateAdditionalData = array())
    {
        /**
         * If the template wishes to execute any custom pre/post code then this method
         * should be extended and parent::render($data) called at the appropriate point.
         * But that's obvious, isn't it...?
         */

        if (is_file($this->path . 'views/render.php')) {

            //  If passed, extract any controller data
            $NAILS_CONTROLLER_DATA =& getControllerData();

            if ($NAILS_CONTROLLER_DATA) {

                extract($NAILS_CONTROLLER_DATA);
            }

            //  Extract the variables, so that the view can use them
            if ($aWidgetData) {

                extract($aWidgetData);
            }

            if ($aTemplateAdditionalData) {

                extract($aTemplateAdditionalData);
            }

            //  Start the buffer, basically copying how CI does it's view loading
            ob_start();

            include $this->path . 'views/render.php';

            //  Flush buffer
            $sBuffer = ob_get_contents();
            @ob_end_clean();

            //  Return the HTML
            return $sBuffer;
        }

        return '';
    }
}
