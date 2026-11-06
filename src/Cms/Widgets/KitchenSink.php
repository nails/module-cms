<?php

/**
 * A WidgetEditor (v2) test widget which uses every field type and every field option shipped by Nails modules,
 * so each inspector control and each canvas edit mode can be checked in one place.
 *
 * It is offered everywhere except production, where it is hidden: existing instances still render and edit.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 */

namespace Nails\Cms\Cms\Widgets;

use Nails\Cdn\Cms\Fields\ObjectPicker;
use Nails\Cms\Cms\Fields;
use Nails\Cms\WidgetEditor\Status;
use Nails\Cms\WidgetEditor\Widget\Base;
use Nails\Environment;

class KitchenSink extends Base
{
    protected string $label       = 'Kitchen sink';
    protected string $summary     = 'Every field type, for testing the widget editor.';
    protected string $description = 'Uses every field type and option. Hidden in production.';
    protected string $icon        = 'fa-flask';
    protected array $groups       = ['Developer'];
    protected array $keywords     = ['test', 'fields', 'kitchen sink', 'developer'];

    // --------------------------------------------------------------------------

    public static function getStatus(): Status
    {
        return Environment::is(Environment::ENV_PROD) ? Status::HIDDEN : Status::ACTIVE;
    }

    // --------------------------------------------------------------------------

    public function getFields(): ?array
    {
        $tones = ['light' => 'Light', 'dark' => 'Dark', 'brand' => 'Brand'];

        return [
            //  Edited on the canvas
            Fields\Text::make('title')->label('Title')->required()->default('Kitchen sink'),
            Fields\Richtext::make('body')->label('Body'),
            Fields\Richtext::make('quote')->label('Quote')->basic(),

            //  Edited in the inspector
            Fields\Text::make('subtitle')->label('Subtitle')->maxLength(80)->default('A subtitle'),
            Fields\Textarea::make('summary')->label('Summary')->maxLength(300),
            Fields\Textarea::make('notes')->label('Notes')->wide()->tip('A wide field opens in a modal')->info('Shown under the control. Pass a class such as alert alert-info for a box.', 'alert alert-info'),
            Fields\Richtext::make('aside')->label('Aside')->tip('Rich text the canvas does not show'),
            Fields\Richtext::make('caption')->label('Caption')->basic(),
            Fields\Select::make('tone')->label('Tone')->subtitle('Light, dark, or the brand colour.')->options($tones),
            Fields\Select::make('size')->label('Size')->options(['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'])->default('medium'),
            Fields\Toggle::make('showLink')->label('Show link'),
            Fields\Text::make('linkText')->label('Link text')->required()->visibleWhen(['showLink' => true]),
            Fields\Text::make('linkUrl')->label('Link URL')->visibleWhen(['showLink' => true]),
            Fields\Number::make('count')->label('Count')->min(0)->max(10)->tip('0 to 10, not enforced'),
            Fields\Number::make('ratio')->label('Ratio'),
            ObjectPicker::make('image')->label('Image')->required(),
            Fields\Table::make('grid')->label('Grid')->size(2, 2)->wide(),
            Fields\Repeater::make('items')
                ->label('Items')
                ->max(4)
                ->wide()
                ->fields([
                    Fields\Text::make('label')->label('Label')->required(),
                    Fields\Select::make('tone')->label('Tone')->options($tones),
                    Fields\Text::make('detail')->label('Detail')->visibleWhen(['tone' => 'dark']),
                    Fields\Textarea::make('text')->label('Text'),
                    Fields\Richtext::make('body')->label('Body')->basic(),
                    Fields\Toggle::make('featured')->label('Featured'),
                    Fields\Number::make('weight')->label('Weight')->min(1),
                    ObjectPicker::make('image')->label('Image'),
                ]),
        ];
    }

    // --------------------------------------------------------------------------

    public function render(array $data): string
    {
        $rows = '';
        foreach (['subtitle', 'summary', 'notes', 'tone', 'size', 'showLink', 'linkText', 'linkUrl', 'count', 'ratio', 'image'] as $key) {
            $rows .= $this->row($key, $data[$key] ?? null);
        }
        foreach (['aside', 'caption'] as $key) {
            $rows .= '<dt>' . $key . '</dt><dd>' . (string) ($data[$key] ?? '') . '</dd>';
        }

        $grid = '';
        foreach ((array) ($data['grid'] ?? []) as $cells) {
            $grid .= '<tr>';
            foreach ((array) $cells as $cell) {
                $grid .= '<td>' . htmlspecialchars((string) $cell, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $grid .= '</tr>';
        }
        $rows .= '<dt>grid</dt><dd><table>' . $grid . '</table></dd>';

        $items = '';
        foreach ((array) ($data['items'] ?? []) as $item) {
            $item  = (array) $item;
            $items .= '<li><dl>';
            foreach (['label', 'tone', 'detail', 'text', 'featured', 'weight', 'image'] as $key) {
                $items .= $this->row($key, $item[$key] ?? null);
            }
            $items .= '<dt>body</dt><dd>' . (string) ($item['body'] ?? '') . '</dd></dl></li>';
        }

        return '<div class="cms-widget cms-widget-kitchen-sink">'
            . $this->editable('title', 'h2', (string) ($data['title'] ?? ''))
            . $this->richEditable('body', 'div', (string) ($data['body'] ?? ''))
            . $this->richEditable('quote', 'blockquote', (string) ($data['quote'] ?? ''))
            . '<dl>' . $rows . '</dl>'
            . '<ol>' . $items . '</ol>'
            . '</div>';
    }

    // --------------------------------------------------------------------------

    /**
     * A key and its value, escaped. A CDN object id also shows a 50×50 thumbnail, a size this module permits.
     */
    private function row(string $key, mixed $value): string
    {
        $text = match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => 'null',
            default         => (string) $value,
        };

        $html = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        if ($key === 'image' && is_int($value) && function_exists('cdnCrop')) {
            $html .= ' <img src="' . htmlspecialchars((string) cdnCrop($value, 50, 50), ENT_QUOTES, 'UTF-8') . '" width="50" height="50" alt="">';
        }

        return '<dt>' . $key . '</dt><dd>' . $html . '</dd>';
    }

    // --------------------------------------------------------------------------

    /**
     * Empty rich text shows a hint on the canvas, so the field can be clicked
     */
    private function richEditable(string $key, string $tag, string $html): string
    {
        if ($html === '' && $this->isPreview()) {
            return $this->editable($key, $tag, '<p class="cms-we-hint">Write something…</p>', ['data-cms-empty' => '1']);
        }

        return $this->editable($key, $tag, $html);
    }
}
