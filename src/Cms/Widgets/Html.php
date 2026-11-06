<?php

/**
 * The "Plain Text" widget for WidgetEditor (v2). Ports the v1 widget: the stored slug `html` resolves through
 * ALIASES, and the stored `body` key is unchanged. The body is output as it is stored, as in v1.
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

class Html extends Base
{
    const ALIASES = ['html'];

    protected string $label       = 'Plain Text';
    protected string $summary     = 'Plain, completely unformatted text. Perfect for custom HTML.';
    protected string $description = 'Plain, completely unformatted text. Perfect for custom HTML.';
    protected string $icon        = 'fa-code';
    protected array $keywords     = ['text', 'html', 'code', 'plaintext', 'plain text'];
    protected array $defaults     = ['body' => ''];

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function getFields(): ?array
    {
        return [
            Fields\Textarea::make('body')->label('Text')->wide(),
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function render(array $data): string
    {
        if (empty($data['body'])) {
            return '';
        }

        return '<div class="cms-widget cms-widget-html">' . $data['body'] . '</div>';
    }
}
