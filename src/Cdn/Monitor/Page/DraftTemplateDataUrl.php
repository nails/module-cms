<?php

namespace Nails\Cms\Cdn\Monitor\Page;

use Nails\Cms\Cdn\Monitor\ObjectIsUrlInTemplateWidgetData;
use Nails\Cms\Constants;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Model\Base;
use Nails\Factory;

class DraftTemplateDataUrl extends ObjectIsUrlInTemplateWidgetData
{
    /**
     * @throws FactoryException
     */
    public function getModel(): Base
    {
        return Factory::model('Page', Constants::MODULE_SLUG);
    }

    protected function getColumn(): string
    {
        return 'template_data';
    }

    protected function getState(): string
    {
        return static::STATE_DRAFT;
    }

    protected function getDatabaseColumn(): string
    {
        return sprintf('%s_%s', $this->getState(), $this->getColumn());
    }
}
