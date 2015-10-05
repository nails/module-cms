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
    protected $view        = '';

    // --------------------------------------------------------------------------

    public function setTitle($sTitle)
    {
        $this->title = $sTitle;
    }

    // --------------------------------------------------------------------------

    public function getTitle()
    {
        return $this->title;
    }

    // --------------------------------------------------------------------------

    public function setDescription($sDescription)
    {
        $this->description = $sDescription;
    }

    // --------------------------------------------------------------------------

    public function getDescription()
    {
        return $this->description;
    }

    // --------------------------------------------------------------------------

    public function setView    ($sView)
    {
        $this->view = $sView;
    }

    // --------------------------------------------------------------------------

    public function getView()
    {
        return $this->view;
    }

    // --------------------------------------------------------------------------

    public function toArray()
    {
        return array(
            'title' => $this->title,
            'description' => $this->description,
            'view' => $this->view
        );
    }
}
