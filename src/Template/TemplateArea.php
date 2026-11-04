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

    /** @var string[]|null Slugs, aliases, or `group:<label>`. Null means the area does not restrict inserts. */
    protected ?array $allowed = null;

    /** @var string[]|null */
    protected ?array $disallowed = null;

    // --------------------------------------------------------------------------

    /**
     * Get the template area's title
     * @return string
     */
    public function setTitle($sTitle)
    {
        $this->title = $sTitle;
        return $this;
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
        return $this;
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
     * Widgets that may be inserted into this area. Entries are slugs, aliases, or `group:<label>`.
     *
     * @param string[]|null $allowed
     */
    public function setAllowed(?array $allowed): static
    {
        $this->allowed = $allowed;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * @return string[]|null
     */
    public function getAllowed(): ?array
    {
        return $this->allowed;
    }

    // --------------------------------------------------------------------------

    /**
     * Widgets that may not be inserted into this area, applied after `allowed`.
     *
     * @param string[]|null $disallowed
     */
    public function setDisallowed(?array $disallowed): static
    {
        $this->disallowed = $disallowed;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * @return string[]|null
     */
    public function getDisallowed(): ?array
    {
        return $this->disallowed;
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
