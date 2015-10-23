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

use Nails\Factory;

class Widget
{
    protected $aLoadedWidgets;
    protected $aWidgetDirs;

    // --------------------------------------------------------------------------

    /**
     * Construct the model
     */
    public function __construct()
    {
        $aModules           = _NAILS_GET_MODULES();
        $this->aWidgetDirs = array();

        foreach ($aModules as $oModule) {

            $this->aWidgetDirs[]   = $oModule->path . 'cms/widgets/';
        }

        /**
         * Load App widgets afterwards so that they may override the module
         * supplied ones.
         */

        $this->aWidgetDirs[]   = FCPATH . APPPATH . 'modules/cms/widgets/';
    }

    // --------------------------------------------------------------------------

    /**
     * Get all available widgets to the system
     * @param  string $loadAssets Whether or not to load widget's assets, and if so whether EDITOR or RENDER assets.
     * @return array
     */
    public function getAvailable($loadAssets = false)
    {
        if (!empty($this->aLoadedWidgets)) {

            return $this->aLoadedWidgets;
        }

        Factory::helper('directory');
        $aAvailableWidgets = array();

        foreach ($this->aWidgetDirs as $sDir) {

            if (is_dir($sDir)) {

                $aWidgets = directory_map($sDir);

                foreach ($aWidgets as $sWidgetDir => $aWidgetFiles) {

                    if (is_file($sDir . $sWidgetDir . '/widget.php')) {

                        $aAvailableWidgets[$sWidgetDir] = array(
                            'path' => $sDir,
                            'name' => $sWidgetDir
                        );
                    }
                }
            }
        }

        //  Instantiate widgets
        $aLoadedWidgets = array();
        foreach ($aAvailableWidgets as $aWidget) {

            include_once $aWidget['path'] . $aWidget['name'] . '/widget.php';

            $sClassName = '\Nails\Cms\Widget\\' . ucfirst(strtolower($aWidget['name']));

            if (!class_exists($sClassName)) {

                log_message(
                    'error',
                    'CMS Widget discovered at "' . $aWidget['path'] . $aWidget['name'] .
                    '" but does not contain class "' . $sClassName . '"'
                );

            } elseif (!empty($sClassName::isDisabled())) {

                /**
                 * This widget is disabled, ignore this template. Don't log
                 * anything as it's likely a developer override to hide a default
                 * template.
                 */

            } else {

                $aLoadedWidgets[$aWidget['name']] = new $sClassName();

                //  Load the template's assets if requested
                if ($loadAssets) {

                    $aAssets = $aLoadedWidgets[$aWidget['name']]->getAssets($loadAssets);
                    $this->loadAssets($aAssets);
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Sort the widgets into their sub groupings
        $aOut          = array();
        $aGeneric      = array();
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
     * @param  string $sSlug       The widget's slug
     * @param  string $sLoadAssets Whether or not to load the widget's assets, and if so whether EDITOR or RENDER assets.
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
     * @param  array  $aAssets An array of assets to load
     * @return void
     */
    protected function loadAssets($aAssets = array())
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
