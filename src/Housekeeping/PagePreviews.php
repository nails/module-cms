<?php

namespace Nails\Cms\Housekeeping;

use Nails\Cms\Constants;
use Nails\Common\Model\Base as ModelBase;
use Nails\Factory;
use Nails\Housekeeping\Routine\Base;
use Nails\Housekeeping\Traits\DeletesModelRows;

/**
 * Deletes page previews once they are an hour old.
 *
 * A preview is created each time the preview button is pressed and is only
 * needed for that page load. The job itself runs once a day.
 */
class PagePreviews extends Base
{
    use DeletesModelRows;

    const LABEL           = 'CMS page previews';
    const DESCRIPTION     = 'Deletes page previews created more than 1 hour ago';
    const CRON_EXPRESSION = '@daily';

    protected function model(): ModelBase
    {
        return Factory::model('PagePreview', Constants::MODULE_SLUG);
    }

    /**
     * @return array<int, mixed>
     */
    protected function where(): array
    {
        /** @var \DateTime $oCutoff */
        $oCutoff = Factory::factory('DateTime');
        $oCutoff->sub(new \DateInterval('PT1H'));

        return [
            ['created <', $oCutoff->format('Y-m-d H:i:s')],
        ];
    }

    /**
     * @return string[]
     */
    protected function auditColumns(): array
    {
        return ['id', 'created', 'draft_title'];
    }
}
