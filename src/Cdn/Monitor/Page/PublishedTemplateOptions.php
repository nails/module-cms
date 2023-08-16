<?php

namespace Nails\Cms\Cdn\Monitor\Page;

class PublishedTemplateOptions extends DraftTemplateOptions
{
    protected function getState(): string
    {
        return static::STATE_PUBLISHED;
    }
}
