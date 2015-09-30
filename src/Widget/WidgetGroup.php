<?php

/**
 * Multiple widgets can be grouped together using this class
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Widget;

class WidgetGroup
{
    protected $sLabel;
    protected $aWidgets;

    // --------------------------------------------------------------------------

    /**
     * Construct a new widget group
     * @param string $sLabel The label to give the group
     * @param array $aWidgets An array of widgets to add to the group
     */
    public function __construct($sLabel = '', $aWidgets = array())
    {
        $this->setLabel($sLabel);
        $this->aWidgets = array();

        if (!empty($aWidgets)) {
            foreach ($aWidgets as $oWidget) {
                $this->add($oWidget);
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
     * @param object $oWidget The widget to add
     * @return $this
     */
    public function add($oWidget)
    {
        $this->aWidgets[$oWidget->getSlug()] = $oWidget;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Remove a widget from the group
     * @param object $oWidget The widget to remove
     * @return $this
     */
    public function remove($oWidget)
    {
        $this->aWidgets[$oWidget->getSlug()] = null;
        $this->aWidgets = array_filter($this->aWidgets);
        return $this;
    }
}
