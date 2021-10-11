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

use Nails\Cms\Constants;
use Nails\Cms\Interfaces\Template;
use Nails\Config;
use Nails\Factory;
use Nails\Functions;

/**
 * Class TemplateBase
 *
 * @package Nails\Cms\Template
 */
abstract class TemplateBase implements Template
{
    /**
     * Whether the template is enabled or not
     *
     * @var bool
     */
    const DISABLED = false;

    /**
     * Whether the template is deprecated
     *
     * @var bool
     */
    const DEPRECATED = false;

    /**
     * If template is deprecated, suggest alternative
     *
     * @var string
     */
    const ALTERNATIVE = '';

    // --------------------------------------------------------------------------

    /**
     * Whether the template is the default template
     *
     * @var bool
     */
    protected static $isDefault = false;

    /**
     * The template's label
     *
     * @var string
     */
    protected $label = '';

    /**
     * The template's description
     *
     * @var string
     */
    protected $description = '';

    /**
     * The template's grouping
     *
     * @var string
     */
    protected $grouping = '';

    /**
     * The available widget areas
     *
     * @var array
     */
    protected $widget_areas = [];

    /**
     * Additional fields to make available
     *
     * @var array
     */
    protected $additional_fields = [];

    /**
     * Any manual config items to pass in
     *
     * @var string
     */
    protected $manual_config = '';

    /**
     * The template's icon
     *
     * @var string
     */
    protected $icon = '';

    /**
     * The template's slug
     *
     * @var string
     */
    protected $slug = '';

    /**
     * Assets to load when in the editor
     *
     * @var array
     */
    protected $assets_editor = [];

    /**
     * Assets to load when rendering
     *
     * @var array
     */
    protected $assets_render = [];

    /**
     * The template's path
     *
     * @var string
     */
    protected $path = '';

    // --------------------------------------------------------------------------

