<?php

/**
 * Form helper
 *
 * @package     Nails
 * @subpackage  nails/module-cms
 * @category    Helper
 * @author      Nails Dev Team
 */

namespace Nails\Cms\Helper;

use Nails\Cms\Constants;
use Nails\Cms\WidgetEditor\Service\FieldTypes;
use Nails\Common\Helper\ArrayHelper;
use Nails\Factory;

class Form
{
    /**
     * The following constants represent the various field types available.
     */
    const FIELD_WIDGETS = 'cms_widgets';

    /** The editor assets are emitted once per request, however many widget fields the page has */
    private static bool $assetsEmitted = false;

    // --------------------------------------------------------------------------

    public static function cms_widgets_button(array $aConfig)
    {
        $sKey        = ArrayHelper::get('key', $aConfig, []);
        $sId         = ArrayHelper::get('id', $aConfig, '');
        $sDefault    = ArrayHelper::get('default', $aConfig, []);
        $sButtonIcon = ArrayHelper::get('button_icon', $aConfig, 'fa-puzzle-piece');
        $sButtonText = ArrayHelper::get('button_text', $aConfig, 'Open Widget Editor');

        if (!is_string($sDefault)) {
            $sDefault = json_encode($sDefault) ?? '[]';
        }

        $sDefault = htmlspecialchars(set_value($sKey, $sDefault, false));
        $sRules   = static::rulesAttribute($aConfig);

        return <<<EOT
        <textarea class="widget-data hidden" name="$sKey" $sId>$sDefault</textarea>
        <button type="button" class="btn btn-primary btn-sm open-editor" data-key="$sKey"$sRules>
        <span class="fa $sButtonIcon">&nbsp;</span> $sButtonText
        </button>
        EOT
            . static::editorAssets();
    }

    // --------------------------------------------------------------------------

    /**
     * `data-cms-rules` for an area that limits which widgets can be inserted
     *
     * @param array{allowed?: mixed, disallowed?: mixed} $config
     */
    public static function rulesAttribute(array $config): string
    {
        $rules = [];
        foreach (['allowed', 'disallowed'] as $key) {
            if (isset($config[$key]) && is_array($config[$key])) {
                $rules[$key] = array_values(array_filter($config[$key], 'is_string'));
            }
        }

        if ($rules === []) {
            return '';
        }

        return ' data-cms-rules="' . htmlspecialchars(
            json_encode($rules, JSON_THROW_ON_ERROR),
            ENT_QUOTES,
            'UTF-8'
        ) . '"';
    }

    // --------------------------------------------------------------------------

    /**
     * The editor bundle, Vue 3, and each field type's assets. Emitted once, with the first widget field.
     */
    private static function editorAssets(): string
    {
        if (self::$assetsEmitted) {
            return '';
        }
        self::$assetsEmitted = true;

        /** @var \Nails\Common\Service\Asset $assets */
        $assets = Factory::service('Asset');
        $before = array_values((array) $assets->getLoaded()->CSS);

        $assets
            ->vue3()
            ->load('admin.widget-editor.min.js', Constants::MODULE_SLUG)
            ->load('admin.widget-editor.min.css', Constants::MODULE_SLUG);

        // Each field type's control registers itself with the editor; see Interfaces\Field::getAssets()
        /** @var FieldTypes $fieldTypes */
        $fieldTypes = Factory::service('WidgetEditorFieldTypes', Constants::MODULE_SLUG);
        foreach ($fieldTypes->assets() as $asset) {
            is_array($asset)
                ? $assets->load($asset[0], $asset[1] ?? null)
                : $assets->load($asset);
        }

        // The field renders in the body, after the head has printed its stylesheets, so a queued
        // stylesheet never reaches the page. Emit the links with the field.
        $stylesheet = '';
        foreach (array_values((array) $assets->getLoaded()->CSS) as $url) {
            if (is_string($url) && !in_array($url, $before, true)) {
                $stylesheet .= '<link rel="stylesheet" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">';
            }
        }

        return $stylesheet;
    }

    // --------------------------------------------------------------------------

    /**
     * Generates a form field containing a CMS Widget editor
     *
     * @param array  $aField The config array
     * @param string $sTip   An optional tip (DEPRECATED: use $aField['tip'] instead)
     *
     * @return string
     *
     * @todo (Pablo - 2020-01-15) - Replace this using \Nails\Admin\Service\Form
     */
    public static function form_field_cms_widgets($aField, $sTip = ''): string
    {
        $aField['type'] = 'cms-widgets';
        $aField['html'] = static::cms_widgets_button($aField);
        return \Nails\Common\Helper\Form\Field::html($aField, $sTip);
    }
}
