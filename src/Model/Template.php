<?php

/**
 * This model handle CMS Templates
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Model;

use Nails\Factory;

class Template
{
    protected $aLoadedTemplates;
    protected $aTemplateDirs;

    // --------------------------------------------------------------------------

    /**
     * Construct the model
     */
    public function __construct()
    {
        $aModules            = _NAILS_GET_MODULES();
        $this->aTemplateDirs = array();

        foreach ($aModules as $oModule) {

            $this->aTemplateDirs[] = array(
                'type' => 'vendor',
                'path' => $oModule->path . 'cms/templates/'
            );
        }

        /**
         * Load App templates afterwards so that they may override the module
         * supplied ones.
         */

        $this->aTemplateDirs[] = array(
            'type' => 'app',
            'path' => APPPATH . 'modules/cms/templates/'
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Get all available templates to the system
     * @param  string $loadAssets Whether or not to load template's assets, and if so whether EDITOR or RENDER assets.
     * @return array
     */
    public function getAvailable($loadAssets = '')
    {
        if (!empty($this->aLoadedTemplates)) {

            return $this->aLoadedTemplates;
        }

        $aAvailableTemplates = array();

        foreach ($this->aTemplateDirs as $aDir) {

            if (is_dir($aDir['path'])) {

                $aTemplates = directory_map($aDir['path']);

                foreach ($aTemplates as $sTemplateDir => $aTemplateFiles) {

                    if (is_file($aDir['path'] . $sTemplateDir . '/template.php')) {

                        $aAvailableTemplates[] = array(
                            'type' => $aDir['type'],
                            'path' => $aDir['path'],
                            'name' => $sTemplateDir
                        );
                    }
                }
            }
        }

        //  Load templates
        $aTemplatesToInstanciate = array();
        foreach ($aAvailableTemplates as $aTemplate) {
            include_once $aTemplate['path'] . $aTemplate['name'] . '/template.php';

            //  Specify which templates to instanciates, app ones will override nails ones
            $aTemplatesToInstanciate[$aTemplate['name']] = $aTemplate;
        }

        //  Instantiate templates
        $aLoadedTemplates = array();
        foreach ($aTemplatesToInstanciate as $aTemplate) {

            $sPrefix    = $aTemplate['type'] == 'vendor' ? 'Nails' : 'App';
            $sClassName = '\\' . $sPrefix . '\Cms\Template\\' . ucfirst(strtolower($aTemplate['name']));

            if (!class_exists($sClassName)) {

                log_message(
                    'error',
                    'CMS Template discovered at "' . $aTemplate['path'] . $aTemplate['name'] .
                    '" but does not contain class "' . $sClassName . '"'
                );

            } elseif ($sClassName::isDisabled()) {

                /**
                 * This template is disabled, ignore this template. Don't log
                 * anything as it's likely a developer override to hide a default
                 * template.
                 */

            } else {

                $aLoadedTemplates[$aTemplate['name']] = new $sClassName();

                //  Load the template's assets if requested
                if ($loadAssets) {

                    $aAssets = $aLoadedTemplates[$aTemplate['name']]->getAssets($loadAssets);
                    $this->loadAssets($aAssets);
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Sort the Templates into their sub groupings
        $aOut          = array();
        $aGeneric      = array();
        $sGenericLabel = 'Generic';

        foreach ($aLoadedTemplates as $sTemplateSlug => $oTemplate) {

            $sTemplateGrouping = $oTemplate->getGrouping();

            if (!empty($sTemplateGrouping)) {

                $sKey = md5($sTemplateGrouping);

                if (!isset($aOut[$sKey])) {

                    $aOut[$sKey] = Factory::factory('TemplateGroup', 'nailsapp/module-cms');
                    $aOut[$sKey]->setLabel($sTemplateGrouping);
                }

                $aOut[$sKey]->add($oTemplate);

            } else {

                $sKey = md5($sGenericLabel);

                if (!isset($aGeneric[$sKey])) {

                    $aGeneric[$sKey] = Factory::factory('TemplateGroup', 'nailsapp/module-cms');
                    $aGeneric[$sKey]->setLabel($sGenericLabel);
                }

                $aGeneric[$sKey]->add($oTemplate);
            }
        }

        //  Glue generic grouping to the beginning of the array
        $aOut = array_merge($aGeneric, $aOut);
        $aOut = array_values($aOut);

        $this->aLoadedTemplates = $aOut;

        //  Sort groupings into alphabetical order
        //  @todo

        return $this->aLoadedTemplates;
    }

    // --------------------------------------------------------------------------

    /**
     * Get an individual template
     * @param  string  $sSlug       The template's slug
     * @param  boolean $sLoadAssets Whether or not to load the template's assets, and if so whether EDITOR or RENDER assets.
     * @return mixed
     */
    public function getBySlug($sSlug, $sLoadAssets = false)
    {
        $oTemplateGroups = $this->getAvailable();

        foreach ($oTemplateGroups as $oTemplateGroup) {

            $aTemplates = $oTemplateGroup->getTemplates();

            foreach ($aTemplates as $oTemplate) {

                if ($sSlug == $oTemplate->getSlug()) {

                    if ($sLoadAssets) {

                        $aAssets = $oTemplate->getAssets($sLoadAssets);
                        $this->loadAssets($aAssets);
                    }

                    return $oTemplate;
                }
            }
        }

        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * Load template assets
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
