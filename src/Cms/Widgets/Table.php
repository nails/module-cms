<?php

/**
 * The "Table" widget for WidgetEditor (v2). Ports the v1 widget: the stored slug `table` resolves through ALIASES.
 * The cells are a `table` field stored under `rows`, the number of heading rows under `header_rows`, and the extra
 * table attributes under `attributes`. v1 stored the cells as a JSON string under `tblData` and the attributes under
 * `tblAttr`, which upgradeV1() converts. Migration13 runs the same conversion on stored pages and areas.
 *
 * Cells and attributes are output as stored, as in v1.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Cms\Widgets;

use Nails\Cms\Cms\Fields;
use Nails\Cms\WidgetEditor\Widget\Base;

class Table extends Base
{
    const ALIASES = ['table'];

    /** Where a v1 value that could not be converted exactly is kept, as it was stored */
    const V1_DATA_KEY = 'v1_tblData';
    const V1_ATTR_KEY = 'v1_tblAttr';

    protected string $label       = 'Table';
    protected string $summary     = 'Easily build a table';
    protected string $description = 'Easily build a table';
    protected string $icon        = 'fa-table';
    protected array $keywords     = ['table', 'tabular data', 'data'];

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function getFields(): ?array
    {
        return [
            Fields\Table::make('rows')->label('Cells')->size(4, 3)->headerRows('header_rows')->wide(),
            Fields\Number::make('header_rows')
                ->label('Header rows')
                ->tip('How many of the first rows are headings')
                ->min(0)
                ->default(1),
            Fields\Text::make('attributes')
                ->label('Attributes')
                ->tip('Added to the <table> tag as written, for example id="prices"'),
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function upgrade(array $data): array
    {
        return static::upgradeV1($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Converts the v1 keys, and returns data that has none unchanged.
     *
     * The v1 editor replaced an instance's data with its form, so v1 keys are always the latest edit and win over
     * `rows` and `attributes`. They are removed once converted. A grid that cannot be converted exactly (JSON that
     * cannot be read, or a cell that is not text or a number) is converted as far as it can be, and the stored value
     * is kept under V1_DATA_KEY; attributes that are not text are kept under V1_ATTR_KEY. v1 had no heading rows,
     * so a converted table gets `header_rows` 0.
     *
     * @param array $data The stored data
     *
     * @return array
     */
    public static function upgradeV1(array $data): array
    {
        $isV1 = false;

        if (array_key_exists('tblData', $data)) {
            $isV1 = true;
            [$rows, $exact] = static::v1Rows($data['tblData']);

            $data['rows'] = $rows;
            if (!$exact) {
                $data[static::V1_DATA_KEY] = $data['tblData'];
            }
            unset($data['tblData']);
        }

        if (array_key_exists('tblAttr', $data)) {
            $isV1       = true;
            $attributes = $data['tblAttr'];

            if ($attributes === null || is_scalar($attributes)) {
                //  v1 printed the attributes only when they were not empty()
                $data['attributes'] = empty($attributes) ? '' : (string) $attributes;
            } else {
                $data['attributes']        = '';
                $data[static::V1_ATTR_KEY] = $attributes;
            }
            unset($data['tblAttr']);
        }

        if ($isV1 && !array_key_exists('header_rows', $data)) {
            $data['header_rows'] = 0;
        }

        return $data;
    }

    // --------------------------------------------------------------------------

    /**
     * Reads a v1 grid, as Handsontable's getData() stored it: a JSON list of rows, each a list of cells
     *
     * A cell is output as v1 output it: null and false are empty, true is "1", a number is its digits. A row that
     * is not a list is empty, as v1 output an empty row for it.
     *
     * @param mixed $stored The stored `tblData`
     *
     * @return array{0: array<int, array<int, string>>, 1: bool} The rows, and whether nothing was lost
     */
    protected static function v1Rows(mixed $stored): array
    {
        if ($stored === null || (is_string($stored) && trim($stored) === '')) {
            return [[], true];
        }

        if (is_string($stored)) {
            //  Big integers keep their digits rather than becoming floats
            $grid = json_decode($stored, true, 512, JSON_BIGINT_AS_STRING);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [[], false];
            }
        } else {
            $grid = $stored;
        }

        if ($grid === null) {
            return [[], true];
        }

        if (!is_array($grid)) {
            return [[], false];
        }

        $exact = true;
        $rows  = [];

        foreach ($grid as $row) {
            if (!is_array($row)) {
                $exact  = false;
                $rows[] = [];
                continue;
            }

            $cells = [];
            foreach ($row as $cell) {
                if ($cell === null || $cell === false) {
                    $cells[] = '';
                } elseif (is_scalar($cell)) {
                    $cells[] = (string) $cell;
                } else {
                    $exact   = false;
                    $cells[] = '';
                }
            }
            $rows[] = $cells;
        }

        return [$rows, $exact];
    }

    // --------------------------------------------------------------------------

    /**
     * A table whose cells are all blank renders nothing. The canvas still draws it, so it can be seen and filled.
     *
     * @inheritDoc
     */
    public function render(array $data): string
    {
        $rows = array_values(array_filter((array) ($data['rows'] ?? []), 'is_array'));

        if ($rows === []) {
            return $this->isPreview()
                ? '<div class="cms-widget cms-widget-table"><p class="cms-we-hint">Add some cells in the inspector.</p></div>'
                : '';
        }

        if (!$this->isPreview() && static::isBlank($rows)) {
            return '';
        }

        $attributes = trim((string) ($data['attributes'] ?? ''));
        $headers    = max(0, min(count($rows), (int) ($data['header_rows'] ?? 0)));
        $head       = '';
        $body       = '';

        foreach (array_slice($rows, 0, $headers) as $rowIndex => $cells) {
            $head .= '<tr data-row="' . $rowIndex . '" class="cms-widget-table-row cms-widget-table-header-row">';
            foreach (array_values($cells) as $columnIndex => $cell) {
                $head .= '<th scope="col" data-row="' . $columnIndex . '" class="cms-widget-table-cell">' . static::cell($cell) . '</th>';
            }
            $head .= '</tr>';
        }

        //  The row classes, and data-row holding the column index on a cell, are the v1 markup
        foreach (array_slice($rows, $headers) as $rowIndex => $cells) {
            $body .= '<tr data-row="' . $rowIndex . '" class="cms-widget-table-row cms-widgettable-row-' . ($rowIndex % 2 ? 'odd' : 'even') . '">';
            foreach (array_values($cells) as $columnIndex => $cell) {
                $body .= '<td data-row="' . $columnIndex . '" class="cms-widget-table-cell">' . static::cell($cell) . '</td>';
            }
            $body .= '</tr>';
        }

        return '<div class="cms-widget cms-widget-table">'
            . '<table class="table"' . ($attributes !== '' ? ' ' . $attributes : '') . '>'
            . ($head !== '' ? '<thead>' . $head . '</thead>' : '')
            . ($body !== '' ? '<tbody>' . $body . '</tbody>' : '')
            . '</table>'
            . '</div>';
    }

    // --------------------------------------------------------------------------

    private static function cell(mixed $cell): string
    {
        return is_scalar($cell) ? (string) $cell : '';
    }

    // --------------------------------------------------------------------------

    private static function isBlank(array $rows): bool
    {
        foreach ($rows as $cells) {
            foreach ($cells as $cell) {
                if (trim(static::cell($cell)) !== '') {
                    return false;
                }
            }
        }

        return true;
    }
}
