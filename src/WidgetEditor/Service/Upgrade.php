<?php

/**
 * Upgrades the data of widgets in stored widget JSON, so it is saved in the current shape
 *
 * A widget node is an array or object with a string `slug` and a `data` key, at any depth, so widgets in slots are
 * covered. Only the `data` of a matching node changes, and only when its upgrade changes it. Objects stay objects,
 * so an empty `{}` elsewhere in the tree is not written back as `[]`.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Service
 * @author      Nails Dev Team
 */

namespace Nails\Cms\WidgetEditor\Service;

class Upgrade
{
    /**
     * Runs the upgrade of every node whose slug is a key of $upgraders
     *
     * @param mixed                                $value     A decoded widget tree, with objects or arrays
     * @param array<string, callable(array): array> $upgraders Slug => upgrade, for example a widget's upgrade()
     *
     * @return array{value: mixed, nodes: int} The upgraded tree, and how many nodes changed
     */
    public static function rewrite(mixed $value, array $upgraders): array
    {
        $nodes = 0;
        $value = self::walk($value, $upgraders, $nodes);
        return ['value' => $value, 'nodes' => $nodes];
    }

    // --------------------------------------------------------------------------

    /**
     * @param array<string, callable(array): array> $upgraders
     */
    private static function walk(mixed $value, array $upgraders, int &$nodes): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $child) {
                $value[$key] = self::walk($child, $upgraders, $nodes);
            }
            $slug    = $value['slug'] ?? null;
            $hasData = array_key_exists('data', $value);
            $data    = $value['data'] ?? null;
        } elseif ($value instanceof \stdClass) {
            foreach (get_object_vars($value) as $key => $child) {
                $value->{$key} = self::walk($child, $upgraders, $nodes);
            }
            $slug    = $value->slug ?? null;
            $hasData = property_exists($value, 'data');
            $data    = $value->data ?? null;
        } else {
            return $value;
        }

        if (!is_string($slug) || !$hasData || !array_key_exists($slug, $upgraders)) {
            return $value;
        }

        $before = self::toArray($data);
        if ($before === null) {
            return $value;
        }

        $after = ($upgraders[$slug])($before);
        if ($after === $before) {
            return $value;
        }

        if (is_array($value)) {
            $value['data'] = $after;
        } else {
            $value->data = $after;
        }
        $nodes++;

        return $value;
    }

    // --------------------------------------------------------------------------

    /**
     * The node's data as the array an upgrade() takes, or null when it is not an array or object
     */
    private static function toArray(mixed $data): ?array
    {
        if (!is_array($data) && !$data instanceof \stdClass) {
            return null;
        }

        $data = json_decode(json_encode($data, JSON_PRESERVE_ZERO_FRACTION), true);

        return is_array($data) ? $data : null;
    }
}
