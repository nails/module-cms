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
     * Whether the widget is deprecated
     *
     * @var string
     */
    const DEPRECATED = false;

    /**
     * If widget is deprecated, suggest alternative
     *
     * @var string
     */
    const ALTERNATIVE = '';

    /**
     * The default icon to use
     *
     * @var string
     */
    const DEFAULT_ICON = 'fa-cube';

    // --------------------------------------------------------------------------

    /**
     * The widget's label
     *
     * @var string
     */
    protected $label = '';

    /**
     * The widget's icon
     *
     * @var string
     */
    protected $icon = '';

    /**
     * The widget's description
     *
     * @var string
     */
    protected $description = '';

    /**
     * The widget's keywords
     *
     * @var string
     */
    protected $keywords = '';

    /**
     * The widget's grouping
     *
     * @var string
     */
    protected $grouping = '';

    /**
     * The widget's slug
     *
     * @var string
     */
    protected $slug = '';

    /**
     * The widget's screenshot
     *
     * @var string
     */
    protected $screenshot = '';

    /**
     * The widget's assets_editor
     *
     * @var array
     */
    protected $assets_editor = [];

    /**
     * The widget's assets_render
     *
     * @var array
     */
    protected $assets_render = [];

    /**
     * The widget's path
     *
     * @var string
     */
    protected $path = '';

    /**
     * The widget's callbacks
     *
     * @var object
     */
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
     * Whether the widget is deprecated or not
     *
     * @return bool
     */
    public static function isDeprecated(): bool
    {
        return static::DEPRECATED;
    }

    // --------------------------------------------------------------------------

    /**
     * When deprecated, an alternative widget to use
     *
     * @return string
     */
    public static function alternative(): string
    {
        return static::ALTERNATIVE;
    }

    // --------------------------------------------------------------------------

    /**
     * Constructs the widgets
     */
    public function __construct()
    {
        $this->label     = 'Widget';
        $this->callbacks = (object) [
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
    public static function detectPath(): string
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
     * @return string|null
     */
    public static function getFilePath($sFile): ?string
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
    public function getLabel(): string
    {
        return $this->label;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's icon
     *
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon ?: static::DEFAULT_ICON;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's keywords
     *
     * @return string
     */
    public function getKeywords(): string
    {
        return $this->keywords;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's grouping
     *
     * @return string
     */
    public function getGrouping(): string
    {
        return $this->grouping;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's slug
     *
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's screenshot
     *
     * @return string
     */
    public function getScreenshot(): string
    {
        return $this->screenshot;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's path
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's assets
     *
     * @param string $sType The type of assets to return
     *
     * @return string[]
     */
    protected function getAssets(string $sType): array
    {
        if ($sType === static::ASSETS_EDITOR) {
            return $this->assets_editor;

        } elseif ($sType === static::ASSETS_RENDER) {
            return $this->assets_render;
        }

        return [];
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's render assets
     *
     * @return string[]
     */
    public function getRenderAssets(): array
    {
        return $this->getAssets(static::ASSETS_RENDER);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's editor assets
     *
     * @return string[]
     */
    public function getEditorAssets(): array
    {
        return $this->getAssets(static::ASSETS_EDITOR);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's callbacks
     *
     * @param string $sType The type of callback to return
     *
     * @return mixed
     */
    public function getCallbacks(string $sType = '')
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
    public function getEditor(array $aWidgetData = []): string
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
    public function render(array $aWidgetData = []): string
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
    public function toJson(int $iJsonOptions = 0, int $iJsonDepth = 512)
    {
        return json_encode($this->toArray(), $iJsonOptions, $iJsonDepth);
    }

    // --------------------------------------------------------------------------

    /**
     * Format the widget as an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'label'         => $this->getLabel(),
            'icon'          => $this->getIcon(),
            'description'   => $this->getDescription(),
            'keywords'      => $this->getKeywords(),
            'grouping'      => $this->getGrouping(),
            'slug'          => $this->getSlug(),
            'screenshot'    => $this->getScreenshot(),
            'assets_editor' => $this->getEditorAssets(),
            'assets_render' => $this->getRenderAssets(),
            'path'          => $this->getPath(),
            'callbacks'     => $this->getCallbacks(),
            'is_deprecated' => static::isDeprecated(),
            'alternative'   => static::alternative(),
        ];
    }
}
