<?php

namespace Nails\Cms\Cms\Monitor\Widget;

use Nails\Cms\Constants;
use Nails\Cms\Interfaces;
use Nails\Cms\Traits;
use Nails\Factory;

/**
 * Class Area
 *
 * @package Nails\Cms\Cms\Monitor\Widget
 */
class Area implements Interfaces\Monitor\Widget
{
    use Traits\Monitor\Widget;

    // --------------------------------------------------------------------------

    public function getLabel(): string
    {
        return 'CMS: Areas';
    }

    // --------------------------------------------------------------------------

    protected function getTableName(): string
    {
        return Factory::model('Area', Constants::MODULE_SLUG)->getTableName();
    }

    // --------------------------------------------------------------------------

    private function getDataColumns(): array
    {
        return ['widget_data'];
    }

    // --------------------------------------------------------------------------

    private function getQueryColumns(): array
    {
        return ['id', 'label'];
    }

    // --------------------------------------------------------------------------

    protected function compileUsage(\stdClass $oRow): \Nails\Cms\Factory\Monitor\Detail\Usage
    {
        /** @var \Nails\Cms\Factory\Monitor\Detail\Usage $oUsage */
        $oUsage = Factory::factory(
            'MonitorDetailUsage',
            Constants::MODULE_SLUG,
            $oRow->label,
            null,
            userHasPermission('admin:cms:area:edit')
                ? siteUrl('admin/cms/area/edit/' . $oRow->id)
                : null
        );

        return $oUsage;
    }
}
