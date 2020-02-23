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

use Nails\Common\Helper\ArrayHelper;
use Nails\Factory;

class Form
{
    public static function cms_widgets_button(array $aConfig)
    {
        $sKey     = ArrayHelper::getFromArray('key', $aConfig, []);
        $sId      = ArrayHelper::getFromArray('id', $aConfig, '');
        $sDefault = ArrayHelper::getFromArray('default', $aConfig, []);

        if (!is_string($sDefault)) {
            $sDefault = json_encode($sDefault) ?? '[]';
        }

        $sDefault = htmlspecialchars(set_value($sKey, $sDefault, false));

        return <<<EOT
        <textarea class="widget-data hidden" name="$sKey" $sId>$sDefault</textarea>
        <button type="button" class="btn btn-primary btn-sm open-editor" data-key="$sKey">
        <span class="fa fa-cogs">&nbsp;</span> Open Widget Editor
        </button>
EOT;
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
        $aField['html'] = static::cms_widgets_button($aField);
        return \Nails\Common\Helper\Form\Field::html($aField, $sTip);
    }
}
