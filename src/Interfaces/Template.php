<?php

namespace Nails\Cms\Interfaces;

/**
 * Interface Template
 *
 * @package Nails\Cms\Interfaces
 */
interface Template
{
    public static function isDisabled();

    public static function isDefault();

    public static function detectPath();

    public static function getFilePath($sFile);

    public function getLabel();

    public function getDescription();

    public function getGrouping();

    public function getWidgetAreas();

    public function getAdditionalFields();

    public function getManualConfig();

    public function getIcon();

    public function getSlug();

    public function getPath();

    public function getAssets($sType);

    public function render(array $aTplData = [], array $aTplOptions = []);

    public function toJson($iJsonOptions = 0, $iJsonDepth = 512);

    public function toArray();
}
