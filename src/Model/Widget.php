<?php

/**
 * This model handle CMS Widgets
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Model;

use Nails\Cms\Exception\Widget\NotFoundException;
use Nails\Cms\Widget\WidgetBase;
use Nails\Factory;

class Widget
{
    protected $aLoadedWidgets;

    // --------------------------------------------------------------------------

    /**
     * Get all available widgets to the system
     *
     * @param bool $bLoadAssets Whether or not to load widget's assets, and if so whether EDITOR or RENDER assets.
     *
     * @throws NotFoundException
     * @return array
     */
    public function getAvailable($bLoadAssets = false)
    {
        if (!empty($this->aLoadedWidgets)) {
            return $this->aLoadedWidgets;
        }

        Factory::helper('directory');
        $aAvailableWidgets = [];
        $aModules          = _NAILS_GET_MODULES();

        //  Append the app
        $aModules['app'] = (object) [
            'path'      => APPPATH . 'modules/',
            'namespace' => 'App\\',
        ];

        foreach ($aModules as $oModule) {

            $sWidgetDir = $oModule->path . 'cms/widgets/';

            if (!is_dir($sWidgetDir)) {
                continue;
            }

            $aWidgets = directory_map($sWidgetDir, 1);
            if (!empty($aWidgets)) {
                foreach ($aWidgets as $sWidgetName) {
                    $sWidgetName       = trim($sWidgetName, DIRECTORY_SEPARATOR);
                    $sWidgetDefinition = $sWidgetDir . $sWidgetName . '/widget.php';
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

            if (!$sClassName::isDisabled()) {

                $aLoadedWidgets[$oWidget->name] = new $sClassName();

                //  Load the widget's assets if requested
                if ($bLoadAssets) {
                    $aAssets = $aLoadedWidgets[$oWidget->name]->getAssets($bLoadAssets);
                    $this->loadAssets($aAssets);
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Sort the widgets into their sub groupings
        $aOut          = [];
        $aGeneric      = [];
        $sGenericLabel = 'Generic';

        foreach ($aLoadedWidgets as $sWidgetSlug => $oWidget) {

            $sWidgetGrouping = $oWidget->getGrouping();

            if (!empty($sWidgetGrouping)) {

                $sKey = md5($sWidgetGrouping);

                if (!isset($aOut[$sKey])) {
                    $aOut[$sKey] = Factory::factory('WidgetGroup', 'nailsapp/module-cms');
                    $aOut[$sKey]->setLabel($sWidgetGrouping);
                }

                $aOut[$sKey]->add($oWidget);

            } else {

                $sKey = md5($sGenericLabel);

                if (!isset($aGeneric[$sKey])) {
                    $aGeneric[$sKey] = Factory::factory('WidgetGroup', 'nailsapp/module-cms');
                    $aGeneric[$sKey]->setLabel($sGenericLabel);
                }

                $aGeneric[$sKey]->add($oWidget);
            }
        }

        //  Glue generic grouping to the beginning of the array
        $aOut = array_merge($aGeneric, $aOut);
        $aOut = array_values($aOut);

        $this->aLoadedWidgets = $aOut;

        return $this->aLoadedWidgets;
    }

    // --------------------------------------------------------------------------

    /**
     * Get an individual widget
     *
     * @param  string $sSlug       The widget's slug
     * @param  string $sLoadAssets Whether or not to load the widget's assets, and if so whether EDITOR or RENDER assets.
     *
     * @return mixed
     */
    public function getBySlug($sSlug, $sLoadAssets = false)
    {
        $aWidgetGroups = $this->getAvailable();

        foreach ($aWidgetGroups as $oWidgetGroup) {

            $aWidgets = $oWidgetGroup->getWidgets();

            foreach ($aWidgets as $oWidget) {
                if ($sSlug == $oWidget->getSlug()) {

                    if ($sLoadAssets) {
                        $aAssets = $oWidget->getAssets($sLoadAssets);
                        $this->loadAssets($aAssets);
                    }

                    return $oWidget;
                }
            }
        }

        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * Load widget assets
     *
     * @param  array $aAssets An array of assets to load
     *
     * @return void
     */
    protected function loadAssets($aAssets = [])
    {
        $oAsset = Factory::service('Asset');
        foreach ($aAssets as $aAsset) {

            if (is_array($aAsset)) {

                if (!empty($aAsset[1])) {
                    $bIsNails = $aAsset[1];
                } else {
                    $bIsNails = false;
                }

                $oAsset->load($aAsset[0], $bIsNails);

            } elseif (is_string($aAsset)) {
                $oAsset->load($aAsset);
            }
        }
    }
}
