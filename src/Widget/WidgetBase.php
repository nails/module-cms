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

abstract class WidgetBase
{
    /**
     * Whether this widget is disabled or not
     */
    const DISABLED = false;

    // --------------------------------------------------------------------------

    protected $label;
    protected $icon;
    protected $description;
    protected $keywords;
    protected $grouping;
    protected $slug;
    protected $screenshot;
    protected $assets_editor;
    protected $assets_render;
    protected $path;
    protected $callbacks;

    // --------------------------------------------------------------------------

    /**
     * Returns whether the widget is disabled
     * @return bool
     */
    public static function isDisabled()
    {
        return !empty(static::DISABLED);
    }

    // --------------------------------------------------------------------------

    /**
     * Constructs the widgets
     */
    public function __construct()
    {
        $this->label         = 'Widget';
        $this->icon          = 'fa-cube';
        $this->description   = '';
        $this->keywords      = '';
        $this->grouping      = '';
        $this->slug          = '';
        $this->screenshot    = '';
        $this->assets_editor = [];
        $this->assets_render = [];
        $this->path          = '';
        $this->callbacks     = (object) [
            'dropped' => '',
            'removed' => '',
        ];

        //  Detect the path
        $sCalledClass = get_called_class();
        $this->path   = $sCalledClass::detectPath();

        //  Slug - this should uniquely identify the widget
        $this->slug = pathinfo($this->path);
        $this->slug = $this->slug['basename'];

        //  Detect the screenshot
        if (is_file($this->path . 'screenshot.png')) {
            $this->screenshot = 'data:image/jpg;base64,' . base64_encode(file_get_contents($this->path . 'screenshot.png'));
        } elseif (is_file($this->path . 'screenshot.jpg')) {
            $this->screenshot = 'data:image/jpg;base64,' . base64_encode(file_get_contents($this->path . 'screenshot.jpg'));
        }

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
     * Detects the path of the called class
     * @return string
     */
    public static function detectPath()
    {
        $oReflect = new \ReflectionClass(get_called_class());
        return dirname($oReflect->getFileName()) . '/';
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
     * Returns the widget's screenshot
     * @return string
     */
    public function getScreenshot()
    {
        return $this->screenshot;
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
     *
     * @param string $sType the type of assets to return
     *
     * @return array
     */
    public function getAssets($sType)
    {
        if ($sType == 'EDITOR') {
            return $this->assets_editor;
        } elseif ($sType == 'RENDER') {
            return $this->assets_render;
        } else {
            return [];
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's callbacks
     *
     * @param string $sType the type of callback to return
     *
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
     *
     * @param  array $aWidgetData The data to render the widget editor with
     *
     * @return string
     */
    public function getEditor($aWidgetData = [])
    {
        return $this->loadView('editor', $aWidgetData);
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the widget with the provided data.
     *
     * @param  array $aWidgetData The data to render the widget with
     *
     * @return string
     */
    public function render($aWidgetData = [])
    {
        return $this->loadView('render', $aWidgetData, true);
    }

    // --------------------------------------------------------------------------

    /**
     * Load a specific view
     *
     * @param string $sView                  The view to load
     * @param array  $aWidgetData            The data to render the view with
     * @param bool   $bExtractControllerData Whether to extract controller data or not
     *
     * @return string
     */
    protected function loadView($sView, array $aWidgetData, $bExtractControllerData = false)
    {
        //  Look for the view in the [potential] class hierarchy
        $aClasses = array_filter(
            array_merge(
                [get_called_class()],
                array_values(class_parents(get_called_class()))
            )
        );

        foreach ($aClasses as $sClass) {

            $sPath = $sClass::detectPath();

            if (is_file($sPath . 'views/' . $sView . '.php')) {

                //  Populate widget data
                $this->populateWidgetData($aWidgetData);

                //  Add a reference to the CI super object, for view loading etc
                $oCi = get_instance();

                /**
                 * Extract data into variables in the local scope so the view can use them.
                 * Basically copying how CI does it's view loading/rendering
                 */
                if ($bExtractControllerData) {
                    $NAILS_CONTROLLER_DATA =& getControllerData();
                    if ($NAILS_CONTROLLER_DATA) {
                        extract($NAILS_CONTROLLER_DATA);
                    }
                }

                if ($aWidgetData) {
                    extract((array) $aWidgetData);
                }

                ob_start();
                include $sPath . 'views/' . $sView . '.php';
                $sBuffer = ob_get_contents();
                @ob_end_clean();

                return $sBuffer;
            }
        }
        return '';
    }

    // --------------------------------------------------------------------------

    /**
     * Can be used to ensure that $aWidgetData has fields defined in both the editor
     * and render views.
     *
     * @param array $aWidgetData The widget's data
     */
    protected function populateWidgetData(&$aWidgetData)
    {
    }

    // --------------------------------------------------------------------------

    /**
     * Format the widget as a JSON object
     *
     * @param int $iJsonOptions
     * @param int $iJsonDepth
     *
     * @return string
     */
    public function toJson($iJsonOptions = 0, $iJsonDepth = 512)
    {
        return json_encode($this->toArray(), $iJsonOptions, $iJsonDepth);
    }

    // --------------------------------------------------------------------------

    /**
     * Format the widget as an array
     * @return array
     */
    public function toArray()
    {
        return [
            'label'         => $this->getLabel(),
            'icon'          => $this->getIcon(),
            'description'   => $this->getDescription(),
            'keywords'      => $this->getKeywords(),
            'grouping'      => $this->getGrouping(),
            'slug'          => $this->getSlug(),
            'screenshot'    => $this->getScreenshot(),
            'assets_editor' => $this->getAssets('EDITOR'),
            'assets_render' => $this->getAssets('RENDER'),
            'path'          => $this->getPath(),
            'callbacks'     => $this->getCallbacks(),
        ];
    }
}
