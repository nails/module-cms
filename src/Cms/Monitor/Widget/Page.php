<?php

namespace Nails\Cms\Cms\Monitor\Widget;

use Nails\Cms\Constants;
use Nails\Cms\Interfaces;
use Nails\Factory;

/**
 * Class Page
 *
 * @package Nails\Cms\Cms\Monitor\Widget
 */
class Page implements Interfaces\Monitor\Widget
{
    public function getLabel(): string
    {
        return 'CMS: Pages';
    }

    // --------------------------------------------------------------------------

    public function countUsages(Interfaces\Widget $oWidget): int
    {
        /** @var \Nails\Common\Service\Database $oDb */
        $oDb = Factory::service('Database');
        $this->compileQuery($oWidget);
        return $oDb->count_all_results();
    }

    // --------------------------------------------------------------------------

    public function getUsages(Interfaces\Widget $oWidget): array
    {
        /** @var \Nails\Common\Service\Database $oDb */
        $oDb = Factory::service('Database');
        /** @var \Nails\Cms\Model\Page $oModel */
        $oModel = Factory::model('Page', Constants::MODULE_SLUG);

        $oDb->select('id, published_title, published_slug, draft_title, draft_slug');
        $this->compileQuery($oWidget);

        return array_map(function (\stdClass $oPage) {

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

    private function compileQuery(Interfaces\Widget $oWidget): void
    {
        /** @var \Nails\Common\Service\Database $oDb */
        $oDb = Factory::service('Database');
        /** @var \Nails\Cms\Model\Page $oModel */
        $oModel = Factory::model('Page', Constants::MODULE_SLUG);

        $oDb->from($oModel->getTableName());
        $oDb->or_where(sprintf(
            'JSON_CONTAINS(JSON_EXTRACT(published_template_data, "$.*[*].slug"), \'"%s"\', \'$\')',
            $oWidget->getSlug()
        ));
        $oDb->or_where(sprintf(
            'JSON_CONTAINS(JSON_EXTRACT(draft_template_data, "$.*[*].slug"), \'"%s"\', \'$\')',
            $oWidget->getSlug()
        ));
    }
}
