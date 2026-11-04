<?php

/**
 * Forces a v2 widget which extends another widget to be a separate widget, even from a higher-precedence component
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Attribute
 * @author      Nails Dev Team
 */

namespace Nails\Cms\WidgetEditor\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class DoesNotOverride
{
}
