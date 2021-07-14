<?php

namespace Nails\Cms\Cms\Monitor\Template;

use Nails\Cms\Constants;
use Nails\Cms\Interfaces;
use Nails\Factory;

/**
 * Class Page
 *
 * @package Nails\Cms\Cms\Monitor\Template
 */
class Page implements Interfaces\Monitor\Template
{
    public function getLabel(): string
    {
        return 'CMS: Pages';
    }

    // --------------------------------------------------------------------------

    public function countUsages(Interfaces\Template $oTemplate): int
    {
        /** @var \Nails\Common\Service\Database $oDb */
        $oDb = Factory::service('Database');
        $this->compileQuery($oTemplate);
        return $oDb->count_all_results();
    }

    // --------------------------------------------------------------------------

    public function getUsages(Interfaces\Template $oTemplate): array
    {
        /** @var \Nails\Common\Service\Database $oDb */
        $oDb = Factory::service('Database');
        /** @var \Nails\Cms\Model\Page $oModel */
        $oModel = Factory::model('Page', Constants::MODULE_SLUG);

        $oDb->select('id, published_title, published_slug, draft_title, draft_slug');
        $this->compileQuery($oTemplate);

        return array_map(function (\stdClass $oPage) use ($oModel) {

            /** @var \Nails\Cms\Factory\Monitor\Detail\Usage $oUsage */
            $oUsage = Factory::factory(
                'MonitorDetailUsage',
                Constants::MODULE_SLUG,
                $oPage->published_title ?: $oPage->draft_title,
                siteUrl($oPage->published_slug ?: $oPage->draft_slug),
                userHasPermission('admin:cms:pages:edit')
                    ? siteUrl('admin/cms/pages/edit/' . $oPage->id)
                    : null
            );

            return $oUsage;

        }, $oDb->get()->result());
    }

    // --------------------------------------------------------------------------

    private function compileQuery(Interfaces\Template $oTemplate): void
    {
        /** @var \Nails\Common\Service\Database $oDb */
        $oDb = Factory::service('Database');
        /** @var \Nails\Cms\Model\Page $oModel */
        $oModel = Factory::model('Page', Constants::MODULE_SLUG);

        $oDb->from($oModel->getTableName());
        $oDb->or_where('published_template', $oTemplate->getSlug());
        $oDb->or_where('draft_template', $oTemplate->getSlug());
    }
}
