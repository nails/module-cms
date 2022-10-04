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
use Nails\Common\Exception\NailsException;
use Nails\Common\Exception\AssetException;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Helper\ArrayHelper;
use Nails\Common\Helper\Directory;
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
        if ($bIncludeHidden && !empty($this->aLoadedWidgetsIncHidden)) {
            return $this->aLoadedWidgetsIncHidden;

        } elseif (!empty($this->aLoadedWidgets)) {
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

            if (!empty($sWidgetGrouping)) {

                $sKey = md5($sWidgetGrouping);

                if (!isset($aOut[$sKey])) {
                    $aOut[$sKey] = Factory::factory('WidgetGroup', Constants::MODULE_SLUG);
                    $aOut[$sKey]->setLabel($sWidgetGrouping);
                }

                $aOut[$sKey]->add($oWidget);

            } else {

                if (!isset($aGeneric[$sGenericKey])) {
                    $aGeneric[$sGenericKey] = Factory::factory('WidgetGroup', Constants::MODULE_SLUG);
                    $aGeneric[$sGenericKey]->setLabel($sGenericLabel);
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
     * Get an individual widget
     *
     * @param string $sSlug The widget's slug
     *
     * @return Interfaces\Widget|null
     * @return \Nails\Cms\Interfaces\Widget|null
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
        /** @var \Nails\Common\Service\Asset $oAsset */
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
