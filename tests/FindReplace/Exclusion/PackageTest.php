<?php

namespace Tests\FindReplace\Exclusion;

use Nails\Cms\FindReplace\Exclusion\Package;
use PHPUnit\Framework\TestCase;

class PackageTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('NAILS_DB_PREFIX')) {
            define('NAILS_DB_PREFIX', 'nails_');
        }
    }

    public function testHidden(): void
    {
        self::assertSame(
            [
                NAILS_DB_PREFIX . 'cms_page_slug_history',
                NAILS_DB_PREFIX . 'cms_page_preview',
                NAILS_DB_PREFIX . 'cms_page.draft_hash',
                NAILS_DB_PREFIX . 'cms_page.published_hash',
            ],
            (new Package())->hidden()
        );
    }

    public function testReadOnly(): void
    {
        self::assertSame(
            [
                NAILS_DB_PREFIX . 'cms_page.draft_slug',
                NAILS_DB_PREFIX . 'cms_page.draft_slug_end',
                NAILS_DB_PREFIX . 'cms_page.published_slug',
                NAILS_DB_PREFIX . 'cms_page.published_slug_end',
                NAILS_DB_PREFIX . 'cms_page.draft_template',
                NAILS_DB_PREFIX . 'cms_page.published_template',
                NAILS_DB_PREFIX . 'cms_menu_item.url',
            ],
            (new Package())->readOnly()
        );
    }
}
