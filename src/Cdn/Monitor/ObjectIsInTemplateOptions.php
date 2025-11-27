<?php

namespace Nails\Cms\Cdn\Monitor;

use Closure;
use DateTime;
use Nails\Cdn\Cdn\Monitor\ObjectIsInColumn;
use Nails\Cdn\Factory\Monitor\Detail;
use Nails\Cdn\Resource\CdnObject;
use Nails\Cms\Constants;
use Nails\Cms\Resource\Page;
use Nails\Cms\Service\Monitor\Cdn;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Exception\NailsException;
use Nails\Common\Helper\Model\Condition;
use Nails\Common\Model\Base;
use Nails\Common\Resource\Entity;
use Nails\Common\Service\Database;
use Nails\Factory;

abstract class ObjectIsInTemplateOptions extends ObjectIsInColumn
{
    const STATE_DRAFT     = 'draft';
    const STATE_PUBLISHED = 'published';

    // --------------------------------------------------------------------------

    abstract protected function getState(): string;

    // --------------------------------------------------------------------------

    /**
     * @throws FactoryException
     */
    public function getModel(): Base
    {
        return Factory::model('Page', Constants::MODULE_SLUG);
    }

    // --------------------------------------------------------------------------

    protected function getColumn(): string
    {
        return 'template_options';
    }

    // --------------------------------------------------------------------------

    protected function getDatabaseColumn(): string
    {
        return sprintf('%s_%s', $this->getState(), $this->getColumn());
    }

    // --------------------------------------------------------------------------

    protected function getEntityLabel(Entity $oEntity): string
    {
        return $oEntity->{$this->getState()}->title ?: '<no label>';
    }

    // --------------------------------------------------------------------------

    /**
     * @return Detail[]
     * @throws FactoryException
     * @throws ModelException
     */
    public function locate(CdnObject $oObject, ?Closure $fnCreateDetail = null): array
    {
        $oModel    = $this->getModel();
        $aDetails  = [];
        $aMappings = $this->getCdnMappings();
        if (empty($aMappings)) {
            //  No mappings, nothing to locate
            return [];
        }

        parent::locate($oObject, function (Entity $oEntity) use (&$aDetails, $aMappings, $oObject, $oModel) {

            $oOptions = $this->getOptionsFromEntity($oEntity);

            foreach ($aMappings as $sTemplate => $aPaths) {
                foreach ($aPaths as $sPath) {
                    if ($oEntity->{$this->getState()}->template === $sTemplate) {
                        if ($oObject->id === (int) ($oOptions->{$sPath} ?? null)) {
                            $aDetails[] = $this->createDetail($oEntity, $oModel, ['path' => $sPath]);
                        }
                    }
                }
            }

        });

        return $aDetails;
    }

    // --------------------------------------------------------------------------

    /**
     * @return Condition[]
     * @throws FactoryException
     */
    protected function getQueryConditions(CdnObject $oObject): array
    {
        $aMappings   = $this->getCdnMappings();
        $aConditions = [];

        foreach ($aMappings as $aPaths) {
            foreach ($aPaths as $sPath) {
                $aConditions[] = sprintf(
                    'JSON_EXTRACT(`%s`, \'$.%s\') LIKE \'%%"%s"%%\'',
                    $this->getDatabaseColumn(),
                    $sPath,
                    $oObject->id,
                );
            }
        }

        return [
            new Condition(implode(PHP_EOL . ' OR ', $aConditions)),
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * @throws FactoryException
     */
    protected function getCdnMappings(): array
    {
        /** @var Cdn $oCdnMonitor */
        $oCdnMonitor = Factory::service('MonitorCdn', Constants::MODULE_SLUG);
        return $oCdnMonitor->getTemplateMappings();
    }

    // --------------------------------------------------------------------------

    /**
     * @throws FactoryException
     * @throws ModelException
     * @throws NailsException
     */
    public function delete(Detail $oDetail, CdnObject $oObject): void
    {
        $oEntity  = $this->getEntityFromDetail($oDetail);
        $oOptions = $this->getOptionsFromEntity($oEntity);

        $oOptions->{$oDetail->getData()->path} = null;

        $this->updateEntity(
            $oEntity,
            [
                $this->getDatabaseColumn() => json_encode($oOptions),
            ]
        );
    }

    // --------------------------------------------------------------------------

    /**
     * @throws FactoryException
     * @throws ModelException
     * @throws NailsException
     */
    public function replace(Detail $oDetail, CdnObject $oObject, CdnObject $oReplacement): void
    {
        $oEntity  = $this->getEntityFromDetail($oDetail);
        $oOptions = $this->getOptionsFromEntity($oEntity);

        //  Cast as a string as that is how it is stored when a value is set via the admin UI
        $oOptions->{$oDetail->getData()->path} = (string) $oReplacement->id;

        $this->updateEntity(
            $oEntity,
            [
                $this->getDatabaseColumn() => json_encode($oOptions),
            ]
        );
    }

    // --------------------------------------------------------------------------

    protected function getOptionsFromEntity(Entity $oEntity): \stdClass
    {
        return $oEntity->{$this->getState()}->{$this->getColumn()};
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
            $oActionView = Factory::factory('MonitorDetailAction', \Nails\Cdn\Constants::MODULE_SLUG);
            $aActions[]  = $oActionView
                ->setUrl($oEntity->published->url)
                ->setLabel('View')
                ->setTarget('_blank');
        }

        /** @var Detail\Action $oActionEdit */
        $oActionEdit = Factory::factory('MonitorDetailAction', \Nails\Cdn\Constants::MODULE_SLUG);
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
        $oActionDelete    = Factory::factory('MonitorDetailAction', \Nails\Cdn\Constants::MODULE_SLUG);
        $aActions[] = $oActionDelete
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
         * generation and anything which binds to the listeners. Hopefully not a problem
         * though as we're updating an option rather than anything to do with page generation.
         */

        /** @var Database $oDb */
        $oDb    = Factory::service('Database');
        $oModel = $this->getModel();

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
