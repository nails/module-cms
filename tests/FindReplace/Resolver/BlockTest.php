<?php

namespace Tests\FindReplace\Resolver;

use Nails\Cms\FindReplace\Resolver\Block;
use Nails\FindReplace\Factory\Matched;
use PHPUnit\Framework\TestCase;

class BlockTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('NAILS_DB_PREFIX')) {
            define('NAILS_DB_PREFIX', 'nails_');
        }
    }

    public function testTables(): void
    {
        self::assertSame([NAILS_DB_PREFIX . 'cms_block'], (new Block())->tables());
    }

    public function testResolvesByLabel(): void
    {
        $table = NAILS_DB_PREFIX . 'cms_block';
        $resolver = $this->resolver([
            (object) ['id' => 5, 'label' => 'Telephone number'],
            (object) ['id' => 6, 'label' => ''],
        ]);

        $hits = $resolver->resolve($table, [
            $this->match($table, '5'),
            $this->match($table, '6'),
            $this->match($table, '9'),
        ]);

        self::assertCount(2, $hits);
        self::assertSame('CMS Block', $hits['5']->getEntityLabel());
        self::assertSame('5', $hits['5']->getEntityId());
        self::assertSame('Telephone number', $hits['5']->getTitle());
        self::assertSame('Edit', $hits['5']->getActions()[0]->getLabel());
        self::assertSame('https://example.com/admin/cms/block/edit/5', $hits['5']->getActions()[0]->getUrl());
        self::assertNull($hits['6']->getTitle());
    }

    public function testNoMatchesResolvesNothing(): void
    {
        self::assertSame([], $this->resolver([])->resolve(NAILS_DB_PREFIX . 'cms_block', []));
    }

    private function match(string $table, string $id): Matched
    {
        return new Matched($table, 'label', 'plaintext', 'example', $id, 'example');
    }

    /**
     * @param list<\stdClass> $rows
     */
    private function resolver(array $rows): Block
    {
        return new class ($rows) extends Block {
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
                return 'https://example.com/admin/cms/block/edit';
            }
        };
    }
}
