<?php

namespace Nails\Cms\FindReplace\Exclusion;

use Nails\FindReplace\Interfaces\Exclusion;

class Package implements Exclusion
{
    public function hidden(): array
    {
        return [
            NAILS_DB_PREFIX . 'cms_page_slug_history',
            NAILS_DB_PREFIX . 'cms_page_preview',
            NAILS_DB_PREFIX . 'cms_page.draft_hash',
            NAILS_DB_PREFIX . 'cms_page.published_hash',
        ];
    }

    public function readOnly(): array
    {
        return [
            NAILS_DB_PREFIX . 'cms_page.draft_slug',
            NAILS_DB_PREFIX . 'cms_page.draft_slug_end',
            NAILS_DB_PREFIX . 'cms_page.published_slug',
            NAILS_DB_PREFIX . 'cms_page.published_slug_end',
            NAILS_DB_PREFIX . 'cms_page.draft_template',
            NAILS_DB_PREFIX . 'cms_page.published_template',
            //  Built from parent page titles; a direct edit is overwritten or goes stale.
            NAILS_DB_PREFIX . 'cms_page.draft_breadcrumbs',
            NAILS_DB_PREFIX . 'cms_page.published_breadcrumbs',
        ];
    }
}
