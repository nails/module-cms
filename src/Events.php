<?php

/**
 * This class provides a summary of the events fired by the CMS module
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Events
 * @author      Nails Dev Team
 */

namespace Nails\Cms;

use Nails\Common\Events\Base;

class Events extends Base
{
    /**
     * Fired when a CMS Page is published
     *
     * @param int $iId The Page ID
     */
    const PAGE_PUBLISHED = 'PAGE:PUBLISHED';

    /**
     * Fired when a CMS Page is unpublished
     *
     * @param int $iId The Page ID
     */
    const PAGE_UNPUBLISHED = 'PAGE:UNPUBLISHED';
}
