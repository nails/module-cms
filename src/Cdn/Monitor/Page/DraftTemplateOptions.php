<?php

namespace Nails\Cms\Cdn\Monitor\Page;

use Nails\Cms\Cdn\Monitor\ObjectIsInTemplateOptions;

class DraftTemplateOptions extends ObjectIsInTemplateOptions
{
    protected function getState(): string
    {
        return static::STATE_DRAFT;
    }
}
