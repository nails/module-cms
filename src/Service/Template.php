<?php

/**
 * This service handle CMS Templates
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Service
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Service;

use Nails\Cms\Constants;
use Nails\Cms\Exception\Template\NotFoundException;
use Nails\Cms\Interfaces;
use Nails\Cms\Template\TemplateGroup;
use Nails\Common\Exception\NailsException;
use Nails\Common\Helper\ArrayHelper;
use Nails\Common\Helper\Directory;
use Nails\Components;
use Nails\Factory;

/**
 * Class Template
 *
 * @package Nails\Cms\Service
 */
class Template
{
    /** @var \Nails\Cms\Template\TemplateGroup[] */
    protected $aLoadedTemplates;

    /** @var array[] */
    protected $aTemplateDirs;

    // --------------------------------------------------------------------------

    /**
     * Template constructor.
     */
    public function __construct()
    {
        $aModules            = Components::modules();
        $this->aTemplateDirs = [];

        foreach ($aModules as $oModule) {
            $this->aTemplateDirs[] = [
                'namespace' => $oModule->namespace,
                'path'      => $oModule->path . 'cms/templates/',
            ];
        }

        /**
         * Load App templates afterwards so that they may override the module
         * supplied ones.
         */
        $this->aTemplateDirs[] = [
            'namespace' => 'App\\',
            'path'      => NAILS_APP_PATH . 'application/modules/cms/templates/',
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * Get all available templates to the system
     *
     * @return \Nails\Cms\Template\TemplateGroup[]
     * @throws NotFoundException
     */
    public function getAvailable()
    {
        if (!empty($this->aLoadedTemplates)) {
            return $this->aLoadedTemplates;
        }

        $aAvailableTemplates = [];

        foreach ($this->aTemplateDirs as $aDir) {

            $aTemplates = Directory::map($aDir['path'], 2);
            $aTemplates = array_map(function ($sPath) {
                return dirname($sPath) . DIRECTORY_SEPARATOR;
            }, $aTemplates);
            $aTemplates = array_unique($aTemplates);
            $aTemplates = array_values($aTemplates);

            foreach ($aTemplates as $sTemplateDir) {

                if (is_file($sTemplateDir . 'template.php')) {
                    $aAvailableTemplates[] = [
                        'namespace' => $aDir['namespace'],
                        'path'      => $aDir['path'],
                        'name'      => basename($sTemplateDir),
                    ];
                }
            }
        }

        //  Load templates
        $aTemplatesToInstantiate = [];
        foreach ($aAvailableTemplates as $aTemplate) {
            require_once $aTemplate['path'] . $aTemplate['name'] . '/template.php';

            //  Specify which templates to instantiate, app ones will override module ones
            $aTemplatesToInstantiate[$aTemplate['name']] = $aTemplate;
        }

        //  Instantiate templates
        $aLoadedTemplates = [];
        foreach ($aTemplatesToInstantiate as $aTemplate) {

            $sTemplateName  = trim($aTemplate['name'], DIRECTORY_SEPARATOR);
            $sTemplateClass = $aTemplate['namespace'] . 'Cms\Template\\' . ucfirst($sTemplateName);

            if (!class_exists($sTemplateClass)) {
                throw new NotFoundException(
                    'Template class "' . $sTemplateClass . '" missing from "' . $aTemplate['path'] . '"',
                    500
                );
            }

            if (!$sTemplateClass::isDisabled()) {
                $aLoadedTemplates[$aTemplate['name']] = new $sTemplateClass();
            }
        }

        // --------------------------------------------------------------------------

        //  Sort the Templates into their sub groupings
        $aOut          = [];
        $aGeneric      = [];
        $sGenericLabel = 'Generic';

        foreach ($aLoadedTemplates as $sTemplateSlug => $oTemplate) {

            $sTemplateGrouping = $oTemplate->getGrouping();

            if (!empty($sTemplateGrouping)) {

                $sKey = md5($sTemplateGrouping);

                if (!isset($aOut[$sKey])) {

                    $aOut[$sKey] = Factory::factory('TemplateGroup', Constants::MODULE_SLUG);
                    $aOut[$sKey]->setLabel($sTemplateGrouping);
                }

                $aOut[$sKey]->add($oTemplate);

            } else {

                $sKey = md5($sGenericLabel);

                if (!isset($aGeneric[$sKey])) {

                    $aGeneric[$sKey] = Factory::factory('TemplateGroup', Constants::MODULE_SLUG);
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
     *
     * @param string $sSlug The template's slug
     *
     * @return Interfaces\Template|null
     */
    public function getBySlug($sSlug): ?Interfaces\Template
    {
        $oTemplateGroups = $this->getAvailable();

        foreach ($oTemplateGroups as $oTemplateGroup) {

            $aTemplates = $oTemplateGroup->getTemplates();

            foreach ($aTemplates as $oTemplate) {
                if ($sSlug == $oTemplate->getSlug()) {
                    return $oTemplate;
                }
            }
        }

        return null;
    }

    // --------------------------------------------------------------------------

    /**
     * Load template assets
     *
     * @param array $aAssets An array of assets to load
     *
     * @return void
     */
    protected function loadAssets($aAssets = [])
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
     * Extracts assets of a particular type from an array of Templates or TemplateGroups
     *
     * @param TemplateGroup[]|Interfaces\Template[] $aTemplates The templates, or template groups
     * @param string                                $sType      The type of asset
     *
     * @return array
     * @throws \Nails\Common\Exception\NailsException
     */
    protected function extractAssets(array $aTemplates, string $sType): array
    {
        $aAssets = array_map(function ($oTemplate) use ($sType) {

            if ($oTemplate instanceof TemplateGroup) {
                return $this->reduceAssets(
                    array_map(
                        function (Interfaces\Template $oTemplate) use ($sType) {
                            if ($sType === Interfaces\Template::ASSETS_EDITOR) {
                                return $oTemplate->getEditorAssets();
                            } elseif ($sType === Interfaces\Template::ASSETS_RENDER) {
                                return $oTemplate->getRenderAssets();
                            } else {
                                return [];
                            }
                        },
                        $oTemplate->getTemplates()
                    )
                );

            } elseif ($oTemplate instanceof Interfaces\Template) {
                if ($sType === Interfaces\Template::ASSETS_EDITOR) {
                    return $oTemplate->getEditorAssets();

                } elseif ($sType === Interfaces\Template::ASSETS_RENDER) {
                    return $oTemplate->getRenderAssets();

                } else {
                    return [];
                }

            } else {
                throw new NailsException(sprintf(
                    'Expected instance of %s or %s, received %s',
                    TemplateGroup::class,
                    Interfaces\Template::class,
                    get_class($oTemplate)
                ));
            }

        }, $aTemplates);

        return $this->reduceAssets($aAssets);
    }

    // --------------------------------------------------------------------------

    /**
     * Reduces template assets to a normalised array
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
     * Loads template editor assets
     *
     * @param Interfaces\Template[] $aTemplates The templates to load assets for
     *
     * @return $this
     * @throws NailsException
     */
    public function loadEditorAssets(array $aTemplates): self
    {
        $this->loadAssets($this->extractAssets($aTemplates, Interfaces\Template::ASSETS_EDITOR));
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Loads template render assets
     *
     * @param Interfaces\Template[] $aTemplates The templates to load assets for
     *
     * @return $this
     * @throws NailsException
     */
    public function loadRenderAssets(array $aTemplates): self
    {
        $this->loadAssets($this->extractAssets($aTemplates, Interfaces\Template::ASSETS_RENDER));
        return $this;
    }
}
