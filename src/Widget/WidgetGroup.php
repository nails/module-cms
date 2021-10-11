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

use Nails\Cms\Interfaces;

class WidgetGroup
{
    /** @var string */
    protected $sLabel;

    /** @var Interfaces\Widget[] */
    protected $aWidgets = [];

    // --------------------------------------------------------------------------

    /**
     * Construct a new widget group
     *
     * @param string              $sLabel   The label to give the group
     * @param Interfaces\Widget[] $aWidgets An array of widgets to add to the group
     */
    public function __construct(string $sLabel = '', array $aWidgets = [])
    {
        $this->setLabel($sLabel);

        foreach ($aWidgets as $oWidget) {
            $this->add($oWidget);
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
     * Add a widget to the group
     *
     * @param Interfaces\Widget $oWidget The widget to add
     *
     * @return $this
     */
    public function add(Interfaces\Widget $oWidget): self
    {
        $this->aWidgets[$oWidget->getSlug()] = $oWidget;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Remove a widget from the group
     *
     * @param Interfaces\Widget $oWidget The widget to remove
     *
     * @return $this
     */
    public function remove(Interfaces\Widget $oWidget): self
    {
        $this->aWidgets[$oWidget->getSlug()] = null;
        $this->aWidgets                      = array_filter($this->aWidgets);
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Return the widgets in the group
     *
     * @return Interfaces\Widget[]
     */
    public function getWidgets(): array
    {
        return $this->aWidgets;
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
                'label'   => $this->getLabel(),
                'widgets' => array_map(function (Interfaces\Widget $oWidget) {
                    return $oWidget->toArray();
                }, $this->getWidgets()),
            ],
            $iJsonOptions,
            $iJsonDepth
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Get the widgets as JSON
     *
     * @param int $iJsonOptions
     * @param int $iJsonDepth
     *
     * @return string
     */
    public function getWidgetsAsJson(int $iJsonOptions = 0, int $iJsonDepth = 512): string
    {
        $aWidgetsJson = [];
        $aWidgets     = $this->getWidgets();

        foreach ($aWidgets as $oWidget) {
            $aWidgetsJson[] = $oWidget->toJson($iJsonOptions, $iJsonDepth);
        }

        return '[' . implode(',', $aWidgetsJson) . ']';
    }
}
