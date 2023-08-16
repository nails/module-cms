<?php

namespace Nails\Cms\Cdn\Monitor\Page;

class PublishedTemplateData extends DraftTemplateData
{
    protected function getColumn(): string
    {
        return 'published_template_data';
    }
}
