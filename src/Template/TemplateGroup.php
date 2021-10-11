<?php

/**
 * Multiple templates can be grouped together using this class
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Template
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Template;

use Nails\Cms\Interfaces;

class TemplateGroup
{
    /** @var string */
    protected $sLabel;

    /** @var Interfaces\Template[] */
    protected $aTemplates = [];

    // --------------------------------------------------------------------------

    /**
     * Construct a new template group
     *
     * @param string                $sLabel     The label to give the group
     * @param Interfaces\Template[] $aTemplates An array of templates to add to the group
     */
    public function __construct(string $sLabel = '', array $aTemplates = [])
    {
        $this->setLabel($sLabel);

        foreach ($aTemplates as $oTemplate) {
            $this->add($oTemplate);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Get the group's label
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->sLabel;
    }

    // --------------------------------------------------------------------------

    /**
     * Set the group's label
     *
     * @param string $sLabel The label to give the group
     *
     * @return $this
     */
    public function setLabel(string $sLabel): self
    {
        $this->sLabel = $sLabel;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Add a template to the group
     *
     * @param Interfaces\Template $oTemplate The template to add
     *
     * @return $this
     */
    public function add(Interfaces\Template $oTemplate): self
    {
        $this->aTemplates[$oTemplate->getSlug()] = $oTemplate;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Remove a template from the group
     *
     * @param Interfaces\Template $oTemplate The template to remove
     *
     * @return $this
     */
    public function remove(Interfaces\Template $oTemplate): self
    {
        $this->aTemplates[$oTemplate->getSlug()] = null;
        $this->aTemplates                        = array_filter($this->aTemplates);
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Return the templates in the group
     *
     * @return Interfaces\Template[]
     */
    public function getTemplates(): array
    {
        //  Sort into some alphabetical order and save for later
        ksort($this->aTemplates);

        //  Place default templates first
        $aDefaultTemplates = [];
        foreach ($this->aTemplates as $sKey => &$oTemplate) {
            if ($oTemplate->isDefault()) {
                $aDefaultTemplates[$sKey] = $oTemplate;
                $oTemplate                = null;
            }
        }

        $this->aTemplates = $aDefaultTemplates + array_filter($this->aTemplates);

        return $this->aTemplates;
    }

    // --------------------------------------------------------------------------

    /**
     * Get the group as JSON
     *
     * @param int $iJsonOptions
     * @param int $iJsonDepth
     *
     * @return string
     */
    public function toJson(int $iJsonOptions = 0, int $iJsonDepth = 512): string
    {
        return json_encode(
            [
                'label'     => $this->getLabel(),
                'templates' => array_map(function (Interfaces\Template $oTemplate) {
                    return $oTemplate->toArray();
                }, $this->getTemplates()),
            ],
            $iJsonOptions,
            $iJsonDepth
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Get the templates as JSON
     *
     * @param int $iJsonOptions
     * @param int $iJsonDepth
     *
     * @return string
     */
    public function getTemplatesAsJson(int $iJsonOptions = 0, int $iJsonDepth = 512): string
    {
        $aTemplatesJson = [];
        $aTemplates     = $this->getTemplates();

        foreach ($aTemplates as $oTemplate) {
            $aTemplatesJson[] = $oTemplate->toJson($iJsonOptions, $iJsonDepth);
        }

        return '[' . implode(',', $aTemplatesJson) . ']';
    }
}
