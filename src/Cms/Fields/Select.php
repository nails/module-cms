<?php

/**
 * A drop-down. The first option is the default unless default() names another.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Field
 * @author      Nails Dev Team
 */

namespace Nails\Cms\Cms\Fields;

use Nails\Cms\WidgetEditor\Field\Base;

class Select extends Base
{
    /** @var array<string, string> Value => label */
    protected array $options = [];

    // --------------------------------------------------------------------------

    public static function getType(): string
    {
        return 'select';
    }

    // --------------------------------------------------------------------------

    /**
     * @param array<string|int, string> $options Value => label
     */
    public function options(array $options): static
    {
        $this->options = [];
        foreach ($options as $value => $label) {
            $this->options[(string) $value] = (string) $label;
        }
        return $this;
    }

    // --------------------------------------------------------------------------

    public function validate(): void
    {
        if ($this->options === []) {
            throw new \InvalidArgumentException(sprintf('Field "%s": a select needs options', $this->key));
        }

        $default = $this->getDefault();
        if ($default !== null && !array_key_exists((string) $default, $this->options)) {
            throw new \InvalidArgumentException(sprintf('Field "%s": default is not one of its options', $this->key));
        }
    }

    // --------------------------------------------------------------------------

    protected function defaultValue(): mixed
    {
        //  PHP stores numeric option keys as integers; a select reports its value as a string
        $first = array_key_first($this->options);
        return $first === null ? null : (string) $first;
    }

    // --------------------------------------------------------------------------

    protected function config(): array
    {
        return ['options' => $this->options];
    }
}
