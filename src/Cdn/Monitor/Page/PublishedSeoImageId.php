<?php

namespace Nails\Cms\Cdn\Monitor\Page;

class PublishedSeoImageId extends DraftSeoImageId
{
    protected function getColumn(): string
    {
        return 'published_seo_image_id';
    }
}
