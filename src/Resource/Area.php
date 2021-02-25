<?php

/**
 * This class represents objects dispensed by the Area model
 *
 * @package  Nails\Cms\Resource
 * @category resource
 */

namespace Nails\Cms\Resource;

use Nails\Cms\Constants;
use Nails\Cms\Exception\Widget\NotFoundException;
use Nails\Cms\Service\Widget;
use Nails\Cms\Widget\WidgetBase;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Resource\Entity;
use Nails\Environment;
use Nails\Factory;

/**
 * Class Area
 *
 * @package Nails\Cms\Resource
 */
class Area extends Entity
{
    /** @var string */
    public $label;

    /** @var string */
    public $description;

    /** @var string|array|null */
    public $widget_data;

    // --------------------------------------------------------------------------

    /**
     * Renders an area
     *
     * @return string
     * @throws NotFoundException
     * @throws FactoryException
     */
    public function render(): string
    {
        $sOut        = '';
        $aWidgetData = $this->widget_data;

        if (is_string($aWidgetData)) {
            $aWidgetData = json_decode($aWidgetData);
        }

        if (!empty($aWidgetData)) {

            /** @var Widget $oWidgetService */
            $oWidgetService = Factory::service('Widget', Constants::MODULE_SLUG);

            foreach ($aWidgetData as $oWidgetData) {

                $sSlug = $oWidgetData->slug ?? '';
                /** @var WidgetBase $oWidget */
                $oWidget = $oWidgetService->getBySlug($sSlug);

                if (!empty($oWidget)) {
                    $sOut .= $oWidget->render((array) $oWidgetData->data);

                } elseif (Environment::not(Environment::ENV_PROD)) {
                    throw new NotFoundException('"' . $sSlug . '" is not a valid widget');
                }
            }
        }

        return $sOut;
    }
}
