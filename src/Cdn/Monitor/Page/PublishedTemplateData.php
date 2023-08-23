<?php

namespace Nails\Cms\Cdn\Monitor\Page;

class PublishedTemplateData extends DraftTemplateData
{
    protected function getState(): string
    {
        return static::STATE_PUBLISHED;
    }
}
