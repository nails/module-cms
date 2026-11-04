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
use Nails\Cms\WidgetEditor\Service\Version;
use Nails\Common\Helper\ArrayHelper;
use Nails\Factory;

class Form
{
    /**
     * The following constants represent the various field types available.
     */
    const FIELD_WIDGETS = 'cms_widgets';

    /** The version config is emitted once per request, however many widget fields the page has */
    private static bool $configEmitted = false;

    // --------------------------------------------------------------------------

    public static function cms_widgets_button(array $aConfig)
    {
        $sKey        = ArrayHelper::get('key', $aConfig, []);
        $sId         = ArrayHelper::get('id', $aConfig, '');
        $sDefault    = ArrayHelper::get('default', $aConfig, []);
        $sButtonIcon = ArrayHelper::get('button_text', $aConfig, 'fa-cogs');
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
            . static::editorConfig();
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
     * The editor versions this page should use, and the v2 bundle when that version is enabled
     */
    private static function editorConfig(): string
    {
        if (self::$configEmitted) {
            return '';
        }
        self::$configEmitted = true;

        /** @var Version $version */
        $version = Factory::service('WidgetEditorVersion', Constants::MODULE_SLUG);
        $enabled = $version->getEnabledVersions();
        $active  = $version->getDefault();

        $stylesheet = '';
        if (in_array(Version::V2, $enabled, true)) {
            /** @var \Nails\Common\Service\Asset $assets */
            $assets = Factory::service('Asset');
            $assets
                ->vue3()
                ->load('admin.widget-editor.min.js', Constants::MODULE_SLUG)
                ->load('admin.widget-editor.min.css', Constants::MODULE_SLUG);

            // The field renders in the body, after the head has printed its stylesheets, so a queued
            // stylesheet never reaches the page. Emit the link with the field.
            foreach ($assets->getLoaded()->CSS as $url) {
                if (is_string($url) && str_contains($url, 'admin.widget-editor.min.css')) {
                    $stylesheet = '<link rel="stylesheet" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">';
                    break;
                }
            }
        }

        $json = json_encode(
            ['enabled' => $enabled, 'active' => $active],
            JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP
        );

        return $stylesheet . '<script type="application/json" id="cms-widget-editor-config">' . $json . '</script>';
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
