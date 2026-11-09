<?php

/**
 * The "Blockquote" widget for WidgetEditor (v2). Ports the v1 widget: the stored slug `blockquote` resolves through
 * ALIASES, and the stored keys are unchanged.
 *
 * The v1 view inverted the citation link (the text was linked only when there was no URL). Here the text is
 * linked when a URL is given and is plain otherwise.
 *
 * With no quotation the public page renders nothing. The canvas still draws the quotation and the citation
 * as inline fields, and an empty field shows its placeholder.
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

class Blockquote extends Base
{
    const ALIASES = ['blockquote'];

    protected string $label       = 'Blockquote';
    protected string $summary     = 'A block quote with optional citation';
    protected string $description = 'A block quote with optional citation';
    protected string $icon        = 'fa-quote-left';
    protected array  $keywords    = ['text', 'plain text', 'quote', 'blockquote', 'callout'];
    protected array  $defaults    = ['quote' => '', 'cite_text' => '', 'cite_url' => ''];

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function getFields(): ?array
    {
        return [
            Fields\Richtext::make('quote')
                ->label('Quotation')
                ->basic(),

            Fields\Text::make('cite_text')
                ->label('Citation'),

            Fields\Text::make('cite_url')
                ->label('Citation URL'),
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function render(array $data): string
    {
        $quote = (string) ($data['quote'] ?? '');
        $text  = (string) ($data['cite_text'] ?? '');
        $url   = (string) ($data['cite_url'] ?? '');

        if ($this->isPreview()) {
            return $this->markup($this->quoteField($quote), $this->citationField($text, $url));
        }

        if ($quote === '') {
            return '';
        }

        return $this->markup($quote, $this->footer($text, $url));
    }

    // --------------------------------------------------------------------------

    private function markup(string $quote, string $footer): string
    {
        return '<div class="cms-widget cms-widget-blockquote"><blockquote>' . $quote . $footer . '</blockquote></div>';
    }

    // --------------------------------------------------------------------------

    /**
     * The public citation. Omitted when there is no text. Linked only when a URL is given.
     */
    private function footer(string $text, string $url): string
    {
        if ($text === '') {
            return '';
        }

        $text = htmlspecialchars($text, ENT_QUOTES);
        $url  = htmlspecialchars($url, ENT_QUOTES);
        $cite = $url !== '' ? '<a href="' . $url . '">' . $text . '</a>' : $text;

        return '<footer>' . $cite . '</footer>';
    }

    // --------------------------------------------------------------------------

    /**
     * The quotation, edited in place. An empty one shows a placeholder the canvas can click.
     */
    private function quoteField(string $quote): string
    {
        $empty = $quote === '';
        $attrs = [];
        if ($empty) {
            $attrs['data-cms-empty'] = '1';
        }

        return $this->editable(
            'quote',
            'div',
            $empty ? '<p class="cms-we-hint">Write something…</p>' : $quote,
            $attrs
        );
    }

    // --------------------------------------------------------------------------

    /**
     * The citation, edited in place. An empty one shows its placeholder.
     *
     * With a URL the field is the link itself, so clearing the text keeps the same element and the placeholder is
     * never wrapped in a link around it. Without one the field is plain text.
     */
    private function citationField(string $text, string $url): string
    {
        $empty = $text === '';
        $attrs = ['data-cms-placeholder' => 'Add a citation'];
        if ($empty) {
            $attrs['data-cms-empty'] = '1';
        }

        $value = $empty ? 'Add a citation' : $text;
        if ($url === '') {
            return '<footer>' . $this->editable('cite_text', 'span', $value, $attrs) . '</footer>';
        }

        $attrs['href'] = $url;

        return '<footer>' . $this->editable('cite_text', 'a', $value, $attrs) . '</footer>';
    }
}
