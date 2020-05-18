<?php

namespace Nails\Cms\SiteMap\Generator;

use Nails\Cms\Constants;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Factory;
use Nails\SiteMap;

/**
 * Class Page
 *
 * @package Nails\Cms\SiteMap\Generator
 */
class Page implements SiteMap\Interfaces\Generator
{
    /**
     * Returns an array of URLs for the sitemap
     *
     * @return SiteMap\Factory\Url[]
     * @throws FactoryException
     * @throws ModelException
     */
    public function execute(): array
    {
        /** @var \Nails\Cms\Model\Page $oModel */
        $oModel = Factory::model('Page', Constants::MODULE_SLUG);
        $aUrls  = [];

        foreach ($oModel->getAll() as $oItem) {

            if (!$oItem->is_published) {
                continue;
            }

            /** @var SiteMap\Factory\Url $oUrl */
            $oUrl = Factory::factory('Url', SiteMap\Constants::MODULE_SLUG);
            $oUrl->setUrl($oItem->published->url);

            $aUrls[] = $oUrl;
        }

        return $aUrls;
    }
}
