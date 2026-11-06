<?php

/**
 * A list of rows, each with its own fields. A row cannot hold another repeater. min() and max() limit Add item and
 * Remove; stored rows outside them are kept. A repeater always defaults to no rows.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Field
 * @author      Nails Dev Team
 */

namespace Nails\Cms\Cms\Fields;

use Nails\Cms\WidgetEditor\Field\Base;
use Nails\Cms\WidgetEditor\Interfaces\Field;
use Nails\Cms\WidgetEditor\Service\Fields;

class Repeater extends Base
{
    /** @var Field[] */
    protected array $fields = [];
    protected int $min = 0;
    protected ?int $max = null;

    // --------------------------------------------------------------------------

    public static function getType(): string
    {
        return 'repeater';
    }

    // --------------------------------------------------------------------------

    /**
     * @param Field[] $fields
     */
    public function fields(array $fields): static
    {
        $this->fields = $fields;
        return $this;
    }

    public function min(int $min): static
    {
        $this->min = $min;
        return $this;
    }

    public function max(?int $max): static
    {
        $this->max = $max;
        return $this;
    }

    // --------------------------------------------------------------------------

    public function getDefault(): mixed
    {
        return [];
    }

    // --------------------------------------------------------------------------

    public function fill(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        return array_values(array_map(
            fn($row) => Fields::fill($this->fields, is_array($row) ? $row : []),
            $value
        ));
    }

    // --------------------------------------------------------------------------

    public function validate(): void
    {
        if ($this->fields === []) {
            throw new \InvalidArgumentException(sprintf('Field "%s": a repeater needs fields', $this->key));
        }

        foreach ($this->fields as $field) {
            if ($field instanceof self) {
                throw new \InvalidArgumentException(sprintf(
                    'Field "%s": a repeater cannot contain a repeater',
                    $this->key
                ));
            }
        }

        Fields::validate($this->fields);
    }

    // --------------------------------------------------------------------------

    protected function config(): array
    {
        return [
            'fields' => array_map(fn(Field $field) => $field->toArray(), $this->fields),
            'min'    => $this->min,
            'max'    => $this->max,
        ];
    }
}
