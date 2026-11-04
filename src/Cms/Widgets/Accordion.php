<?php

/**
 * The "Accordion" widget for WidgetEditor (v2). Bridged to the v1 "accordion" widget, which keeps its editor and renderer.
 * The stored slug `accordion` resolves through the bridge's alias.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Cms\Widgets;

use Nails\Cms\WidgetEditor\Traits\Legacy;
use Nails\Cms\WidgetEditor\Widget\Base;

class Accordion extends Base
{
    use Legacy;

    protected static function legacyClass(): string
    {
        return \Nails\Cms\Cms\Widget\Accordion::class;
    }
}
