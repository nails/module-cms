<?php

namespace Nails\Cms\FindReplace\Resolver;

use Nails\Cms\Admin\Controller;
use Nails\Common\Service\Database;
use Nails\Factory;
use Nails\FindReplace\Factory\Action;
use Nails\FindReplace\Factory\Hit;
use Nails\FindReplace\Factory\Matched;
use Nails\FindReplace\Interfaces\Resolver;

class Menu implements Resolver
{
    /**
     * The engine passes every match in a table at once; a long IN () list
     * overflows CodeIgniter's where_in parser, so IDs are queried in chunks.
     */
    const IDS_PER_QUERY = 500;

    public function tables(): array
    {
        return [
            NAILS_DB_PREFIX . 'cms_menu',
            NAILS_DB_PREFIX . 'cms_menu_item',
        ];
    }

    public function resolve(string $table, array $matches): array
    {
        $ids = array_values(array_unique(array_map(fn(Matched $match): string => $match->getId(), $matches)));

        if ($ids === []) {
            return [];
        }

        return $table === NAILS_DB_PREFIX . 'cms_menu_item'
            ? $this->items($table, $ids)
            : $this->menus($table, $ids);
    }

    /**
     * @param list<string> $ids
     *
     * @return array<string, Hit>
     */
    private function menus(string $table, array $ids): array
    {
        $editUrl = $this->editUrl();
        $hits = [];

        foreach ($this->rows($table, 'id, label', $ids) as $row) {
            $id = (string) $row->id;

            $hits[$id] = new Hit(
                $table,
                $id,
                entityLabel: 'CMS Menu',
                entityId: $id,
                title: (string) $row->label ?: null,
                actions: [
                    new Action('Edit', $editUrl . '/' . $id),
                ],
            );
        }

        return $hits;
    }

    /**
     * Items are edited on their menu's screen.
     *
     * @param list<string> $ids
     *
     * @return array<string, Hit>
     */
    private function items(string $table, array $ids): array
    {
        $items = $this->rows($table, 'id, menu_id, label', $ids);
        $menuIds = array_values(array_unique(array_map(fn(\stdClass $item): string => (string) $item->menu_id, $items)));
        $menus = [];

        if ($menuIds !== []) {
            foreach ($this->rows(NAILS_DB_PREFIX . 'cms_menu', 'id, label', $menuIds) as $menu) {
                $menus[(string) $menu->id] = (string) $menu->label;
            }
        }

        $editUrl = $this->editUrl();
        $hits = [];

        foreach ($items as $item) {
            $id = (string) $item->id;
            $menuId = (string) $item->menu_id;
            $title = array_filter([$menus[$menuId] ?? null, (string) $item->label]);

            $hits[$id] = new Hit(
                $table,
                $id,
                entityLabel: 'CMS Menu Item',
                entityId: $id,
                title: implode(' › ', $title) ?: null,
                actions: [
                    new Action('Edit menu', $editUrl . '/' . $menuId),
                ],
            );
        }

        return $hits;
    }

    /**
     * @param list<string> $ids
     *
     * @return list<\stdClass>
     */
    protected function rows(string $table, string $columns, array $ids): array
    {
        /** @var Database $database */
        $database = Factory::service('Database');
        $rows = [];

        foreach (array_chunk($ids, static::IDS_PER_QUERY) as $chunk) {
            $database->select($columns);
            $database->where_in('id', $chunk);
            $rows = array_merge($rows, $database->get($table)->result());
        }

        return $rows;
    }

    protected function editUrl(): string
    {
        return Controller\Menu::url('edit');
    }
}
