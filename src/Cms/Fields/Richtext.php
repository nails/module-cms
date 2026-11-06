<?php

/**
 * Rich text, stored as HTML and edited with Tiptap. On the canvas it is edited in place when the widget marks it
 * with editable(); otherwise the inspector opens the same editor in a modal.
 *
 * basic() is CKEditor's wysiwyg-basic choice: formatting only (bold, italic, links), no headings, lists, quotes or
 * code blocks.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Field
 * @author      Nails Dev Team
 */

namespace Nails\Cms\Cms\Fields;

use Nails\Cms\WidgetEditor\Field\Base;

class Richtext extends Base
{
    protected bool $basic = false;

    // --------------------------------------------------------------------------

    public static function getType(): string
    {
        return 'richtext';
    }

    // --------------------------------------------------------------------------

    public function basic(bool $basic = true): static
    {
        $this->basic = $basic;
        return $this;
    }

    // --------------------------------------------------------------------------

    public function getCanvasEdit(): ?string
    {
        return $this->basic ? 'rich-basic' : 'rich';
    }

    // --------------------------------------------------------------------------

    protected function defaultValue(): mixed
    {
        return '';
    }

    // --------------------------------------------------------------------------

    protected function config(): array
    {
        return ['editor' => $this->basic ? 'basic' : 'default'];
    }
}
