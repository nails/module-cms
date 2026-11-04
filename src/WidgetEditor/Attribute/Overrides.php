<?php

/**
 * Forces a v2 widget to override the widget it extends, even within the same component
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Attribute
 * @author      Nails Dev Team
 */

namespace Nails\Cms\WidgetEditor\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Overrides
{
}
