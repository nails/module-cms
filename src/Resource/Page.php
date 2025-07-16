<?php

/**
 * This class represents objects dispensed by the Page model
 *
 * @package  Nails\Cms\Resource
 * @category resource
 */

namespace Nails\Cms\Resource;

use Nails\Admin\Interfaces\ChangeLog;
use Nails\Cms\Constants;
use Nails\Cms\Exception\RenderException;
use Nails\Cms\Resource\Page\Data;
use Nails\Cms\Service\Template;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Model\Base;
use Nails\Common\Resource\Entity;
use Nails\Factory;
use stdClass;

/**
 * Class Page
 *
 * @package Nails\Cms\Resource
 */
class Page extends Entity implements ChangeLog
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
     * @throws FactoryException
     */
    public function __construct(self|stdClass|array $resource = [], ?Base $model = null)
    {
        parent::__construct($resource, $model);

        //  Loop properties and sort into published data and draft data
        $dataPublished = new \stdClass();
        $dataDraft     = new \stdClass();

        foreach ($resource as $sProperty => $mValue) {

            preg_match('/^(published|draft)_(.+)$/', $sProperty, $aMatches);

            if (!empty($aMatches[1]) && !empty($aMatches[2]) && $aMatches[1] == 'published') {
                $dataPublished->{$aMatches[2]} = $mValue;
                unset($resource->{$sProperty});

            } elseif (!empty($aMatches[1]) && !empty($aMatches[2]) && $aMatches[1] == 'draft') {
                $dataDraft->{$aMatches[2]} = $mValue;
                unset($resource->{$sProperty});
            }
        }

        // --------------------------------------------------------------------------

        //  Unpublished changes?
        $resource->has_unpublished_changes = $resource->is_published && $dataDraft->hash != $dataPublished->hash;

        // --------------------------------------------------------------------------

        //  SEO Title; If not set then fallback to the page title
        if (empty($resource->seo_title) && !empty($resource->title)) {
            $resource->seo_title = $resource->title;
        }

        // --------------------------------------------------------------------------

        $this->published = Factory::resource('PageData', Constants::MODULE_SLUG, $dataPublished);
        $this->draft     = Factory::resource('PageData', Constants::MODULE_SLUG, $dataDraft);
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

    // --------------------------------------------------------------------------

    public static function getChageLogTypeLabel(): string
    {
        return 'CMS: Page';
    }

    // --------------------------------------------------------------------------

    public static function getChageLogTypeUrl(): string
    {
        return \Nails\Cms\Admin\Controller\Pages::url();
    }
}
