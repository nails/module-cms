<?php

/**
 * A number. min() and max() are a hint: a number outside them is kept, and nothing is clamped.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Field
 * @author      Nails Dev Team
 */

namespace Nails\Cms\Cms\Fields;

use Nails\Cms\WidgetEditor\Field\Base;

class Number extends Base
{
    protected ?float $min = null;
    protected ?float $max = null;

    // --------------------------------------------------------------------------

    public static function getType(): string
    {
        return 'number';
    }

    // --------------------------------------------------------------------------

    public function min(?float $min): static
    {
        $this->min = $min;
        return $this;
    }

    public function max(?float $max): static
    {
        $this->max = $max;
        return $this;
    }

    // --------------------------------------------------------------------------

    protected function config(): array
    {
        return ['min' => $this->min, 'max' => $this->max];
    }
}
