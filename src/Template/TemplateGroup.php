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

class TemplateGroup
{
    protected $sLabel;
    protected $aTemplates;

    // --------------------------------------------------------------------------

    /**
     * Construct a new widget group
     * @param string $sLabel The label to give the group
     * @param array $aTemplates An array of widgets to add to the group
     */
    public function __construct($sLabel = '', $aTemplates = array())
    {
        $this->setLabel($sLabel);
        $this->aTemplates = array();

        if (!empty($aTemplates)) {
            foreach ($aTemplates as $oTemplate) {
                $this->add($oTemplate);
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Get the group's label
     * @return string
     */
    public function getLabel()
    {
        return $this->sLabel;
    }

    // --------------------------------------------------------------------------

    /**
     * Set the group's label
     * @param string $sLabel The label to give the group
     * @return $this
     */
    public function setLabel($sLabel)
    {
        $this->sLabel = $sLabel;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Add a widget to the group
     * @param object $oTemplate The widget to add
     * @return $this
     */
    public function add($oTemplate)
    {
        $this->aTemplates[$oTemplate->getSlug()] = $oTemplate;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Remove a widget from the group
     * @param object $oTemplate The widget to remove
     * @return $this
     */
    public function remove($oTemplate)
    {
        $this->aTemplates[$oTemplate->getSlug()] = null;
        $this->aTemplates = array_filter($this->aTemplates);
        return $this;
    }

    // --------------------------------------------------------------------------

    public function getTemplates()
    {
        //  Sort into some alphabetical order and save for later
        ksort($this->aTemplates);

        //  Place default templates first
        $aDefaultTemplates = array();
        foreach ($this->aTemplates as $sKey => &$oTemplate) {
            if ($oTemplate->isDefault()) {
                $aDefaultTemplates[$sKey] = $oTemplate;
                $oTemplate = null;
            }
        }

        $this->aTemplates = $aDefaultTemplates + array_filter($this->aTemplates);

        return $this->aTemplates;
    }

    // --------------------------------------------------------------------------

    public function getTemplatesAsJson($iJsonOptions = 0, $iJsonDepth = 512)
    {
        $aTemplatesJson = array();
        $aTemplates = $this->getTemplates();

        foreach ($aTemplates as $oTemplate) {
            $aTemplatesJson[] = $oTemplate->toJson($iJsonOptions, $iJsonDepth);
        }

        return '[' . implode(',', $aTemplatesJson) . ']';
    }
}
