<?php

/**
 * A user's favourite WidgetEditor widgets
 *
 * Rows, not a JSON blob, so a later slug remap can update them. The column is `sort_order`
 * rather than `order`, which is reserved in MySQL.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Service
 * @author      Nails Dev Team
 */

namespace Nails\Cms\WidgetEditor\Service;

use Nails\Common\Exception\NailsException;
use Nails\Factory;

class Favourite
{
    public const MAX_SLUGS = 500;

    // --------------------------------------------------------------------------

    public function table(): string
    {
        return NAILS_DB_PREFIX . 'cms_widget_favourite';
    }

    // --------------------------------------------------------------------------

    /**
     * @throws \InvalidArgumentException
     */
    public static function checkSlug(string $slug): void
    {
        if ($slug === '' || mb_strlen($slug) > 255) {
            throw new \InvalidArgumentException('A favourite slug must be between 1 and 255 characters');
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Drops non-strings, rejects an empty or oversized slug, and keeps the first of any duplicate
     *
     * @param mixed $slugs
     *
     * @return string[]
     * @throws \InvalidArgumentException
     */
    public static function normaliseList(mixed $slugs): array
    {
        if (!is_array($slugs) || !array_is_list($slugs)) {
            throw new \InvalidArgumentException('Favourites must be a list of slugs');
        }

        if (count($slugs) > self::MAX_SLUGS) {
            throw new \InvalidArgumentException(sprintf('At most %d favourites', self::MAX_SLUGS));
        }

        $out = [];
        foreach ($slugs as $slug) {
            if (!is_string($slug)) {
                throw new \InvalidArgumentException('Each favourite must be a string');
            }

            self::checkSlug($slug);

            if (!in_array($slug, $out, true)) {
                $out[] = $slug;
            }
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * The user's favourites, first to last
     *
     * @return string[]
     */
    public function list(int $userId): array
    {
        $db = $this->db();
        $db->select('widget_slug');
        $db->where('user_id', $userId);
        $db->order_by('sort_order', 'ASC');
        $db->order_by('id', 'ASC');

        $slugs = [];
        foreach ($db->get($this->table())->result() as $row) {
            $slugs[] = (string) $row->widget_slug;
        }

        return $slugs;
    }

    // --------------------------------------------------------------------------

    /**
     * Adds a favourite at the end. Adding one that is already there changes nothing.
     *
     * @throws \InvalidArgumentException
     * @throws NailsException
     */
    public function add(int $userId, string $slug): void
    {
        self::checkSlug($slug);

        $db = $this->db();
        $db->select('id');
        $db->where('user_id', $userId);
        $db->where('widget_slug', $slug);

        if ($db->get($this->table())->row()) {
            return;
        }

        $db->where('user_id', $userId);
        $next = (int) $db->count_all_results($this->table());

        $this->insert($userId, $slug, $next);
    }

    // --------------------------------------------------------------------------

    /**
     * Removes a favourite. Removing one that is not there changes nothing.
     *
     * @throws \InvalidArgumentException
     */
    public function remove(int $userId, string $slug): void
    {
        self::checkSlug($slug);

        $db = $this->db();
        $db->where('user_id', $userId);
        $db->where('widget_slug', $slug);
        $db->delete($this->table());
    }

    // --------------------------------------------------------------------------

    /**
     * Replaces the user's favourites with this list, in this order
     *
     * @param string[] $slugs
     *
     * @throws \InvalidArgumentException
     * @throws NailsException
     */
    public function replace(int $userId, array $slugs): void
    {
        $slugs = self::normaliseList($slugs);
        $db    = $this->db();
        $tx    = $db->transaction()->start();

        try {
            $db->where('user_id', $userId);
            $db->delete($this->table());

            foreach ($slugs as $index => $slug) {
                $this->insert($userId, $slug, $index);
            }

            if (!$tx->status()) {
                throw new NailsException('Could not save favourites');
            }

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollback();
            throw $e;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Moves favourites onto the user who is kept when accounts are merged
     *
     * Runs before the merged users are deleted, so the foreign key does not cascade them away.
     * Where both sides have the same slug, the kept user's row wins.
     *
     * @param int[] $mergeIds
     */
    public function reassign(int $keepId, array $mergeIds): void
    {
        $mergeIds = array_values(array_filter(array_map('intval', $mergeIds), fn(int $id) => $id > 0 && $id !== $keepId));
        if ($mergeIds === []) {
            return;
        }

        $db = $this->db();
        $db->select('widget_slug');
        $db->where('user_id', $keepId);

        $already = [];
        foreach ($db->get($this->table())->result() as $row) {
            $already[(string) $row->widget_slug] = true;
        }

        //  Drop merged rows that would collide, before the update. The unique key is
        //  (user_id, widget_slug), so moving a duplicate onto the kept user would fail.
        $db->select('id, widget_slug');
        $db->where_in('user_id', $mergeIds);
        $db->order_by('id', 'ASC');

        $drop    = [];
        $arriving = [];
        foreach ($db->get($this->table())->result() as $row) {
            $slug = (string) $row->widget_slug;
            if (isset($already[$slug]) || isset($arriving[$slug])) {
                $drop[] = (int) $row->id;
            } else {
                $arriving[$slug] = true;
            }
        }

        if ($drop !== []) {
            $db->where_in('id', $drop);
            $db->delete($this->table());
        }

        $db->where_in('user_id', $mergeIds);
        $db->update($this->table(), ['user_id' => $keepId]);
    }

    // --------------------------------------------------------------------------

    /**
     * @throws NailsException
     */
    private function insert(int $userId, string $slug, int $order): void
    {
        $db = $this->db();
        $ok = $db->insert($this->table(), [
            'user_id'     => $userId,
            'widget_slug' => $slug,
            'sort_order'  => $order,
            'created'     => date('Y-m-d H:i:s'),
        ]);

        if (!$ok) {
            throw new NailsException('Could not save the favourite');
        }
    }

    // --------------------------------------------------------------------------

    private function db(): \Nails\Common\Service\Database
    {
        return Factory::service('Database');
    }
}
