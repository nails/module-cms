<?php

namespace Nails\Cms\FindReplace\Resolver;

use Nails\Cms\Admin\Controller;
use Nails\Common\Service\Database;
use Nails\Factory;
use Nails\FindReplace\Factory\Action;
use Nails\FindReplace\Factory\Hit;
use Nails\FindReplace\Factory\Matched;
use Nails\FindReplace\Interfaces\Resolver;
use Nails\FindReplace\Interfaces\Resolver\Badges;
use Nails\FindReplace\Interfaces\Resolver\FacetValues;
use Nails\FindReplace\Interfaces\Resolver\Facets;

class Page implements Resolver, Facets, FacetValues, Badges
{
    /**
     * The engine passes every match in a table at once; a long IN () list
     * overflows CodeIgniter's where_in parser, so IDs are queried in chunks.
     */
    const IDS_PER_QUERY = 500;

    const STATUS_PUBLISHED = 'PUBLISHED';

    const STATUS_DRAFT = 'DRAFT';

    const STATUS_DELETED = 'DELETED';

    public function tables(): array
    {
        return [
            NAILS_DB_PREFIX . 'cms_page',
        ];
    }

    public function resolve(string $table, array $matches): array
    {
        $ids = array_values(array_unique(array_map(fn(Matched $match): string => $match->getId(), $matches)));

        if ($ids === []) {
            return [];
        }

        $editUrl = $this->editUrl();
        $hits = [];

        foreach ($this->rows($table, 'id, published_title, draft_title, is_published, is_deleted', $ids) as $row) {
            $id = (string) $row->id;
            $status = match (true) {
                (bool) $row->is_deleted => self::STATUS_DELETED,
                (bool) $row->is_published => self::STATUS_PUBLISHED,
                default => self::STATUS_DRAFT,
            };

            //  Deleted pages cannot be opened in the editor.
            $actions = $status === self::STATUS_DELETED
                ? []
                : [new Action('Edit', $editUrl . '/' . $id)];

            $hits[$id] = new Hit(
                $table,
                $id,
                entityLabel: 'CMS Page',
                entityId: $id,
                title: $this->title($row),
                actions: $actions,
                facets: [
                    'status' => $status,
                ],
            );
        }

        return $hits;
    }

    public function facets(): array
    {
        return [
            'status' => 'Status',
        ];
    }

    public function values(): array
    {
        return [
            'status' => [
                self::STATUS_PUBLISHED,
                self::STATUS_DRAFT,
                self::STATUS_DELETED,
            ],
        ];
    }

    public function badges(): array
    {
        return ['status'];
    }

    /**
     * The published title for a published page, otherwise the draft title.
     */
    private function title(\stdClass $row): ?string
    {
        $published = (string) $row->published_title;
        $draft = (string) $row->draft_title;

        if ($row->is_published && $published !== '') {
            return $published;
        }

        return ($draft !== '' ? $draft : $published) ?: null;
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
        return Controller\Pages::url('edit');
    }
}
