<?php

/**
 * Represents a template option
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Template
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Template;

class TemplateOption
{
    /**
     * @var array The data used to create the field (passed to form_field())
     */
    protected $aFieldData;

    // --------------------------------------------------------------------------

    /**
     * TemplateOption constructor.
     */
    public function __construct()
    {
        //  Set up the default property values
        $this->aFieldData = array(
            'type'        => '',
            'key'         => '',
            'label'       => '',
            'subLabel'    => '',
            'info'        => '',
            'default'     => '',
            'class'       => '',
            'placeholder' => '',
            'tip'         => '',
            'options'     => array()
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Sets a field property
     * @param  $sProperty string The name of the property to set
     * @param  $mValue    mixed  The value of the property
     * @return $this
     */
    public function setProperty($sProperty, $mValue)
    {
        $this->aFieldData[$sProperty] = $mValue;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Retrieve a property value
     * @param  $sProperty string The property to retrieve
     * @return mixed|null
     */
    public function getProperty($sProperty)
    {
        return isset($this->aFieldData[$sProperty]) ? $this->aFieldData[$sProperty] : null;
    }

    // --------------------------------------------------------------------------

    /**
     * Set the value of the "type" property
     * @param $sValue string The value to set
     * @return TemplateOption
     */
    public function setType($sValue)
    {
        return $this->setProperty('type', $sValue);
    }

    // --------------------------------------------------------------------------

    /**
     * Retrieve the value of the "type" property
     * @return mixed|null
     */
    public function getType()
    {
        return $this->getProperty('type');
    }

    // --------------------------------------------------------------------------

    /**
     * Set the value of the "key" property
     * @param $sValue string The value to set
     * @return TemplateOption
     */
    public function setKey($sValue)
    {
        return $this->setProperty('key', $sValue);
    }

    // --------------------------------------------------------------------------

    /**
     * Retrieve the value of the "key" property
     * @return mixed|null
     */
    public function getKey()
    {
        return $this->getProperty('key');
    }

    // --------------------------------------------------------------------------

    /**
     * Set the value of the "label" property
     * @param $sValue string The value to set
     * @return TemplateOption
     */
    public function setLabel($sValue)
    {
        return $this->setProperty('label', $sValue);
    }

    // --------------------------------------------------------------------------

    /**
     * Retrieve the value of the "label" property
     * @return mixed|null
     */
    public function getLabel()
    {
        return $this->getProperty('label');
    }

    // --------------------------------------------------------------------------

    /**
     * Set the value of the "subLabel" property
     * @param $sValue string The value to set
     * @return TemplateOption
     */
    public function setSubLabel($sValue)
    {
        return $this->setProperty('subLabel', $sValue);
    }

    // --------------------------------------------------------------------------

    /**
     * Retrieve the value of the "subLabel" property
     * @return mixed|null
     */
    public function getSubLabel()
    {
        return $this->getProperty('subLabel');
    }

    // --------------------------------------------------------------------------

    /**
     * Set the value of the "info" property
     * @param $sValue string The value to set
     * @return TemplateOption
     */
    public function setInfo($sValue)
    {
        return $this->setProperty('info', $sValue);
    }

    // --------------------------------------------------------------------------

    /**
     * Retrieve the value of the "info" property
     * @return mixed|null
     */
    public function getInfo()
    {
        return $this->getProperty('info');
    }

    // --------------------------------------------------------------------------

    /**
     * Set the value of the "default" property
     * @param $sValue string The value to set
     * @return TemplateOption
     */
    public function setDefault($sValue)
    {
        return $this->setProperty('default', $sValue);
    }

    // --------------------------------------------------------------------------

    /**
     * Retrieve the value of the "default" property
     * @return mixed|null
     */
    public function getDefault()
    {
        return $this->getProperty('default');
    }

    // --------------------------------------------------------------------------

    /**
     * Set the value of the "class" property
     * @param $sValue string The value to set
     * @return TemplateOption
     */
    public function setClass($sValue)
    {
        return $this->setProperty('class', $sValue);
    }

    // --------------------------------------------------------------------------

    /**
     * Retrieve the value of the "class" property
     * @return mixed|null
     */
    public function getClass()
    {
        return $this->getProperty('class');
    }

    // --------------------------------------------------------------------------

    /**
     * Set the value of the "placeholder" property
     * @param $sValue string The value to set
     * @return TemplateOption
     */
    public function setPlaceholder($sValue)
    {
        return $this->setProperty('placeholder', $sValue);
    }

    // --------------------------------------------------------------------------

    /**
     * Retrieve the value of the "placeholder" property
     * @return mixed|null
     */
    public function getPlaceholder()
    {
        return $this->getProperty('placeholder');
    }

    // --------------------------------------------------------------------------

    /**
     * Set the value of the "tip" property
     * @param $sValue string The value to set
     * @return TemplateOption
     */
    public function setTip($sValue)
    {
        return $this->setProperty('tip', $sValue);
    }

    // --------------------------------------------------------------------------

    /**
     * Retrieve the value of the "tip" property
     * @return mixed|null
     */
    public function getTip()
    {
        return $this->getProperty('tip');
    }

    // --------------------------------------------------------------------------

    /**
     * Set the value of the "options" property
     * @param $aValue array The options to set
     * @return TemplateOption
     */
    public function setOptions($aValue)
    {
        return $this->setProperty('options', $aValue);
    }

    // --------------------------------------------------------------------------

    /**
     * Retrieve the value of the "options" property
     * @return array|null
     */
    public function getOptions()
    {
        return $this->getProperty('options');
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the class properties as an array
     * @return array
     */
    public function toArray()
    {
        return $this->aFieldData;
    }
}
