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
     * @param array $mObj
     *
     * @throws FactoryException
     */
    public function __construct($mObj = [])
    {
        $mObj->depth = count(explode('/', $mObj->slug)) - 1;
        $mObj->url   = siteUrl($mObj->slug);

        //  Decode JSON
        $mObj->template_data    = json_decode($mObj->template_data ?? 'null');
        $mObj->template_options = json_decode($mObj->template_options);
        $mObj->breadcrumbs      = json_decode($mObj->breadcrumbs) ?: [];

        // --------------------------------------------------------------------------

        foreach ($mObj->breadcrumbs as &$oBreadcrumb) {
            $oBreadcrumb = Factory::resource('PageDataBreadcrumb', Constants::MODULE_SLUG, $oBreadcrumb);
        }

        parent::__construct($mObj);
    }
}
