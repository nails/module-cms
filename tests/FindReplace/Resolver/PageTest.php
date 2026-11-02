<?php

namespace Tests\FindReplace\Resolver;

use Nails\Cms\FindReplace\Resolver\Page;
use Nails\FindReplace\Factory\Matched;
use PHPUnit\Framework\TestCase;

class PageTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('NAILS_DB_PREFIX')) {
            define('NAILS_DB_PREFIX', 'nails_');
        }
    }

    public function testTables(): void
    {
        self::assertSame([NAILS_DB_PREFIX . 'cms_page'], (new Page())->tables());
    }

    public function testFacets(): void
    {
        $resolver = new Page();

        self::assertSame(['status' => 'Status'], $resolver->facets());
        self::assertSame(['status' => ['PUBLISHED', 'DRAFT', 'DELETED']], $resolver->values());
        self::assertSame(['status'], $resolver->badges());
    }

    public function testResolvesPublishedDraftAndDeletedPages(): void
    {
        $table = NAILS_DB_PREFIX . 'cms_page';
        $resolver = $this->resolver([
            (object) ['id' => 1, 'published_title' => 'About', 'draft_title' => 'About us', 'is_published' => '1', 'is_deleted' => '0'],
            (object) ['id' => 2, 'published_title' => '', 'draft_title' => 'Coming soon', 'is_published' => '0', 'is_deleted' => '0'],
            (object) ['id' => 3, 'published_title' => 'Old', 'draft_title' => '', 'is_published' => '1', 'is_deleted' => '1'],
        ]);

        $hits = $resolver->resolve($table, [
            $this->match($table, '1', 'published_template_data'),
            $this->match($table, '1', 'draft_template_data'),
            $this->match($table, '2', 'draft_title'),
            $this->match($table, '3', 'published_title'),
        ]);

        self::assertCount(3, $hits);

        self::assertSame('CMS Page', $hits['1']->getEntityLabel());
        self::assertSame('1', $hits['1']->getEntityId());
        self::assertSame('About', $hits['1']->getTitle());
        self::assertSame(['status' => 'PUBLISHED'], $hits['1']->getFacets());
        self::assertSame('https://example.com/admin/cms/pages/edit/1', $hits['1']->getActions()[0]->getUrl());

        self::assertSame('Coming soon', $hits['2']->getTitle());
        self::assertSame(['status' => 'DRAFT'], $hits['2']->getFacets());
        self::assertCount(1, $hits['2']->getActions());

        self::assertSame('Old', $hits['3']->getTitle());
        self::assertSame(['status' => 'DELETED'], $hits['3']->getFacets());
        self::assertSame([], $hits['3']->getActions());
    }

    public function testUntitledPageHasNoTitle(): void
    {
        $table = NAILS_DB_PREFIX . 'cms_page';
        $resolver = $this->resolver([
            (object) ['id' => 4, 'published_title' => '', 'draft_title' => '', 'is_published' => '0', 'is_deleted' => '0'],
        ]);

        $hits = $resolver->resolve($table, [$this->match($table, '4', 'draft_seo_title')]);

        self::assertNull($hits['4']->getTitle());
    }

    public function testNoMatchesResolvesNothing(): void
    {
        self::assertSame([], $this->resolver([])->resolve(NAILS_DB_PREFIX . 'cms_page', []));
    }

    private function match(string $table, string $id, string $column): Matched
    {
        return new Matched($table, $column, 'plaintext', 'example', $id, 'example');
    }

    /**
     * @param list<\stdClass> $rows
     */
    private function resolver(array $rows): Page
    {
        return new class ($rows) extends Page {
            /**
             * @param list<\stdClass> $stub
             */
            public function __construct(private readonly array $stub)
            {
            }

            protected function rows(string $table, string $columns, array $ids): array
            {
                return $this->stub;
            }

            protected function editUrl(): string
            {
                return 'https://example.com/admin/cms/pages/edit';
            }
        };
    }
}
