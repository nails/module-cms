<?php

/**
 * Multi-line plain text. It is edited in the inspector: the canvas edits only a single line in place.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Field
 * @author      Nails Dev Team
 */

namespace Nails\Cms\Cms\Fields;

use Nails\Cms\WidgetEditor\Field\Base;

class Textarea extends Base
{
    protected ?int $maxLength = null;

    // --------------------------------------------------------------------------

    public static function getType(): string
    {
        return 'textarea';
    }

    // --------------------------------------------------------------------------

    public function maxLength(?int $maxLength): static
    {
        $this->maxLength = $maxLength;
        return $this;
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
