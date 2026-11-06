<?php

/**
 * The "Rich Text" widget for WidgetEditor (v2). Ports the v1 widget: the stored slug `richtext` resolves through
 * ALIASES, and the stored `body` key is unchanged.
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

class Richtext extends Base
{
    const ALIASES = ['richtext'];

    protected string $label       = 'Rich Text';
    protected string $summary     = 'Build beautiful pages using the rich text editor; embed images, links and more.';
    protected string $description = 'Build beautiful pages using the rich text editor; embed images, links and more.';
    protected string $icon        = 'fa-paragraph';
    protected array $keywords     = ['rich text', 'formatted text', 'formatted', 'wysiwyg', 'embed'];
    protected array $defaults     = ['body' => ''];

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function getFields(): ?array
    {
        return [
            Fields\Richtext::make('body')->label('Content'),
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function render(array $data): string
    {
        $body = (string) ($data['body'] ?? '');
        if ($body === '') {
            // The public page stays empty. The canvas draws a hint, so the field can be clicked.
            if (!$this->isPreview()) {
                return '';
            }

            return $this->editable(
                'body',
                'div',
                '<p class="cms-we-hint">Write something…</p>',
                ['class' => 'cms-widget cms-widget-richtext', 'data-cms-empty' => '1']
            );
        }

        return $this->editable('body', 'div', $body, ['class' => 'cms-widget cms-widget-richtext']);
    }
}
