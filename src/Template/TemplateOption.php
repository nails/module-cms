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
    public $type        = '';
    public $key         = '';
    public $label       = '';
    public $subLabel    = '';
    public $info        = '';
    public $default     = '';
    public $class       = '';
    public $placeholder = '';
    public $tip         = '';
    public $options     = array();

    // --------------------------------------------------------------------------

    /**
     * Returns the class properties as an array
     * @return array
     */
    public function toArray()
    {
        return array(
            'type'        => $this->type,
            'key'         => $this->key,
            'label'       => $this->label,
            'subLabel'    => $this->subLabel,
            'info'        => $this->info,
            'default'     => $this->default,
            'class'       => $this->class,
            'placeholder' => $this->placeholder,
            'tip'         => $this->tip,
            'options'     => $this->options
        );
    }
}
