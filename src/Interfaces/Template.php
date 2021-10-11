<?php

namespace Nails\Cms\Interfaces;

/**
 * Interface Template
 *
 * @package Nails\Cms\Interfaces
 */
interface Template
{
    /**
     * The different types of asset
     */
    const ASSETS_EDITOR = 'EDITOR';
    const ASSETS_RENDER = 'RENDER';

    // --------------------------------------------------------------------------

    /**
     * Returns whether the template is disabled
     *
     * @return bool
     */
    public static function isDisabled(): bool;

    // --------------------------------------------------------------------------

    /**
     * Returns whether the template is a default template or not
     *
     * @return bool
     */
    public static function isDefault(): bool;

    // --------------------------------------------------------------------------

    /**
     * Detects the path of the called class
     *
     * @return string
     */
    public static function detectPath(): string;

    // --------------------------------------------------------------------------

    /**
     * Looks for a file in the widget hierarchy and returns it if found
     *
     * @param string $sFile The file name to look for
     *
     * @return string|null
     */
    public static function getFilePath(string $sFile): ?string;

    // --------------------------------------------------------------------------

    /**
     * Returns the template's label
     *
     * @return string
     */
    public function getLabel(): string;

    // --------------------------------------------------------------------------

    /**
     * Returns the template's description
     *
     * @return string
     */
    public function getDescription(): string;

    // --------------------------------------------------------------------------

    /**
     * Returns the template's grouping
     *
     * @return string
     */
    public function getGrouping(): string;

    // --------------------------------------------------------------------------

    /**
     * Returns the template's widget areas
     *
     * @return array
     */
    public function getWidgetAreas();

    // --------------------------------------------------------------------------

    /**
     * Returns the template's additional fields
     *
     * @return array
     */
    public function getAdditionalFields(): array;

    // --------------------------------------------------------------------------

    /**
     * Returns the template's manual config
     *
     * @return string
     */
    public function getManualConfig(): string;

    // --------------------------------------------------------------------------

    /**
     * Returns the template's icon
     *
     * @return string
     */
    public function getIcon(): string;

    // --------------------------------------------------------------------------

    /**
     * Returns the template's slug
     *
     * @return string
     */
    public function getSlug(): string;

    // --------------------------------------------------------------------------

    /**
     * Returns the template's path
     *
     * @return string
     */
    public function getPath(): string;

    // --------------------------------------------------------------------------

    /**
     * Returns the template's render assets
     *
     * @return string[]
     */
    public function getRenderAssets(): array;

    // --------------------------------------------------------------------------

    /**
     * Returns the template's editor assets
     *
     * @return string[]
     */
    public function getEditorAssets(): array;

    // --------------------------------------------------------------------------

    /**
     * Renders the template with the provided data.
     *
     * @param array $aTplData    The widgets to include in the template
     * @param array $aTplOptions Additional data created by the template
     *
     * @return string
     */
    public function render(array $aTplData = [], array $aTplOptions = []): string;

    // --------------------------------------------------------------------------

    /**
     * Format the template as a JSON object
     *
     * @param int $iJsonOptions The JSON options
     * @param int $iJsonDepth   The JSON depth
     *
     * @return string
     */
    public function toJson(int $iJsonOptions = 0, int $iJsonDepth = 512): string;

    // --------------------------------------------------------------------------

    /**
     * Format the template as an array
     *
     * @return array
     */
    public function toArray(): array;
}
