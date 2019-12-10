<?php

namespace Nails\Cms\Model\Page;

use Nails\Cms\Model\Page;

/**
 * Class Preview
 *
 * @package Nails\Cms\Model\Page
 */
class Preview extends Page
{
    /**
     * The table this model represents
     *
     * @var string
     */
    const TABLE = NAILS_DB_PREFIX . 'cms_page_preview';

    /**
     * Whether the model is a preview
     *
     * @var bool
     */
    const IS_PREVIEW = true;
}
