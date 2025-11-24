<?php

namespace Nails\Cms\Cdn\Monitor;

use Nails\Cdn\Cdn\Monitor\ObjectIsInColumn;
use Nails\Cdn\Factory\Monitor\Detail;
use Nails\Cdn\Resource\CdnObject;
use Nails\Common\Resource\Entity;
use Nails\Common\Service\Database;
use Nails\Factory;

abstract class ObjectIsInTemplateWidgetData extends ObjectIsInWidgetData
{
    const STATE_DRAFT     = 'draft';
    const STATE_PUBLISHED = 'published';

    // --------------------------------------------------------------------------

    abstract protected function getState(): string;

    // --------------------------------------------------------------------------

    abstract protected function getDatabaseColumn(): string;

    // --------------------------------------------------------------------------

    protected function getJsonExtractPath(string $sSlug): string
    {
        return sprintf(
            'JSON_EXTRACT(`%s`, \'$.*[*].slug\') LIKE \'%%"%s"%%\'',
            $this->getDatabaseColumn(),
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
        object|array|null $aWidgetData,
        CdnObject $oObject,
        Entity $oEntity
    ): array {

        if (empty($aWidgetData)) {
            return [];
        }

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

    // --------------------------------------------------------------------------

    protected function updateEntity(Entity $oEntity, array $aData): void
    {
        /**
         * The Page model is a mess and doesn't support updating individual columns.
         * Updating this way has knock on effects in terms of data sanity checks, hash
         * generation and anything which binds to the listeners.
         */

        /** @var Database $oDb */
        $oDb    = Factory::service('Database');
        $oModel = $this->getModel();

        $aData = [
            $this->getDatabaseColumn() => $aData[$this->getColumn()],
        ];

        if ($oModel->isAutoSetTimestamps()) {
            /** @var \DateTime $oNow */
            $oNow              = Factory::factory('DateTime');
            $aData['modified'] = $oNow->format('Y-m-d H:i:s');
        }

        if ($oModel->isAutoSetUsers()) {
            $aData['modified_by'] = activeUser('id');
        }

        $oDb
            ->set($aData)
            ->where($oModel->getColumnId(), $oEntity->id)
            ->update($oModel->getTableName());
    }
}
