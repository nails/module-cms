<?php

namespace Nails\Cms\Cdn\Monitor;

use Nails\Cdn\Cdn\Monitor\ObjectIsInColumn;
use Nails\Cdn\Factory\Monitor\Detail;
use Nails\Cdn\Resource\CdnObject;
use Nails\Common\Resource\Entity;

abstract class ObjectIsInTemplateWidgetData extends ObjectIsInWidgetData
{
    const STATE_DRAFT     = 'draft';
    const STATE_PUBLISHED = 'published';

    // --------------------------------------------------------------------------

    abstract protected function getState(): string;

    // --------------------------------------------------------------------------

    protected function getJsonExtractPath(string $sSlug): string
    {
        return sprintf(
            'JSON_EXTRACT(`%s_%s`, \'$.*[*].slug\') LIKE \'%%"%s"%%\'',
            $this->getState(),
            $this->getColumn(),
            $sSlug
        );
    }

    // --------------------------------------------------------------------------

    protected function extractWidgetData(Entity $oEntity): object|array
    {
        return $oEntity->{$this->getState()}->{$this->getColumn()};
    }

    // --------------------------------------------------------------------------

    protected function extractDetailsFromWidgetData(
        array $aWidgets,
        array $aMappings,
        object|array $aWidgetData,
        CdnObject $oObject,
        Entity $oEntity
    ): array {

        $aDetails = [];
        foreach ($aWidgetData as $sWidgetArea => $aData) {
            $aDetails = array_merge(
                $aDetails,
                array_map(
                    function (Detail $oDetail) use ($sWidgetArea) {
                        $oData       = $oDetail->getData();
                        $oData->path = $sWidgetArea . '.' . $oData->path;
                        $oDetail->setData($oData);
                        return $oDetail;
                    },
                    parent::extractDetailsFromWidgetData(
                        $aWidgets,
                        $aMappings,
                        $aData,
                        $oObject,
                        $oEntity
                    )
                )
            );
        }

        return $aDetails;
    }
}
