<?php

/**
 * Represents an editable area of a template
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Template
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Template;

class TemplateArea
{
    protected $title       = '';
    protected $description = '';

    // --------------------------------------------------------------------------

    /**
     * Get the template area's title
     * @return string
     */
    public function setTitle($sTitle)
    {
        $this->title = $sTitle;
    }

    // --------------------------------------------------------------------------

    /**
     * Get the template area's title
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    // --------------------------------------------------------------------------

    /**
     * Get the template area's description
     * @return string
     */
    public function setDescription($sDescription)
    {
        $this->description = $sDescription;
    }

    // --------------------------------------------------------------------------

    /**
     * Get the template area's description
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    // --------------------------------------------------------------------------

    /**
     * Return the template area as an array
     * @return array
     */
    public function toArray()
    {
        return array(
            'title' => $this->title,
            'description' => $this->description
        );
    }
}
