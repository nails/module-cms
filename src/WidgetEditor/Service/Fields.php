<?php

/**
 * Validates a fields-schema widget's field list, describes it for the inspector, and fills defaults
 *
 * A field is an instance of a field type (see Interfaces\Field). A bad schema is a developer error, so it throws.
 * The catalogue builds on every admin request, which surfaces it straight away. Values are not validated here:
 * the editor writes what the user entered, and warns about what is missing without blocking the save.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Service
 * @author      Nails Dev Team
 */

namespace Nails\Cms\WidgetEditor\Service;

use Nails\Cms\WidgetEditor\Interfaces\Field;

class Fields
{
    /**
     * Checks a list of fields: each is a field, keys are unique, each field is valid, and visibleWhen names a
     * sibling in the same list
     *
     * @param array<int, mixed> $fields
     *
     * @return Field[]
     * @throws \InvalidArgumentException
     */
    public static function validate(array $fields): array
    {
        $keys = [];

        foreach ($fields as $field) {
            if (!$field instanceof Field) {
                throw new \InvalidArgumentException(sprintf(
                    'Each field must implement %s, %s given',
                    Field::class,
                    get_debug_type($field)
                ));
            }

            $key = $field->getKey();
            if (array_key_exists($key, $keys)) {
                throw new \InvalidArgumentException(sprintf('Field key "%s" is used more than once', $key));
            }
            $keys[$key] = true;

            $field->validate();
        }

        //  visibleWhen must refer to a field in the same list, so a typo cannot hide a field forever
        foreach ($fields as $field) {
            foreach (array_keys($field->toArray()['visibleWhen']) as $other) {
                if (!array_key_exists($other, $keys)) {
                    throw new \InvalidArgumentException(sprintf(
                        'Field "%s": visibleWhen refers to unknown field "%s"',
                        $field->getKey(),
                        $other
                    ));
                }
            }
        }

        return array_values($fields);
    }

    // --------------------------------------------------------------------------

    /**
     * The fields as the inspector reads them
     *
     * @param array<int, mixed> $fields
     *
     * @return array<int, array<string, mixed>>
     * @throws \InvalidArgumentException
     */
    public static function describe(array $fields): array
    {
        return array_map(
            static fn(Field $field) => $field->toArray(),
            static::validate($fields)
        );
    }

    // --------------------------------------------------------------------------

    /**
     * The default value of every field, keyed by field key
     *
     * @param Field[] $fields
     *
     * @return array<string, mixed>
     */
    public static function defaults(array $fields): array
    {
        $out = [];
        foreach ($fields as $field) {
            $out[$field->getKey()] = $field->getDefault();
        }
        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Fills the defaults of any key the data is missing, then lets each field fill its own value (a repeater fills
     * its rows). Pure.
     *
     * @param Field[]              $fields
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public static function fill(array $fields, array $data): array
    {
        $out = array_replace(static::defaults($fields), $data);

        foreach ($fields as $field) {
            $out[$field->getKey()] = $field->fill($out[$field->getKey()]);
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * The field stored under a key, or null
     *
     * @param Field[] $fields
     */
    public static function find(array $fields, string $key): ?Field
    {
        foreach ($fields as $field) {
            if ($field->getKey() === $key) {
                return $field;
            }
        }

        return null;
    }
}
