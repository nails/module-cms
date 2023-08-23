<?php

namespace Nails\Cms\Cdn\Monitor\Page;

use Nails\Cms\Cdn\Monitor\ObjectIsInTemplateWidgetData;
use Nails\Cms\Constants;
use Nails\Common\Model\Base;
use Nails\Common\Resource\Entity;
use Nails\Factory;

class DraftTemplateData extends ObjectIsInTemplateWidgetData
{
    protected function getModel(): Base
    {
        return Factory::model('Page', Constants::MODULE_SLUG);
    }

    // --------------------------------------------------------------------------

    protected function getColumn(): string
    {
        return 'template_data';
    }

    // --------------------------------------------------------------------------

    protected function getState(): string
    {
        return static::STATE_DRAFT;
    }

    // --------------------------------------------------------------------------

    protected function getEntityLabel(Entity $oEntity): string
    {
        $sDraftTitle     = $oEntity->draft->title ?? null;
        $sPublishedTitle = $oEntity->published->title ?? null;

        if ($sDraftTitle !== $sPublishedTitle) {
            return sprintf(
                '%s (published: %s)',
                $sDraftTitle ?? '<no label>',
                $sPublishedTitle ?? '<no label>'
            );
        }

        return $sDraftTitle ?? '<no label>';
    }
}
