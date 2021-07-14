<?php

namespace Nails\Cms\Cms\Monitor\Widget;

use Nails\Cms\Constants;
use Nails\Cms\Interfaces;
use Nails\Factory;

/**
 * Class Area
 *
 * @package Nails\Cms\Cms\Monitor\Widget
 */
class Area implements Interfaces\Monitor\Widget
{
    public function getLabel(): string
    {
        return 'CMS: Areas';
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
        $oDb->select('id, label');
        $this->compileQuery($oWidget);

        return array_map(function (\stdClass $oArea) {

            /** @var \Nails\Cms\Factory\Monitor\Detail\Usage $oUsage */
            $oUsage = Factory::factory(
                'MonitorDetailUsage',
                Constants::MODULE_SLUG,
                $oArea->label,
                null,
                userHasPermission('admin:cms:area:edit')
                    ? siteUrl('admin/cms/area/edit/' . $oArea->id)
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
        /** @var \Nails\Cms\Model\Area $oModel */
        $oModel = Factory::model('Area', Constants::MODULE_SLUG);

        $oDb->from($oModel->getTableName());
        $oDb->where(sprintf(
            'JSON_CONTAINS(JSON_EXTRACT(widget_data, "$[*].slug"), \'"%s"\', \'$\')',
            $oWidget->getSlug()
        ));
    }
}
