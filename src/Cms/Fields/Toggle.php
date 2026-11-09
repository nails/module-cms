<?php

/**
 * A checkbox, stored as a boolean. A toggle is never empty: off is a value.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Field
 * @author      Nails Dev Team
 */

namespace Nails\Cms\Cms\Fields;

use Nails\Cms\WidgetEditor\Field\Base;

class Toggle extends Base
{
    public static function getType(): string
    {
        return 'toggle';
    }

    // --------------------------------------------------------------------------

    protected function defaultValue(): mixed
    {
        return false;
    }
}
