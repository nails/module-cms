<?php

/**
 * This service handle CMS Widgets
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Service
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Service;

use Nails\Cms\Constants;
use Nails\Cms\Exception\Widget\NotFoundException;
use Nails\Cms\Interfaces;
use Nails\Cms\Widget\WidgetGroup;
use Nails\Common\Exception\AssetException;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\NailsException;
use Nails\Common\Helper\ArrayHelper;
use Nails\Common\Helper\Directory;
use Nails\Common\Service\Asset;
use Nails\Components;
use Nails\Factory;

/**
 * Class Widget
 *
 * @package Nails\Cms\Service
 */
class Widget
{
    /**
     * The loaded widgets
     *
     * @var WidgetGroup[]|null
     */
    protected ?array $aLoadedWidgets = null;

    /**
     * The loaded widgets (including hidden)
     *
     * @var WidgetGroup[]|null
     */
    protected ?array $aLoadedWidgetsIncHidden = null;

    // --------------------------------------------------------------------------

    /**
     * Get all available widgets to the system
     *
     * @param bool $bIncludeHidden Whether to include hidden widgets
     *
     * @return WidgetGroup[]
     * @throws NotFoundException
     * @throws FactoryException
     */
    public function getAvailable(bool $bIncludeHidden = false): array
    {
        if ($bIncludeHidden && $this->aLoadedWidgetsIncHidden !== null) {
            return $this->aLoadedWidgetsIncHidden;

        } elseif (!$bIncludeHidden && $this->aLoadedWidgets !== null) {
            return $this->aLoadedWidgets;
        }

        $aAvailableWidgets = [];
        $aModules          = Components::modules();

        //  Append the app
        $aModules['app'] = (object) [
            'path'      => NAILS_APP_PATH . 'application/modules/',
            'namespace' => 'App\\',
        ];

        foreach ($aModules as $oModule) {

            $sWidgetDir = $oModule->path . 'cms/widgets/';
            $aWidgets   = Directory::map($sWidgetDir, 2, false);

            if (!empty($aWidgets)) {

                foreach ($aWidgets as $sWidgetName) {

                    $sWidgetName       = preg_replace('#' . DIRECTORY_SEPARATOR . 'widget\.php$#', '', $sWidgetName);
                    $sWidgetDefinition = $sWidgetDir . $sWidgetName . DIRECTORY_SEPARATOR . 'widget.php';
                    $sWidgetClass      = $oModule->namespace . 'Cms\Widget\\' . ucfirst($sWidgetName);

                    if (is_file($sWidgetDefinition)) {

                        require_once $sWidgetDefinition;

                        if (!class_exists($sWidgetClass)) {
                            throw new NotFoundException(
                                'Widget class "' . $sWidgetClass . '" missing from "' . $sWidgetDefinition . '"',
                                500
                            );
                        }

                        $aAvailableWidgets[$sWidgetName] = (object) [
                            'path'  => $sWidgetDir,
                            'name'  => $sWidgetName,
                            'class' => $sWidgetClass,
                        ];
                    }
                }
            }
        }

        //  Instantiate widgets
        $aLoadedWidgets = [];
        foreach ($aAvailableWidgets as $oWidget) {

            $sClassName = $oWidget->class;

            if ($sClassName::isDisabled()) {
                continue;
            } elseif (!$bIncludeHidden && $sClassName::isHidden()) {
                continue;
            }

            $aLoadedWidgets[$oWidget->name] = new $sClassName();
        }

        // --------------------------------------------------------------------------

        //  Sort the widgets into their sub groupings
        $aOut          = [];
        $aGeneric      = [];
        $sGenericLabel = 'Generic';
        $sGenericKey   = md5('Generic');

        foreach ($aLoadedWidgets as $oWidget) {

            $sWidgetGrouping = $oWidget->getGrouping();

            if (!empty($sWidgetGrouping) && $sWidgetGrouping !== $sGenericLabel) {

                $sKey = md5($sWidgetGrouping);

                if (!isset($aOut[$sKey])) {
                    $aOut[$sKey] = Factory::factory('WidgetGroup', Constants::MODULE_SLUG);
                    $aOut[$sKey]
                        ->setLabel($sWidgetGrouping)
                        ->setOrder($this->getWidgetGroupOrder($sWidgetGrouping));
                }

                $aOut[$sKey]->add($oWidget);

            } else {

                if (!isset($aGeneric[$sGenericKey])) {
                    $aGeneric[$sGenericKey] = Factory::factory('WidgetGroup', Constants::MODULE_SLUG);
                    $aGeneric[$sGenericKey]
                        ->setLabel($sGenericLabel)
                        ->setOrder($this->getWidgetGroupOrder($sGenericLabel));
                }

                $aGeneric[$sGenericKey]->add($oWidget);
            }
        }

        //  Glue generic grouping to the beginning of the array
        $aOut = array_merge($aGeneric, $aOut);
        $aOut = array_values($aOut);

        if ($bIncludeHidden) {
            return $this->aLoadedWidgetsIncHidden = $aOut;
        } else {
            return $this->aLoadedWidgets = $aOut;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the order value for a widget group
     */
    protected function getWidgetGroupOrder(string $sLabel): ?int
    {
        /**
         * This method is intended to be overridden by the App.
         * By default, all widget groups are ordered `null`. The `null`
         * order comes after any other numerically defined orders, i.e.:
         * 0, 1, 2, 3, null
         * Widget groups with the same order are sub-sorted by label A-Z
         */
        return null;
    }

    // --------------------------------------------------------------------------

    /**
     * The deepest level at which WidgetEditor (v2) containers are rendered. Deeper nodes are skipped.
     * This method is intended to be overridden by the App.
     */
    public function getMaxDepth(): int
    {
        return 8;
    }

    // --------------------------------------------------------------------------

    /**
     * The total number of widget instances an area may hold, counted at every depth.
     * The WidgetEditor (v2) enforces this on insert and move. This method is intended to be overridden by the App.
     */
    public function getMaxInstancesPerArea(): int
    {
        return 200;
    }

    // --------------------------------------------------------------------------

    /**
     * Stylesheet URLs the WidgetEditor canvas loads, so previews look like the site. Scripts are never loaded.
     * This method is intended to be overridden by the App.
     *
     * @return string[]
     */
    public function getCanvasAssets(): array
    {
        return [];
    }

    // --------------------------------------------------------------------------

    /**
     * The WidgetEditor catalogue for one request
     *
     * Group order comes from getWidgetGroupOrder(), so an app override of that hook applies here.
     *
     * @param array{allowed?: string[], disallowed?: string[]} $rules
     * @param string[]                                         $favourites
     *
     * @return array<string, mixed>
     * @throws FactoryException
     */
    public function catalogue(array $rules, array $favourites): array
    {
        return (new \Nails\Cms\WidgetEditor\Service\Catalogue())->build(
            $this->getRegistry(),
            $rules,
            $favourites,
            fn(string $label): ?int => $this->getWidgetGroupOrder($label),
            $this->getMaxDepth(),
            $this->getMaxInstancesPerArea(),
            $this->getCanvasAssets()
        );
    }

    // --------------------------------------------------------------------------

    /**
     * The WidgetEditor (v2) registry, for this request
     *
     * @throws FactoryException
     */
    public function getRegistry(): \Nails\Cms\WidgetEditor\Service\Registry
    {
        return Factory::service('WidgetEditorRegistry', Constants::MODULE_SLUG);
    }

    // --------------------------------------------------------------------------

    /**
     * Whether a stored slug (or alias) is known to either widget system, whatever its status
     *
     * Use this to decide whether an unknown slug is an error. A known widget which does not render
     * (disabled) is not an error, and renders as an empty string.
     *
     * @throws FactoryException
     * @throws NotFoundException
     */
    public function isKnownSlug(string $sSlug): bool
    {
        return $this->getRegistry()->knows($sSlug) || $this->getBySlug($sSlug) !== null;
    }

    // --------------------------------------------------------------------------

    /**
     * Renders one stored instance, {slug, data}, through whichever widget system knows the slug
     *
     * WidgetEditor (v2) widgets handle their own assets and render events, including for their children.
     * Legacy (v1) widgets get their render assets loaded here, as before. Returns an empty string for an
     * unknown or disabled slug; use isKnownSlug() to tell them apart.
     *
     * @param string               $sSlug The stored slug, or an alias
     * @param array|object|null    $mData The instance's data
     *
     * @throws FactoryException
     * @throws NotFoundException
     * @throws NailsException
     */
    public function renderSlug(string $sSlug, array|object|null $mData = []): string
    {
        $oRegistry = $this->getRegistry();

        if ($oRegistry->knows($sSlug)) {
            return $oRegistry->renderNode(['slug' => $sSlug, 'data' => $mData ?? []]);
        }

        $oWidget = $this->getBySlug($sSlug);
        if ($oWidget === null) {
            return '';
        }

        $this->loadRenderAssets([$oWidget]);
        return $oWidget->render((array) $mData);
    }

    // --------------------------------------------------------------------------

    /**
     * Get an individual widget
     *
     * @param string $sSlug The widget's slug
     *
     * @return Interfaces\Widget|null
     * @return Interfaces\Widget|null
     * @throws NotFoundException
     * @throws FactoryException
     */
    public function getBySlug(string $sSlug): ?Interfaces\Widget
    {
        $aWidgetGroups = $this->getAvailable(true);

        foreach ($aWidgetGroups as $oWidgetGroup) {

            $aWidgets = $oWidgetGroup->getWidgets();

            foreach ($aWidgets as $oWidget) {
                if ($sSlug === $oWidget->getSlug()) {
                    return $oWidget;
                }
            }
        }

        return null;
    }

    // --------------------------------------------------------------------------

    /**
     * Load widget assets
     *
     * @param array $aAssets An array of assets to load
     *
     * @return void
     * @throws AssetException
     * @throws FactoryException
     */
    protected function loadAssets(array $aAssets = []): void
    {
        /** @var Asset $oAsset */
        $oAsset = Factory::service('Asset');

        foreach ($aAssets as $aAsset) {
            if (is_array($aAsset)) {
                $oAsset->load($aAsset[0], $aAsset[1] ?? null);

            } elseif (is_string($aAsset)) {
                $oAsset->load($aAsset);
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Extracts assets of a particular type from an array of Widgets or WidgetGroups
     *
     * @param WidgetGroup[]|Interfaces\Widget[] $aWidgets The widgets, or widget groups
     * @param string                            $sType    The type of asset
     *
     * @return array
     * @throws NailsException
     */
    protected function extractAssets(array $aWidgets, string $sType): array
    {
        $aAssets = array_map(function ($oWidget) use ($sType) {

            if ($oWidget instanceof WidgetGroup) {
                return $this->reduceAssets(
                    array_map(
                        function (Interfaces\Widget $oWidget) use ($sType) {
                            if ($sType === Interfaces\Widget::ASSETS_EDITOR) {
                                return $oWidget->getEditorAssets();
                            } elseif ($sType === Interfaces\Widget::ASSETS_RENDER) {
                                return $oWidget->getRenderAssets();
                            } else {
                                return [];
                            }
                        },
                        $oWidget->getWidgets()
                    )
                );

            } elseif ($oWidget instanceof Interfaces\Widget) {
                if ($sType === Interfaces\Widget::ASSETS_EDITOR) {
                    return $oWidget->getEditorAssets();

                } elseif ($sType === Interfaces\Widget::ASSETS_RENDER) {
                    return $oWidget->getRenderAssets();

                } else {
                    return [];
                }

            } else {
                throw new NailsException(sprintf(
                    'Expected instance of %s or %s, received %s',
                    WidgetGroup::class,
                    Interfaces\Widget::class,
                    get_class($oWidget)
                ));
            }

        }, $aWidgets);

        return $this->reduceAssets($aAssets);
    }

    // --------------------------------------------------------------------------

    /**
     * Reduces widget assets to a normalised array
     *
     * @param string[]|array[] $aAssets The assets to reduce
     *
     * @return string[]|array[]
     */
    protected function reduceAssets(array $aAssets): array
    {
        $aOut = [];
        foreach ($aAssets as $mAsset) {
            $aOut = array_merge($aOut, $mAsset);
        }

        return array_values(
            array_filter(
                ArrayHelper::arrayUniqueMulti(
                    $aOut
                )
            )
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Loads widget editor assets
     *
     * @param Interfaces\Widget[] $aWidgets The widgets to load assets for
     *
     * @return $this
     * @throws NailsException
     */
    public function loadEditorAssets(array $aWidgets): self
    {
        $this->loadAssets($this->extractAssets($aWidgets, Interfaces\Widget::ASSETS_EDITOR));
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Loads widget render assets
     *
     * @param Interfaces\Widget[] $aWidgets The widgets to load assets for
     *
     * @return $this
     * @throws NailsException
     */
    public function loadRenderAssets(array $aWidgets): self
    {
        $this->loadAssets($this->extractAssets($aWidgets, Interfaces\Widget::ASSETS_RENDER));
        return $this;
    }
}
