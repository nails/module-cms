<?php

namespace Nails\Cms\Interfaces;

/**
 * Interface Widget
 *
 * @package Nails\Cms\Interfaces
 */
interface Widget
{
    public static function isDisabled(): bool;

    public static function isHidden(): bool;

    public static function detectPath();

    public static function getFilePath($sFile);

    public function getLabel();

    public function getIcon();

    public function getDescription();

    public function getKeywords();

    public function getGrouping();

    public function getSlug();

    public function getScreenshot();

    public function getPath();

    public function getAssets($sType);

    public function getCallbacks($sType = '');

    public function getEditor(array $aWidgetData = []);

    public function render(array $aWidgetData = []);

    public function toJson($iJsonOptions = 0, $iJsonDepth = 512);

    public function toArray();
}
