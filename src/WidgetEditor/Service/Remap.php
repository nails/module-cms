<?php

/**
 * Rewrites widget slugs in stored widget JSON (D14)
 *
 * A widget node is an array with a string `slug` and a `data` key, at any depth, so widgets in slots are covered.
 * Only the `slug` of a node changes. Nothing else is touched, and a value that is not a node is never rewritten.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Service
 * @author      Nails Dev Team
 */

namespace Nails\Cms\WidgetEditor\Service;

class Remap
{
    /**
     * Rewrites every node whose slug is a key of $map
     *
     * @param mixed                 $value A decoded widget tree
     * @param array<string, string> $map   Old slug => new slug
     *
     * @return array{value: mixed, nodes: int} The rewritten tree, and how many nodes changed
     */
    public static function rewrite(mixed $value, array $map): array
    {
        $iNodes = 0;
        $mValue = self::walk($value, $map, $iNodes);
        return ['value' => $mValue, 'nodes' => $iNodes];
    }

    // --------------------------------------------------------------------------

    /**
     * @param array<string, string> $map
     */
    private static function walk(mixed $mValue, array $map, int &$iNodes): mixed
    {
        if (!is_array($mValue)) {
            return $mValue;
        }

        $bIsNode = is_string($mValue['slug'] ?? null) && array_key_exists('data', $mValue);

        if ($bIsNode && is_string($map[$mValue['slug']] ?? null) && $map[$mValue['slug']] !== $mValue['slug']) {
            $mValue['slug'] = $map[$mValue['slug']];
            $iNodes++;
        }

        foreach ($mValue as $mKey => $mChild) {
            if ($bIsNode && $mKey === 'slug') {
                continue;
            }
            $mValue[$mKey] = self::walk($mChild, $map, $iNodes);
        }

        return $mValue;
    }
}
