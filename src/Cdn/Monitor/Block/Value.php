<?php

namespace Nails\Cms\Cdn\Monitor\Block;

use Nails\Cdn\Cdn\Monitor\ObjectIsInColumn;
use Nails\Cms\Constants;
use Nails\Cms\Model\Block;
use Nails\Common\Helper\Model\WhereIn;
use Nails\Common\Model\Base;
use Nails\Factory;

class Value extends ObjectIsInColumn
{
    protected function getModel(): Base
    {
        return Factory::model('Block', Constants::MODULE_SLUG);
    }

    // --------------------------------------------------------------------------

    protected function getColumn(): string
    {
        return 'value';
    }

    // --------------------------------------------------------------------------

    protected function getAdditionalQueryData(): array
    {
        /** @var Block $oModel */
        $oModel = $this->getModel();

        return [
            new WhereIn('type', [$oModel::TYPE_FILE, $oModel::TYPE_IMAGE]),
        ];
    }
}