    /**
     * Returns whether the template is disabled
     *
     * @return bool
     */
    public static function isDisabled(): bool
    {
        return !empty(static::DISABLED);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns whether the template is a default template or not
     *
     * @return bool
     */
    public static function isDefault(): bool
    {
        return !empty(static::$isDefault);
    }

    // --------------------------------------------------------------------------

    /**
     * Whether the template is deprecated or not
     *
     * @return bool
     */
    public static function isDeprecated(): bool
    {
        return static::DEPRECATED;
    }

    // --------------------------------------------------------------------------

    /**
     * When deprecated, an alternative template to use
     *
     * @return string
     */
    public static function alternative(): string
    {
        return static::ALTERNATIVE;
    }

    // --------------------------------------------------------------------------

    /**
     * Constructs the template
     */
    public function __construct()
    {
        $this->label = 'Template';

        // --------------------------------------------------------------------------

        //  Detect the path
        $sCalledClass = get_called_class();
        $this->path   = $sCalledClass::detectPath();

        //  Icon
        $aExtensions = ['png', 'jpg', 'jpeg', 'gif'];

        foreach ($aExtensions as $sExtension) {

            $sIconPath = $this->path . 'icon.' . $sExtension;

            if (is_file($sIconPath)) {

                if (preg_match('#^' . preg_quote(Config::get('NAILS_PATH'), '#') . '#', $sIconPath)) {

                    //  Nails asset
                    $this->icon = preg_replace('#^' . preg_quote(Config::get('NAILS_PATH'), '#') . '#', Config::get('NAILS_URL'), $sIconPath);

                } elseif (preg_match('#^' . preg_quote(Config::get('NAILS_APP_PATH') . 'application/', '#') . '#', $sIconPath)) {

                    if (Functions::isPageSecure()) {
                        $sPattern   = '#^' . preg_quote(Config::get('NAILS_APP_PATH') . 'application/', '#') . '#';
                        $this->icon = preg_replace($sPattern, Config::get('SECURE_BASE_URL') . Config::get('NAILS_APP_PATH') . 'application/', $sIconPath);
                    } else {
                        $sPattern   = '#^' . preg_quote(Config::get('NAILS_APP_PATH') . 'application/', '#') . '#';
                        $this->icon = preg_replace($sPattern, Config::get('BASE_URL') . Config::get('NAILS_APP_PATH') . 'application/', $sIconPath);
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
    public static function getFilePath(string $sFile): ?string
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
     * Returns the template's label
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the template's description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the template's grouping
     *
     * @return string
     */
    public function getGrouping(): string
    {
        return $this->grouping;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the template's widget areas
     *
     * @return array
     */
    public function getWidgetAreas(): array
    {
        return $this->widget_areas;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the template's additional fields
     *
     * @return array
     */
    public function getAdditionalFields(): array
    {
        return $this->additional_fields;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the template's manual config
     *
     * @return string
     */
    public function getManualConfig(): string
    {
        return $this->manual_config;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the template's icon
     *
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the template's slug
     *
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the template's path
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the template's assets
     *
     * @param string $sType The type of assets to return
     *
     * @return array
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
     * Returns the template's render assets
     *
     * @return string[]
     */
    public function getRenderAssets(): array
    {
        return $this->getAssets(static::ASSETS_RENDER);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the template's editor assets
     *
     * @return string[]
     */
    public function getEditorAssets(): array
    {
        return $this->getAssets(static::ASSETS_EDITOR);
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the template with the provided data.
     *
     * @param array $aTplData    The widgets to include in the template
     * @param array $aTplOptions Additional data created by the template
     *
     * @return string
     */
    public function render(array $aTplData = [], array $aTplOptions = []): string
    {
        //  Process each widget area and render the HTML
        $aWidgetAreas  = $this->getWidgetAreas();
        $aRenderedData = [];

        /** @var \Nails\Cms\Service\Widget $oWidgetService */
        $oWidgetService = Factory::service('Widget', Constants::MODULE_SLUG);

        foreach ($aWidgetAreas as $sAreaSlug => $oWidgetArea) {

            $aWidgetData               = !empty($aTplData[$sAreaSlug]) ? $aTplData[$sAreaSlug] : [];
            $aRenderedData[$sAreaSlug] = '';

            foreach ($aWidgetData as $oWidgetData) {

                if (empty($oWidgetData->slug)) {
                    continue;

                } elseif (!property_exists($oWidgetData, 'data')) {
                    $oWidgetData->data = [];
                }

                $oWidget = $oWidgetService->getBySlug($oWidgetData->slug);

                if ($oWidget) {
                    $oWidgetService->loadRenderAssets([$oWidget]);
                    $aRenderedData[$sAreaSlug] .= $oWidget->render((array) $oWidgetData->data);
                }
            }
        }

        return $this->loadView('view', $aTplOptions, $aRenderedData);
    }

    // --------------------------------------------------------------------------

    /**
     * Load a specific view
     *
     * @param string $sView       The view to load
     * @param array  $aTplOptions The selected template options
     * @param array  $aTplData    The data to render the view with
     *
     * @return string
     */
    protected function loadView($sView, array $aTplOptions, array $aTplData): string
    {
        $sPath = static::getFilePath($sView . '.php');
        if (!empty($sPath)) {

            $oView   = Factory::service('View');
            $sBuffer = $oView->load($sPath, array_merge($aTplOptions, $aTplData), true);

            //  Look for blocks
            preg_match_all('/\[:([a-zA-Z0-9\-]+?):\]/', $sBuffer, $aMatches);

            if ($aMatches[0]) {

                //  Get all the blocks which were found
                $oBlockModel = Factory::model('Block', Constants::MODULE_SLUG);
                $aBlocks     = $oBlockModel->getBySlugs($aMatches[1]);

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

                        $sBuffer = str_replace('[:' . $oBlock->slug . ':]', $oBlock->value, $sBuffer);
                    }
                }

                //  Swap page variables
                $aPageShortTags = [
                    'page-title' => !empty($aTplData['cmspage']) ? $aTplData['cmspage']->title : '',
                ];

                foreach ($aPageShortTags as $sShortTag => $sValue) {
                    $sBuffer = str_replace('[:' . $sShortTag . ':]', $sValue, $sBuffer);
                }
            }

            //  Return the HTML
            return $sBuffer;
        }

        return '';
    }

    // --------------------------------------------------------------------------

    /**
     * Format the template as a JSON object
     *
     * @param int $iJsonOptions The JSON options
     * @param int $iJsonDepth   The JSON depth
     *
     * @return string
     */
    public function toJson(int $iJsonOptions = 0, int $iJsonDepth = 512): string
    {
        return json_encode($this->toArray(), $iJsonOptions, $iJsonDepth);
    }

    // --------------------------------------------------------------------------

    /**
     * Format the template as an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'label'             => $this->getLabel(),
            'description'       => $this->getDescription(),
            'widget_areas'      => $this->getWidgetAreas(),
            'additional_fields' => $this->getAdditionalFields(),
            'manual_config'     => $this->getManualConfig(),
            'icon'              => $this->getIcon(),
            'slug'              => $this->getSlug(),
            'assets_editor'     => $this->getEditorAssets(),
            'assets_render'     => $this->getRenderAssets(),
            'path'              => $this->getPath(),
            'is_deprecated'     => static::isDeprecated(),
            'alternative'       => static::alternative(),
        ];
    }
}
