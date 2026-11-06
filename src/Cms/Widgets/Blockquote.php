<?php

/**
 * The "Blockquote" widget for WidgetEditor (v2). Ports the v1 widget: the stored slug `blockquote` resolves through
 * ALIASES, and the stored keys are unchanged.
 *
 * The v1 view inverted the citation link (the text was linked only when there was no URL). Here the text is
 * linked when a URL is given and is plain otherwise.
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
    protected array $keywords     = ['text', 'plain text', 'quote', 'blockquote', 'callout'];
    protected array $defaults     = ['quote' => '', 'cite_text' => '', 'cite_url' => ''];

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function getFields(): ?array
    {
        return [
            Fields\Richtext::make('quote')->label('Quotation')->basic(),
            Fields\Text::make('cite_text')->label('Citation'),
            Fields\Text::make('cite_url')->label('Citation URL'),
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function render(array $data): string
    {
        $sQuote = (string) ($data['quote'] ?? '');
        if ($sQuote === '') {
            return '';
        }

        $sText   = htmlspecialchars((string) ($data['cite_text'] ?? ''), ENT_QUOTES);
        $sUrl    = htmlspecialchars((string) ($data['cite_url'] ?? ''), ENT_QUOTES);
        $sFooter = '';

        if ($sText !== '') {
            $sCite   = $sUrl !== '' ? '<a href="' . $sUrl . '">' . $sText . '</a>' : $sText;
            $sFooter = '<footer>' . $sCite . '</footer>';
        }

        return '<div class="cms-widget cms-widget-blockquote"><blockquote>' . $sQuote . $sFooter . '</blockquote></div>';
    }
}
