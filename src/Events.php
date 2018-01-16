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
     * Fired when a CMS Page is created
     *
     * @param integer $iId The Page ID
     */
    const PAGE_CREATED = 'PAGE:CREATED';

    /**
     * Fired when a CMS Page is updated
     *
     * @param integer   $iId        The Page ID
     * @param \stdClass $oOldObject The old Page object
     */
    const PAGE_UPDATED = 'PAGE:UPDATED';

    /**
     * Fired when a CMS Page is deleted
     *
     * @param integer $iId The Page ID
     */
    const PAGE_DELETED = 'PAGE:DELETED';

    /**
     * Fired when a CMS Page is published
     *
     * @param integer $iId The Page ID
     */
    const PAGE_PUBLISHED = 'PAGE:PUBLISHED';

    /**
     * Fired when a CMS Page is unpublished
     *
     * @param integer $iId The Page ID
     */
    const PAGE_UNPUBLISHED = 'PAGE:UNPUBLISHED';
}
