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

use Nails\Cms\Interfaces;
use Nails\Common\Service\FileCache;
use Nails\Factory;

/**
 * Class WidgetBase
 *
 * @package Nails\Cms\Widget
 */
abstract class WidgetBase implements Interfaces\Widget
{
    /**
     * Whether this widget is disabled or not
     *
     * @var bool
     */
    const DISABLED = false;

    /**
     * Whether this widget is hidden or not. Hidden widgets can still be used, but will not be offered for use in the editor
     */
    const HIDDEN = false;

    /**
     * The default icon to use
     *
     * @var string
     */
    const DEFAULT_ICON = 'fa-cube';

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
     *
     * @return bool
     */
    public static function isDisabled(): bool
    {
        return !empty(static::DISABLED);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns whether the widget is hidden
     *
     * @return bool
     */
    public static function isHidden(): bool
    {
        return !empty(static::HIDDEN);
    }

    // --------------------------------------------------------------------------

    /**
     * Constructs the widgets
     */
    public function __construct()
    {
        $this->label         = 'Widget';
        $this->icon          = '';
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
        $aFiles = ['screenshot.png', 'screenshot.jpg', 'screenshot.gif'];

        /** @var FileCache $oFileCache */
        $oFileCache = Factory::service('FileCache');
        $sCacheDir  = $oFileCache->public()->getDir();
        $sCacheUrl  = $oFileCache->public()->getUrl() . '/';

        foreach ($aFiles as $sFile) {
            $sPath = static::getFilePath($sFile);
            if (!empty($sPath)) {
                $sCachePath = md5($sPath) . '-widget-' . basename($this->path) . '-' . basename($sPath);

                if (file_exists($sCacheDir . $sCachePath)) {
                    $this->screenshot = $sCacheUrl . $sCachePath;
                } else {
                    //  Attempt to copy the file and serve the cached version
                    if (copy($sPath, $sCacheDir . $sCachePath)) {
                        $this->screenshot = $sCacheUrl . $sCachePath;
                    } else {
                        $this->screenshot = 'data:image/jpg;base64,' . base64_encode(file_get_contents($sPath));
                    }
                }
            }
        }

        //  Callbacks - attempt to auto-populate
        foreach ($this->callbacks as $sProperty => &$sCallback) {
            $aFiles = ['js/' . $sProperty . '.min.js', 'js/' . $sProperty . '.js'];
            foreach ($aFiles as $sFile) {
                $sPath = static::getFilePath($sFile);
                if (!empty($sPath)) {
                    $sCallback = file_get_contents($sPath);
                }
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Detects the path of the called class
     *
     * @return string
     */
    public static function detectPath()
    {
        $oReflect = new \ReflectionClass(get_called_class());
        return dirname($oReflect->getFileName()) . '/';
    }

    // --------------------------------------------------------------------------

    /**
     * Looks for a file in the widget hierarchy and returns it if found
     *
     * @param string $sFile The file name to look for
     *
     * @return null|string
     */
    public static function getFilePath($sFile)
    {
        //  Look for the file in the [potential] class hierarchy
        $aClasses = array_filter(
            array_merge(
                [get_called_class()],
                array_values(class_parents(get_called_class()))
            )
        );

        foreach ($aClasses as $sClass) {
            $sPath = $sClass::detectPath();
            if (is_file($sPath . $sFile)) {
                return $sPath . $sFile;
            }
        }

        return null;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's icon
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon ?: static::DEFAULT_ICON;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's keywords
     *
     * @return string
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's grouping
     *
     * @return string
     */
    public function getGrouping()
    {
        return $this->grouping;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's screenshot
     *
     * @return string
     */
    public function getScreenshot()
    {
        return $this->screenshot;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's path
     *
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
     * @param array $aWidgetData The data to render the widget editor with
     *
     * @return string
     */
    public function getEditor(array $aWidgetData = [])
    {
        return $this->loadView('editor', $aWidgetData);
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the widget with the provided data.
     *
     * @param array $aWidgetData The data to render the widget with
     *
     * @return string
     */
    public function render(array $aWidgetData = [])
    {
        return $this->loadView('render', $aWidgetData, true);
    }

    // --------------------------------------------------------------------------

    /**
     * Load a specific view
     *
     * @param string $sView       The view to load
     * @param array  $aWidgetData The data to render the view with
     *
     * @return string
     */
    protected function loadView($sView, array $aWidgetData)
    {
        $sPath = static::getFilePath('views/' . $sView . '.php');
        if (!empty($sPath)) {
            $this->populateWidgetData($aWidgetData);
            return Factory::service('View')->load($sPath, $aWidgetData, true);
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
    protected function populateWidgetData(array &$aWidgetData)
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
     *
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
