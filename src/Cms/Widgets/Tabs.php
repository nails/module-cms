<?php

/**
 * The "Tabs" widget for WidgetEditor (v2). Ports the v1 widget: the stored slug `tabs` resolves through ALIASES.
 * The tabs are a repeater stored under `tabs`, the key the v1 view read for developer-defined tabs. v1 stored
 * parallel `title` and `body` lists, which upgrade() converts.
 *
 * The public markup is the v1 markup, and the first tab starts selected. The canvas edits each title and body in
 * place, and a click on a tab shows that pane. Which tab is showing is not stored.
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

class Tabs extends Base
{
    const ALIASES = ['tabs'];

    protected string $label       = 'Tabs';
    protected string $summary     = 'Show tabbed content.';
    protected string $description = 'Show tabbed content. The first tab starts selected.';
    protected string $icon        = 'fa-folder';
    protected array $keywords     = ['tabs', 'tabbed', 'panels'];

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function getFields(): ?array
    {
        return [
            Fields\Repeater::make('tabs')
                ->label('Tabs')
                ->fields([
                    Fields\Text::make('title')->label('Title')->required(),
                    Fields\Richtext::make('body')->label('Body'),
                ]),
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function upgrade(array $data): array
    {
        return static::upgradeParallelLists($data, 'tabs');
    }

    // --------------------------------------------------------------------------

    /**
     * Converts the v1 shape, parallel `title` and `body` lists, into rows under $key. Data that already has $key,
     * or has no `title` list, is returned unchanged.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public static function upgradeParallelLists(array $data, string $key): array
    {
        if (array_key_exists($key, $data) || !is_array($data['title'] ?? null)) {
            return $data;
        }

        $titles = array_values($data['title']);
        $bodies = array_values(is_array($data['body'] ?? null) ? $data['body'] : []);
        $rows   = [];

        foreach ($titles as $index => $title) {
            $rows[] = [
                'title' => is_scalar($title) ? (string) $title : '',
                'body'  => is_scalar($bodies[$index] ?? null) ? (string) $bodies[$index] : '',
            ];
        }

        unset($data['title'], $data['body']);
        $data[$key] = $rows;

        return $data;
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function render(array $data): string
    {
        $tabs = array_values(array_filter((array) ($data['tabs'] ?? []), 'is_array'));
        if ($tabs === []) {
            return $this->isPreview()
                ? '<div class="cms-widget nails-cms-widget-tabs"><p class="cms-we-hint">Add a tab in the inspector.</p></div>'
                : '';
        }

        $id    = 'cms-tabs-' . bin2hex(random_bytes(4));
        $nav   = '';
        $panes = '';

        foreach ($tabs as $index => $tab) {
            $active = $index === 0;
            $paneId = $id . '-' . $index;
            $title  = (string) ($tab['title'] ?? '');
            $body   = (string) ($tab['body'] ?? '');

            if ($this->isPreview()) {
                $nav   .= $this->previewTab($index, $paneId, $title, $active);
                $panes .= $this->previewPane($index, $paneId, $body, $active);
                continue;
            }

            $class = $active ? ' active' : '';
            $nav  .= '<li class="tab-item' . $class . '" role="presentation">'
                . '<a href="#' . $paneId . '" role="tab" data-toggle="tab">'
                . htmlspecialchars($title, ENT_QUOTES)
                . '</a>'
                . '</li>';
            $panes .= '<div role="tabpanel" class="tab-pane' . $class . '" id="' . $paneId . '">' . $body . '</div>';
        }

        return '<div class="cms-widget nails-cms-widget-tabs">'
            . '<ul class="nav nav-tabs" role="tablist">' . $nav . '</ul>'
            . '<div class="tab-content">' . $panes . '</div>'
            . '</div>';
    }

    // --------------------------------------------------------------------------

    /**
     * The canvas tab. The title is edited in place; the rest of the tab shows this pane.
     */
    private function previewTab(int $index, string $paneId, string $title, bool $active): string
    {
        $empty = $title === '';
        $attrs = [
            'class'                => 'cms-tab-title',
            'data-cms-placeholder' => 'Add a title',
        ];
        if ($empty) {
            $attrs['data-cms-empty'] = '1';
        }

        $label = $this->editable(
            'tabs/' . $index . '/title',
            'span',
            $empty ? 'Add a title' : $title,
            $attrs
        );

        return '<li class="tab-item' . ($active ? ' active' : '') . '" role="presentation">'
            . '<a href="#' . $paneId . '" role="tab" data-cms-action="tab" data-cms-tab="' . $index . '"'
            . ' aria-selected="' . ($active ? 'true' : 'false') . '">'
            . $label
            . '</a>'
            . '</li>';
    }

    // --------------------------------------------------------------------------

    private function previewPane(int $index, string $paneId, string $body, bool $active): string
    {
        $empty = $body === '';
        $attrs = [
            'role'  => 'tabpanel',
            'class' => 'tab-pane' . ($active ? ' active' : ''),
            'id'    => $paneId,
        ];
        if ($empty) {
            $attrs['data-cms-empty'] = '1';
        }

        return $this->editable(
            'tabs/' . $index . '/body',
            'div',
            $empty ? '<p class="cms-we-hint">Write something…</p>' : $body,
            $attrs
        );
    }
}
