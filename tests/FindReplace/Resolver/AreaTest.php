<?php

namespace Tests\FindReplace\Resolver;

use Nails\Cms\FindReplace\Resolver\Area;
use Nails\FindReplace\Factory\Matched;
use PHPUnit\Framework\TestCase;

class AreaTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('NAILS_DB_PREFIX')) {
            define('NAILS_DB_PREFIX', 'nails_');
        }
    }

    public function testTables(): void
    {
        self::assertSame([NAILS_DB_PREFIX . 'cms_area'], (new Area())->tables());
    }

    public function testResolvesByLabel(): void
    {
        $table = NAILS_DB_PREFIX . 'cms_area';
        $resolver = $this->resolver([
            (object) ['id' => 5, 'label' => 'Footer'],
            (object) ['id' => 6, 'label' => ''],
        ]);

        $hits = $resolver->resolve($table, [
            $this->match($table, '5'),
            $this->match($table, '6'),
            $this->match($table, '9'),
        ]);

        self::assertCount(2, $hits);
        self::assertSame('CMS Area', $hits['5']->getEntityLabel());
        self::assertSame('5', $hits['5']->getEntityId());
        self::assertSame('Footer', $hits['5']->getTitle());
        self::assertSame('Edit', $hits['5']->getActions()[0]->getLabel());
        self::assertSame('https://example.com/admin/cms/area/edit/5', $hits['5']->getActions()[0]->getUrl());
        self::assertNull($hits['6']->getTitle());
    }

    public function testNoMatchesResolvesNothing(): void
    {
        self::assertSame([], $this->resolver([])->resolve(NAILS_DB_PREFIX . 'cms_area', []));
    }

    private function match(string $table, string $id): Matched
    {
        return new Matched($table, 'label', 'plaintext', 'example', $id, 'example');
    }

    /**
     * @param list<\stdClass> $rows
     */
    private function resolver(array $rows): Area
    {
        return new class ($rows) extends Area {
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
                return 'https://example.com/admin/cms/area/edit';
            }
        };
    }
}
