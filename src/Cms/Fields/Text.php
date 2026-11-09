<?php

/**
 * A single line of text. It can be edited in place on the canvas.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Field
 * @author      Nails Dev Team
 */

namespace Nails\Cms\Cms\Fields;

use Nails\Cms\WidgetEditor\Field\Base;

class Text extends Base
{
    protected ?int $maxLength = null;

    // --------------------------------------------------------------------------

    public static function getType(): string
    {
        return 'text';
    }

    // --------------------------------------------------------------------------

    public function maxLength(?int $maxLength): static
    {
        $this->maxLength = $maxLength;
        return $this;
    }

    // --------------------------------------------------------------------------

    public function getCanvasEdit(): ?string
    {
        return 'text';
    }

    // --------------------------------------------------------------------------

    protected function defaultValue(): mixed
    {
        return '';
    }

    // --------------------------------------------------------------------------

    protected function config(): array
    {
        return ['maxlength' => $this->maxLength];
    }
}
