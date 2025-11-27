<?php

namespace Nails\Cms\Cdn\Monitor\Page;

class PublishedTemplateDataUrl extends DraftTemplateDataUrl
{
    protected function getState(): string
    {
        return static::STATE_PUBLISHED;
    }
}
