<?php

/**
 * Monitor helper
 *
 * @package     Nails
 * @subpackage  nails/module-cms
 * @category    Helper
 * @author      Nails Dev Team
 */

namespace Nails\Cms\Helper;

use Nails\Cms\Factory\Monitor\Item;

class Monitor
{
    /**
     * The character used to escape LIKE wildcards in likePattern()
     */
    const LIKE_ESCAPE = '!';

    // --------------------------------------------------------------------------

    /**
     * Returns a widget slug as a JSON string, as MySQL renders it in JSON_EXTRACT() output
     *
     * The result is not SQL-escaped; quote it with the Database service before use.
     */
    public static function jsonString(string $slug): string
    {
        return json_encode($slug, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a LIKE pattern which matches JSON containing the slug as a string, for use
     * with `ESCAPE '!'`
     *
     * The result is not SQL-escaped; quote it with the Database service before use.
     */
    public static function likePattern(string $slug): string
    {
        $escaped = str_replace(
            [static::LIKE_ESCAPE, '%', '_'],
            [static::LIKE_ESCAPE . static::LIKE_ESCAPE, static::LIKE_ESCAPE . '%', static::LIKE_ESCAPE . '_'],
            static::jsonString($slug)
        );

        return '%' . $escaped . '%';
    }

    // --------------------------------------------------------------------------

    /**
     * Query values for the widget CSV link
     *
     * Input::get() returns false when a key is absent. http_build_query() turns that false into 0,
     * which then searches the list for the character 0.
     *
     * @return array<string, string>
     */
    public static function widgetExportQuery(mixed $keywords, mixed $sortOn, mixed $sortOrder): array
    {
        $query = [];

        if (is_string($keywords) && trim($keywords) !== '') {
            $query['keywords'] = $keywords;
        }

        if (is_string($sortOn) && in_array($sortOn, ['label', 'usages'], true)) {
            $query['sortOn'] = $sortOn;
        }

        if (is_string($sortOrder) && in_array($sortOrder, ['asc', 'desc'], true)) {
            $query['sortOrder'] = $sortOrder;
        }

        return $query;
    }

    // --------------------------------------------------------------------------

    /**
     * Rows for the widget monitor CSV, including the header. In use is the opposite of deprecated.
     *
     * @param Item[] $items
     *
     * @return array<int, array<int, int|string>>
     */
    public static function widgetCsv(array $items): array
    {
        $rows = [['Label', 'Slug', 'Description', 'Usages', 'In use', 'Deprecated']];

        foreach ($items as $item) {
            $rows[] = [
                $item->label,
                $item->slug,
                $item->description,
                $item->usages,
                $item->usages > 0 ? 'Yes' : 'No',
                $item->is_deprecated ? 'Yes' : 'No',
            ];
        }

        return $rows;
    }
}
