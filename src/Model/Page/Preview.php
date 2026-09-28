<?php

namespace Nails\Cms\Model\Page;

use Nails\Cms\Model\Page;
use Nails\Common\Service\Database;
use Nails\Factory;

/**
 * Class Preview
 *
 * @package Nails\Cms\Model\Page
 */
class Preview extends Page
{
    /**
     * The table this model represents
     *
     * @var string
     */
    const TABLE = NAILS_DB_PREFIX . 'cms_page_preview';

    /**
     * Whether the model is a preview
     *
     * @var bool
     */
    const IS_PREVIEW = true;

    // --------------------------------------------------------------------------

    /**
     * Remove a preview row.
     *
     * Previews are throwaway copies of a page. The parent delete soft-deletes
     * and rewrites the site routes, which must not run against this table.
     *
     * @param int $iId The preview ID
     */
    public function delete($iId): bool
    {
        /** @var Database $oDb */
        $oDb = Factory::service('Database');
        $oDb->where($this->getColumnId(), (int) $iId);

        return (bool) $oDb->delete($this->getTableName());
    }
}
