<?php

/**
 * The "Table" widget for WidgetEditor (v2). Ports the v1 widget: the stored slug `table` resolves through ALIASES.
 * The cells are a `table` field stored under `rows`, and the extra table attributes under `attributes`. v1 stored
 * the cells as a JSON string under `tblData` and the attributes under `tblAttr`, which upgrade() converts.
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
            Fields\Table::make('rows')->label('Cells')->size(4, 3)->wide(),
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
        if (!array_key_exists('rows', $data) && array_key_exists('tblData', $data)) {
            $rows         = is_string($data['tblData']) ? json_decode($data['tblData'], true) : $data['tblData'];
            $data['rows'] = is_array($rows) ? $rows : [];
        }

        if (!array_key_exists('attributes', $data) && array_key_exists('tblAttr', $data)) {
            $data['attributes'] = is_string($data['tblAttr']) ? $data['tblAttr'] : '';
        }

        unset($data['tblData'], $data['tblAttr']);

        return $data;
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function render(array $data): string
    {
        $rows = array_values(array_filter((array) ($data['rows'] ?? []), 'is_array'));
        if ($rows === []) {
            return '';
        }

        $attributes = trim((string) ($data['attributes'] ?? ''));
        $body       = '';

        foreach ($rows as $rowIndex => $cells) {
            $body .= '<tr data-row="' . $rowIndex . '" class="cms-widget-table-row cms-widgettable-row-' . ($rowIndex % 2 ? 'odd' : 'even') . '">';
            foreach (array_values($cells) as $columnIndex => $cell) {
                $body .= '<td data-row="' . $columnIndex . '" class="cms-widget-table-cell">' . (is_scalar($cell) ? (string) $cell : '') . '</td>';
            }
            $body .= '</tr>';
        }

        return '<div class="cms-widget cms-widget-table">'
            . '<table class="table"' . ($attributes !== '' ? ' ' . $attributes : '') . '><tbody>' . $body . '</tbody></table>'
            . '</div>';
    }
}
