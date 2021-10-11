<?php

/**
 * This class represents objects dispensed by the Page model
 *
 * @package  Nails\Cms\Resource
 * @category resource
 */

namespace Nails\Cms\Resource;

use Nails\Cms\Constants;
use Nails\Cms\Exception\RenderException;
use Nails\Cms\Resource\Page\Data;
use Nails\Cms\Service\Template;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Resource\Entity;
use Nails\Factory;

/**
 * Class Page
 *
 * @package Nails\Cms\Resource
 */
class Page extends Entity
{
    /** @var Data */
    public $published;

    /** @var Data */
    public $draft;

    /** @var bool */
    public $has_unpublished_changes;

    /** @var bool */
    public $is_published;

    /** @var bool */
    public $is_deleted;

    // --------------------------------------------------------------------------

    /**
     * Page constructor.
     *
     * @param array $mObj
     *
     * @throws FactoryException
     */
    public function __construct($mObj = [])
    {
        //  Loop properties and sort into published data and draft data
        $mObj->published = new \stdClass();
        $mObj->draft     = new \stdClass();

        foreach ($mObj as $sProperty => $mValue) {

            preg_match('/^(published|draft)_(.+)$/', $sProperty, $aMatches);

            if (!empty($aMatches[1]) && !empty($aMatches[2]) && $aMatches[1] == 'published') {
                $mObj->published->{$aMatches[2]} = $mValue;
                unset($mObj->{$sProperty});

            } elseif (!empty($aMatches[1]) && !empty($aMatches[2]) && $aMatches[1] == 'draft') {
                $mObj->draft->{$aMatches[2]} = $mValue;
                unset($mObj->{$sProperty});
            }
        }

        // --------------------------------------------------------------------------

        //  Unpublished changes?
        $mObj->has_unpublished_changes = $mObj->is_published && $mObj->draft->hash != $mObj->published->hash;

        // --------------------------------------------------------------------------

        //  SEO Title; If not set then fallback to the page title
        if (empty($mObj->seo_title) && !empty($mObj->title)) {
            $mObj->seo_title = $mObj->title;
        }

        // --------------------------------------------------------------------------

        $mObj->published = Factory::resource('PageData', Constants::MODULE_SLUG, $mObj->published);
        $mObj->draft     = Factory::resource('PageData', Constants::MODULE_SLUG, $mObj->draft);

        parent::__construct($mObj);
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the page as HTML
     *
     * @param bool $bRenderPublished Whether to use published or draft data
     *
     * @return string
     * @throws FactoryException
     */
    public function render(bool $bRenderPublished = true): string
    {
        /** @var Template $oTemplateService */
        $oTemplateService = Factory::service('Template', Constants::MODULE_SLUG);
        $oTemplate        = $oTemplateService->getBySlug(
            $bRenderPublished
                ? $this->published->template
                : $this->draft->template
        );

        if (!$oTemplate) {
            throw new RenderException(
                sprintf(
                    '"%s" is not a valid template.',
                    $bRenderPublished
                        ? $this->published->template
                        : $this->draft->template,
                )
            );
        }

        $oTemplateService->loadRenderAssets([$oTemplate]);

        return $oTemplate->render(
            (array) ($bRenderPublished ? $this->published->template_data : $this->draft->template_data),
            (array) ($bRenderPublished ? $this->published->template_options : $this->draft->template_options),
        );
    }
}
