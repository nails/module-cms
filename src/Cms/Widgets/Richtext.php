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
            ['key' => 'body', 'type' => 'richtext', 'label' => 'Content', 'wide' => true],
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

        return $this->editable('body', 'div', $data['body'], ['class' => 'cms-widget cms-widget-richtext'], 'rich');
    }
}
