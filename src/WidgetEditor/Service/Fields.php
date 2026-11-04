<?php

/**
 * Validates and fills a fields-schema widget's field definitions (D23)
 *
 * A bad schema is a developer error, so it throws. The catalogue builds on every admin request, which surfaces it
 * straight away. Values are not validated here: the editor writes what the user entered, and warns about what is
 * missing without blocking the save.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Service
 * @author      Nails Dev Team
 */

namespace Nails\Cms\WidgetEditor\Service;

class Fields
{
    /** The field types in v2.0 (D23) */
    public const TYPES = [
        'text',
        'textarea',
        'richtext',
        'select',
        'toggle',
        'number',
        'cdn_object',
        'repeater',
    ];

    /** Keys are stored as JSON object keys and read by PHP, so they follow the same shape as a PHP variable */
    private const KEY_PATTERN = '/^[A-Za-z][A-Za-z0-9_]*$/';

    // --------------------------------------------------------------------------

    /**
     * Returns a list of fields, validated and with every optional key filled in
     *
     * @param array<int, mixed> $fields
     *
     * @return array<int, array<string, mixed>>
     * @throws \InvalidArgumentException
     */
    public static function normalise(array $fields): array
    {
        return self::normaliseList($fields, true);
    }

    // --------------------------------------------------------------------------

    /**
     * The default value of every field, keyed by field key. Repeaters default to no rows.
     *
     * @param array<int, array<string, mixed>> $fields Normalised fields
     *
     * @return array<string, mixed>
     */
    public static function defaults(array $fields): array
    {
        $out = [];
        foreach ($fields as $field) {
            $out[$field['key']] = $field['default'];
        }
        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Fills the defaults of any key the data is missing, including the keys of each repeater row. Pure.
     *
     * @param array<int, array<string, mixed>> $fields Normalised fields
     * @param array<string, mixed>             $data
     *
     * @return array<string, mixed>
     */
    public static function fill(array $fields, array $data): array
    {
        $out = array_replace(self::defaults($fields), $data);

        foreach ($fields as $field) {
            if ($field['type'] !== 'repeater' || !isset($out[$field['key']]) || !is_array($out[$field['key']])) {
                continue;
            }
            $out[$field['key']] = array_values(array_map(
                static fn ($row) => self::fill($field['fields'], is_array($row) ? $row : []),
                $out[$field['key']]
            ));
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * @param array<int, mixed> $fields
     * @param bool              $top    Whether this is the widget's own list; repeaters cannot nest
     *
     * @return array<int, array<string, mixed>>
     * @throws \InvalidArgumentException
     */
    private static function normaliseList(array $fields, bool $top): array
    {
        $keys = [];
        $out  = [];

        foreach ($fields as $field) {
            if (!is_array($field)) {
                throw new \InvalidArgumentException('Each field must be an array');
            }

            $key = (string) ($field['key'] ?? '');
            if (!preg_match(self::KEY_PATTERN, $key)) {
                throw new \InvalidArgumentException(sprintf('Field key "%s" must start with a letter and use only letters, digits and underscores', $key));
            }
            if (array_key_exists($key, $keys)) {
                throw new \InvalidArgumentException(sprintf('Field key "%s" is used more than once', $key));
            }
            $keys[$key] = true;

            $type = (string) ($field['type'] ?? '');
            if (!in_array($type, self::TYPES, true)) {
                throw new \InvalidArgumentException(sprintf('Field "%s" has unknown type "%s"', $key, $type));
            }
            if (!$top && $type === 'repeater') {
                throw new \InvalidArgumentException(sprintf('Field "%s": a repeater cannot contain a repeater', $key));
            }

            $out[] = self::normaliseField($key, $type, $field);
        }

        //  visibleWhen must refer to a field in the same list, so a typo cannot hide a field forever
        foreach ($out as $field) {
            foreach (array_keys($field['visibleWhen']) as $other) {
                if (!array_key_exists($other, $keys)) {
                    throw new \InvalidArgumentException(sprintf(
                        'Field "%s": visibleWhen refers to unknown field "%s"',
                        $field['key'],
                        $other
                    ));
                }
            }
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * @param array<string, mixed> $field
     *
     * @return array<string, mixed>
     * @throws \InvalidArgumentException
     */
    private static function normaliseField(string $key, string $type, array $field): array
    {
        $visibleWhen = $field['visibleWhen'] ?? [];
        if (!is_array($visibleWhen)) {
            throw new \InvalidArgumentException(sprintf('Field "%s": visibleWhen must be an array', $key));
        }

        $out = [
            'key'         => $key,
            'type'        => $type,
            'label'       => (string) ($field['label'] ?? $key),
            'tip'         => (string) ($field['tip'] ?? ''),
            'required'    => (bool) ($field['required'] ?? false),
            'wide'        => (bool) ($field['wide'] ?? false),
            'visibleWhen' => $visibleWhen,
            'default'     => null,
        ];

        switch ($type) {
            case 'select':
                $options = $field['options'] ?? null;
                if (!is_array($options) || $options === []) {
                    throw new \InvalidArgumentException(sprintf('Field "%s": a select needs options', $key));
                }
                $out['options'] = [];
                foreach ($options as $value => $label) {
                    $out['options'][(string) $value] = (string) $label;
                }
                $out['default'] = array_key_first($out['options']);
                break;

            case 'number':
                $out['min'] = isset($field['min']) ? (float) $field['min'] : null;
                $out['max'] = isset($field['max']) ? (float) $field['max'] : null;
                break;

            case 'repeater':
                $fields = $field['fields'] ?? null;
                if (!is_array($fields) || $fields === []) {
                    throw new \InvalidArgumentException(sprintf('Field "%s": a repeater needs fields', $key));
                }
                $out['fields'] = self::normaliseList($fields, false);
                $out['min']    = (int) ($field['min'] ?? 0);
                $out['max']    = isset($field['max']) ? (int) $field['max'] : null;
                $out['default'] = [];
                break;

            case 'toggle':
                $out['default'] = false;
                break;

            case 'text':
            case 'textarea':
            case 'richtext':
                $out['default'] = '';
                break;
        }

        if ($type === 'cdn_object') {
            $out['bucket'] = (string) ($field['bucket'] ?? '');
        }

        if (array_key_exists('default', $field) && $type !== 'repeater') {
            $out['default'] = $field['default'];
        }

        if ($type === 'select' && $out['default'] !== null && !array_key_exists((string) $out['default'], $out['options'])) {
            throw new \InvalidArgumentException(sprintf('Field "%s": default is not one of its options', $key));
        }

        return $out;
    }
}
