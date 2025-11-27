<?php

namespace Nails\Cms\Cdn\Monitor;

use DateTime;
use Nails\Cdn\Constants;
use Nails\Cdn\Factory\Monitor\Detail;
use Nails\Cdn\Resource\CdnObject;
use Nails\Cms\Resource\Page;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Model\Base;
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

    /**
     * @throws FactoryException
     */
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

    /**
     * @return Detail\Action[]
     * @throws FactoryException
     */
    protected function generateActions(Entity $oEntity, Base $oModel): array
    {
        $aActions = [];

        /** @var Page $oEntity */
        if ($oEntity->is_published) {
            /** @var Detail\Action $oActionView */
            $oActionView = Factory::factory('MonitorDetailAction', Constants::MODULE_SLUG);
            $aActions[]  = $oActionView
                ->setUrl($oEntity->published->url)
                ->setLabel('View')
                ->setTarget('_blank');
        }

        /** @var Detail\Action $oActionEdit */
        $oActionEdit = Factory::factory('MonitorDetailAction', Constants::MODULE_SLUG);
        $aActions[]  = $oActionEdit
            ->setUrl('admin/cms/pages/edit/' . $oEntity->id)
            ->setLabel('Edit')
            ->setClass('btn-primary')
            ->setTarget('_blank')
            ->setConfirm(true)
            ->setConfirmTitle('Refresh Page After Changes')
            ->setConfirmBody(
                <<<EOT
                This action will open in a new tab.
                <br><br>
                Once you have saved your changes, close the tab and refresh this page to see any changes applied.
                EOT
            );

        /** @var Detail\Action $oActionDelete */
        $oActionDelete = Factory::factory('MonitorDetailAction', Constants::MODULE_SLUG);
        $aActions[]    = $oActionDelete
            ->setUrl('admin/cms/pages/delete/' . $oEntity->id)
            ->setLabel('Delete')
            ->setClass('btn-danger')
            ->setConfirm(true)
            ->setConfirmTitle('Refresh Page After Changes')
            ->setConfirmBody(
                <<<EOT
                This action will open in a new tab.
                <br><br>
                Once you have saved your changes, close the tab and refresh this page to see any changes applied.
                EOT
            );

        return array_merge(
            parent::generateActions($oEntity, $oModel),
            $aActions
        );
    }

    // --------------------------------------------------------------------------

    /**
     * @throws FactoryException
     * @throws ModelException
     */
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

        //  Re-map to the database column
        $aData = [
            $this->getDatabaseColumn() => $aData[$this->getColumn()],
        ];

        if ($oModel->isAutoSetTimestamps()) {
            /** @var DateTime $oNow */
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
