<?php

/**
 * Widget status for the WidgetEditor (v2)
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Enum
 * @author      Nails Dev Team
 */

namespace Nails\Cms\WidgetEditor;

enum Status: string
{
    /** Offered in the organiser, editable, renders */
    case ACTIVE = 'active';

    /** Not offered in the organiser; existing instances editable and render */
    case HIDDEN = 'hidden';

    /** Not offered in the organiser; existing instances editable with a warning, render */
    case DEPRECATED = 'deprecated';

    /** Never instantiated for the editor or the front end; instances are preserved verbatim */
    case DISABLED = 'disabled';

    // --------------------------------------------------------------------------

    /**
     * Whether the widget renders on the front end (and in previews)
     */
    public function renders(): bool
    {
        return $this !== self::DISABLED;
    }
}
