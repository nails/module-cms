<?php

namespace Nails\Cms\Resource\Page;

use Nails\Cms\Constants;
use Nails\Cms\Resource\Page\Data\Breadcrumb;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Resource;
use Nails\Factory;

/**
 * Class Data
 *
 * @package Nails\Cms\Resource\Page
 */
class Data extends Resource
{
    /** @var string */
    public $hash;

    /** @var string */
    public $slug;

    /** @var string */
    public $slug_end;

    /** @var string */
    public $parent_id;

    /** @var string */
    public $template;

    /** @var string */
    public $template_data;

    /** @var string */
    public $template_options;

    /** @var string */
    public $title;

    /** @var Breadcrumb[] */
    public $breadcrumbs;

    /** @var string */
    public $seo_title;

    /** @var string */
    public $seo_description;

    /** @var string */
    public $seo_keywords;

    /** @var int|null */
    public $seo_image_id;

    /** @var int */
    public $depth;

    /** @var string */
    public $url;

    // --------------------------------------------------------------------------

    /**
     * Data constructor.
     *
     * @throws FactoryException
     */
    public function __construct(self|\stdClass|array $resource = [])
    {
        $resource->depth = count(explode('/', (string) $resource->slug)) - 1;
        $resource->url   = siteUrl($resource->slug);

        //  Decode JSON
        $resource->template_data    = json_decode($resource->template_data ?? 'null');
        $resource->template_options = json_decode($resource->template_options ?? 'null') ?: [];
        $resource->breadcrumbs      = json_decode($resource->breadcrumbs ?? 'null') ?: [];

        // --------------------------------------------------------------------------

        foreach ($resource->breadcrumbs as &$oBreadcrumb) {
            $oBreadcrumb = Factory::resource('PageDataBreadcrumb', Constants::MODULE_SLUG, $oBreadcrumb);
        }

        parent::__construct($resource);
    }
}
