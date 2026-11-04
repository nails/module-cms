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
}
