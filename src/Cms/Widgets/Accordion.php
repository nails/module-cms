<?php

/**
 * The "Accordion" widget for WidgetEditor (v2). Ports the v1 widget: the stored slug `accordion` resolves through
 * ALIASES. The panels are a repeater stored under `panels`, the key the v1 view read for developer-defined panels.
 * v1 stored parallel `title` and `body` lists, which upgrade() converts.
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

class Accordion extends Base
{
    const ALIASES = ['accordion'];

    protected string $label       = 'Accordion';
    protected string $summary     = 'Collapsible panels.';
    protected string $description = 'Collapsible panels. Each one can start open or closed.';
    protected string $icon        = 'fa-list-alt';
    protected array  $keywords    = ['accordion', 'collapse', 'panels', 'faq'];

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function getFields(): ?array
    {
        return [
            Fields\Repeater::make('panels')
                ->label('Panels')
                ->fields([
                    Fields\Text::make('title')
                        ->label('Title')
                        ->required(),

                    Fields\Toggle::make('open')
                        ->label('Starts open')
                        ->default(true),

                    Fields\Richtext::make('body')
                        ->label('Body'),
                ]),
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function upgrade(array $data): array
    {
        $data = Tabs::upgradeParallelLists($data, 'panels');
        if (!is_array($data['panels'] ?? null)) {
            return $data;
        }

        foreach ($data['panels'] as $index => $panel) {
            if (!is_array($panel) || array_key_exists('open', $panel)) {
                continue;
            }

            // A v1 panel stored `collapsed`. Everything else starts as the v1 view did: the first panel open.
            $panel['open']          = array_key_exists('collapsed', $panel)
                ? !$panel['collapsed']
                : $index === 0;
            $data['panels'][$index] = $panel;
        }

        return $data;
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function render(array $data): string
    {
        $panels = array_values(array_filter((array) ($data['panels'] ?? []), 'is_array'));
        if ($panels === []) {
            return $this->isPreview()
                ? '<div class="cms-widget cms-widget-accordion"><p class="cms-we-hint">Add a panel in the inspector.</p></div>'
                : '';
        }

        $id   = 'cms-accordion-' . bin2hex(random_bytes(4));
        $html = '';

        foreach ($panels as $index => $panel) {
            $html .= $this->panelHtml($id, $index, $panel);
        }

        return <<<EOF
            <div class="cms-widget cms-widget-accordion">
                <div class="panel-group" id="$id" role="tablist">
                    $html
                </div>
            </div>
            EOF;
    }

    // --------------------------------------------------------------------------

    /**
     * @param array<string, mixed> $panel
     */
    private function panelHtml(string $id, int $index, array $panel): string
    {
        $open    = array_key_exists('open', $panel) ? (bool) $panel['open'] : $index === 0;
        $panelId = $id . '-' . $index;
        $title   = (string) ($panel['title'] ?? '');
        $body    = (string) ($panel['body'] ?? '');

        if ($this->isPreview()) {
            $heading  = $this->previewHeading($index, $title, $open, $panelId);
            $bodyHtml = $this->previewBody($index, $body);
        } else {
            $expanded = $open ? 'true' : 'false';
            $title    = htmlspecialchars($title, ENT_QUOTES);
            $heading  = <<<EOF
                <div class="panel-heading" role="tab" id="{$panelId}-heading">
                    <h4 class="panel-title">
                        <a role="button" data-toggle="collapse" href="#{$panelId}-body" aria-expanded="$expanded" aria-controls="{$panelId}-body">$title</a>
                    </h4>
                </div>
                EOF;
            $bodyHtml = '<div class="panel-body">' . $body . '</div>';
        }

        $in = $open ? ' in' : '';

        return <<<EOF
            <div class="panel panel-default">
                $heading
                <div id="{$panelId}-body" class="panel-collapse collapse{$in}" role="tabpanel" aria-labelledby="{$panelId}-heading">
                    $bodyHtml
                </div>
            </div>
            EOF;
    }

    // --------------------------------------------------------------------------

    /**
     * The canvas heading. The title is edited in place; the rest of the bar opens and closes the panel.
     */
    private function previewHeading(int $index, string $title, bool $open, string $panelId): string
    {
        $empty = $title === '';
        $attrs = [
            'class'                => 'cms-accordion-title',
            'data-cms-placeholder' => 'Add a title',
        ];
        if ($empty) {
            $attrs['data-cms-empty'] = '1';
        }

        $label = $this->editable(
            'panels/' . $index . '/title',
            'span',
            $empty ? 'Add a title' : $title,
            $attrs
        );

        $path     = htmlspecialchars(implode('/', $this->nodePath()), ENT_QUOTES);
        $expanded = $open ? 'true' : 'false';

        return <<<EOF
            <div class="panel-heading" role="tab" id="{$panelId}-heading" data-cms-action="toggle" data-cms-field="panels/{$index}/open" data-cms-path="$path" aria-expanded="$expanded" aria-controls="{$panelId}-body">
                <h4 class="panel-title">$label<span class="cms-accordion-mark" aria-hidden="true"></span></h4>
            </div>
            EOF;
    }

    // --------------------------------------------------------------------------

    private function previewBody(int $index, string $body): string
    {
        $empty = $body === '';
        $attrs = ['class' => 'panel-body'];
        if ($empty) {
            $attrs['data-cms-empty'] = '1';
        }

        return $this->editable(
            'panels/' . $index . '/body',
            'div',
            $empty ? '<p class="cms-we-hint">Write something…</p>' : $body,
            $attrs
        );
    }
}
