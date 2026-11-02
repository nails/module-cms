<?php

namespace Tests\FindReplace\Resolver;

use Nails\Cms\FindReplace\Resolver\Menu;
use Nails\FindReplace\Factory\Matched;
use PHPUnit\Framework\TestCase;

class MenuTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('NAILS_DB_PREFIX')) {
            define('NAILS_DB_PREFIX', 'nails_');
        }
    }

    public function testTables(): void
    {
        self::assertSame(
            [NAILS_DB_PREFIX . 'cms_menu', NAILS_DB_PREFIX . 'cms_menu_item'],
            (new Menu())->tables()
        );
    }

    public function testResolvesMenus(): void
    {
        $table = NAILS_DB_PREFIX . 'cms_menu';
        $resolver = $this->resolver([
            $table => [(object) ['id' => 2, 'label' => 'Main']],
        ]);

        $hits = $resolver->resolve($table, [$this->match($table, '2', 'description')]);

        self::assertSame('CMS Menu', $hits['2']->getEntityLabel());
        self::assertSame('Main', $hits['2']->getTitle());
        self::assertSame('https://example.com/admin/cms/menu/edit/2', $hits['2']->getActions()[0]->getUrl());
    }

    public function testResolvesItemsThroughTheirMenu(): void
    {
        $table = NAILS_DB_PREFIX . 'cms_menu_item';
        $resolver = $this->resolver([
            $table => [
                (object) ['id' => 10, 'menu_id' => 2, 'label' => 'Contact'],
                (object) ['id' => 11, 'menu_id' => 3, 'label' => 'Orphan'],
            ],
            NAILS_DB_PREFIX . 'cms_menu' => [
                (object) ['id' => 2, 'label' => 'Main'],
            ],
        ]);

        $hits = $resolver->resolve($table, [
            $this->match($table, '10', 'url'),
            $this->match($table, '10', 'label'),
            $this->match($table, '11', 'url'),
        ]);

        self::assertCount(2, $hits);
        self::assertSame('CMS Menu Item', $hits['10']->getEntityLabel());
        self::assertSame('10', $hits['10']->getEntityId());
        self::assertSame('Main › Contact', $hits['10']->getTitle());
        self::assertSame('Edit menu', $hits['10']->getActions()[0]->getLabel());
        self::assertSame('https://example.com/admin/cms/menu/edit/2', $hits['10']->getActions()[0]->getUrl());
        self::assertSame('Orphan', $hits['11']->getTitle());
    }

    private function match(string $table, string $id, string $column): Matched
    {
        return new Matched($table, $column, 'plaintext', 'example', $id, 'example');
    }

    /**
     * @param array<string, list<\stdClass>> $rows Keyed by table.
     */
    private function resolver(array $rows): Menu
    {
        return new class ($rows) extends Menu {
            /**
             * @param array<string, list<\stdClass>> $stub
             */
            public function __construct(private readonly array $stub)
            {
            }

            protected function rows(string $table, string $columns, array $ids): array
            {
                return array_values(array_filter(
                    $this->stub[$table] ?? [],
                    fn(\stdClass $row): bool => in_array((string) $row->id, $ids, true)
                ));
            }

            protected function editUrl(): string
            {
                return 'https://example.com/admin/cms/menu/edit';
            }
        };
    }
}
