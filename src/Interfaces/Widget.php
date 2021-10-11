<?php

namespace Nails\Cms\Interfaces;

/**
 * Interface Widget
 *
 * @package Nails\Cms\Interfaces
 */
interface Widget
{
    /**
     * The different types of asset
     */
    const ASSETS_EDITOR = 'EDITOR';
    const ASSETS_RENDER = 'RENDER';

    // --------------------------------------------------------------------------

    /**
     * Returns whether the widget is disabled
     *
     * @return bool
     */
    public static function isDisabled(): bool;

    // --------------------------------------------------------------------------

    /**
     * Returns whether the widget is hidden
     *
     * @return bool
     */
    public static function isHidden(): bool;

    // --------------------------------------------------------------------------

    /**
     * Whether the widget is deprecated or not
     *
     * @return bool
     */
    public static function isDeprecated(): bool;

    // --------------------------------------------------------------------------

    /**
     * When deprecated, an alternative widget to use
     *
     * @return string
     */
    public static function alternative(): string;

    // --------------------------------------------------------------------------

    /**
     * Detects the path of the called class
     *
     * @return string
     */
    public static function detectPath(): ?string;

    // --------------------------------------------------------------------------

    /**
     * Looks for a file in the widget hierarchy and returns it if found
     *
     * @param string $sFile The file name to look for
     *
     * @return string|null
     */
    public static function getFilePath($sFile);

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's label
     *
     * @return string
     */
    public function getLabel(): string;

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's icon
     *
     * @return string
     */
    public function getIcon(): string;

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's description
     *
     * @return string
     */
    public function getDescription(): string;

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's keywords
     *
     * @return string
     */
    public function getKeywords(): string;

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's grouping
     *
     * @return string
     */
    public function getGrouping(): string;

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's slug
     *
     * @return string
     */
    public function getSlug();

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's screenshot
     *
     * @return string
     */
    public function getScreenshot(): string;

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's path
     *
     * @return string
     */
    public function getPath(): string;

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's render assets
     *
     * @return string[]
     */
    public function getRenderAssets(): array;

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's editor assets
     *
     * @return string[]
     */
    public function getEditorAssets(): array;

    // --------------------------------------------------------------------------

    /**
     * Returns the widget's callbacks
     *
     * @param string $sType The type of callback to return
     *
     * @return mixed
     */
    public function getCallbacks(string $sType = '');

    // --------------------------------------------------------------------------

    /**
     * Returns the HTML for the editor view. Any passed data will be used to
     * populate the values of the form elements.
     *
     * @param array $aWidgetData The data to render the widget editor with
     *
     * @return string
     */
    public function getEditor(array $aWidgetData = []): string;

    // --------------------------------------------------------------------------

    /**
     * Renders the widget with the provided data.
     *
     * @param array $aWidgetData The data to render the widget with
     *
     * @return string
     */
    public function render(array $aWidgetData = []): string;

    // --------------------------------------------------------------------------

    /**
     * Format the widget as a JSON object
     *
     * @param int $iJsonOptions
     * @param int $iJsonDepth
     *
     * @return string
     */
    public function toJson(int $iJsonOptions = 0, int $iJsonDepth = 512);

    // --------------------------------------------------------------------------

    /**
     * Format the widget as an array
     *
     * @return array
     */
    public function toArray(): array;
}
