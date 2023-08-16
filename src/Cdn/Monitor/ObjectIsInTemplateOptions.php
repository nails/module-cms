<?php

namespace Nails\Cms\Cdn\Monitor;

use Nails\Cdn\Cdn\Monitor\ObjectIsInColumn;
use Nails\Cdn\Factory\Monitor\Detail;
use Nails\Cdn\Resource\CdnObject;
use Nails\Cms\Constants;
use Nails\Cms\Resource\Page;
use Nails\Cms\Service\Monitor\Cdn;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
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
    protected function getModel(): Base
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
    public function locate(CdnObject $oObject): array
    {
        /** @var Cdn $oCdnMonitor */
        $oCdnMonitor = Factory::service('MonitorCdn', Constants::MODULE_SLUG);

        $aMappings   = $oCdnMonitor->getTemplateMappings();
        $aConditions = [];

        foreach ($aMappings as $sTemplate => $aPaths) {
            foreach ($aPaths as $sPath) {
                $aConditions[] = sprintf(
                    'JSON_EXTRACT(`%s`, \'$.%s\') LIKE \'%%"%s"%%\'',
                    $this->getDatabaseColumn(),
                    $sPath,
                    $oObject->id,
                );
            }
        }

        /** @var Page[] $aPages */
        $aPages = $this
            ->getModel()
            ->getAll([
                new Condition(implode(PHP_EOL . ' OR ', $aConditions)),
            ]);

        $aDetails = [];
        foreach ($aPages as $oPage) {

            $oOptions = $this->getOptionsFromEntity($oPage);

            foreach ($aMappings as $sTemplate => $aPaths) {
                foreach ($aPaths as $sPath) {
                    if ($oPage->{$this->getState()}->template === $sTemplate) {
                        if ($oObject->id === (int) ($oOptions->{$sPath} ?? null)) {
                            $aDetails[] = $this->createDetail($oPage, ['path' => $sPath]);
                        }
                    }
                }
            }
        }

        return $aDetails;
    }

    // --------------------------------------------------------------------------

    /**
     * @throws FactoryException
     * @throws ModelException
     */
    public function delete(Detail $oDetail, CdnObject $oObject): void
    {
        $iId      = $oDetail->getData()->id;
        $oOptions = $this->getOptionsFromEntityId($iId);

        $oOptions->{$oDetail->getData()->path} = null;

        $this->updateEntity($iId, $oOptions);
    }

    // --------------------------------------------------------------------------

    /**
     * @throws FactoryException
     * @throws ModelException
     */
    public function replace(Detail $oDetail, CdnObject $oObject, CdnObject $oReplacement): void
    {
        $iId      = $oDetail->getData()->id;
        $oOptions = $this->getOptionsFromEntityId($iId);

        //  Cast as string as that is how it is stored when a value is set via the admin UI
        $oOptions->{$oDetail->getData()->path} = (string) $oReplacement->id;

        $this->updateEntity($iId, $oOptions);
    }

    // --------------------------------------------------------------------------

    /**
     * @throws ModelException
     */
    protected function getOptionsFromEntityId(int $iId): \stdClass
    {
        $oEntity = $this
            ->getModel()
            ->getById($iId);

        return $this->getOptionsFromEntity($oEntity);
    }

    // --------------------------------------------------------------------------

    protected function getOptionsFromEntity(Entity $oEntity): \stdClass
    {
        return $oEntity->{$this->getState()}->{$this->getColumn()};
    }

    // --------------------------------------------------------------------------

    /**
     * @throws FactoryException
     * @throws ModelException
     */
    protected function updateEntity(int $iEntityId, \stdClass $oOptions): void
    {
        /**
         * The Page model is a mess and doesn't support updating individual columns.
         * Updating this way has knock on effects in terms of data sanity checks, hash
         * generation and anything which binds to the listeners. Hopefully not a problem
         * though as we're updating an option rather than anything to do with page generation.
         */

        /** @var Database $oDb */
        $oDb = Factory::service('Database');
        $oDb
            ->set($this->getDatabaseColumn(), json_encode($oOptions))
            ->where('id', $iEntityId)
            ->update($this->getModel()->getTableName());
    }
}
