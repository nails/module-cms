<?php

/**
 * A grid of plain-text cells, stored as a list of rows, each a list of cell strings. Rows and columns are added
 * and removed in the control. size() sets the empty grid a new instance starts with.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Field
 * @author      Nails Dev Team
 */

namespace Nails\Cms\Cms\Fields;

use Nails\Cms\WidgetEditor\Field\Base;

class Table extends Base
{
    protected int $rows = 3;
    protected int $columns = 3;

    // --------------------------------------------------------------------------

    public static function getType(): string
    {
        return 'table';
    }

    // --------------------------------------------------------------------------

    public function size(int $rows, int $columns): static
    {
        $this->rows    = $rows;
        $this->columns = $columns;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Every row has as many cells as the widest row, and every cell is a string
     */
    public function fill(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $rows  = array_values(array_map(
            fn($row) => array_values(array_map(
                fn($cell) => is_scalar($cell) ? (string) $cell : '',
                is_array($row) ? $row : []
            )),
            $value
        ));
        $width = $rows === [] ? 0 : max(array_map('count', $rows));

        return array_map(fn(array $row) => array_pad($row, $width, ''), $rows);
    }

    // --------------------------------------------------------------------------

    public function validate(): void
    {
        if ($this->rows < 1 || $this->columns < 1) {
            throw new \InvalidArgumentException(sprintf('Field "%s": a table needs at least one row and column', $this->key));
        }
    }

    // --------------------------------------------------------------------------

    protected function defaultValue(): mixed
    {
        return array_fill(0, $this->rows, array_fill(0, $this->columns, ''));
    }
}
