<?php

namespace Nails\Cms\Cdn\Monitor;

use Nails\Cdn\Cdn\Monitor\ObjectIsInColumn;
use Nails\Cdn\Exception\CdnException;
use Nails\Cdn\Factory\Monitor\Detail;
use Nails\Cdn\Resource\CdnObject;
use Nails\Cms\Constants;
use Nails\Cms\Helper\Monitor;
use Nails\Cms\Service\Monitor\Cdn;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Cms\WidgetEditor\Service\Registry;
use Nails\Common\Helper\Model\Condition;
use Nails\Common\Helper\Model\Select;
use Nails\Common\Helper\Model\Sort;
use Nails\Common\Helper\Model\Where;
use Nails\Common\Resource\Entity;
use Nails\Common\Service\Database;
use Nails\Factory;

abstract class ObjectIsInWidgetData extends ObjectIsInColumn
{
    /**
     * @return Detail[]
     * @throws FactoryException
     * @throws ModelException
     */
    public function locate(CdnObject $oObject, ?\Closure $fnCreateDetail = null): array
    {
        $aMappings = $this->getWidgetMappings();
        if (empty($aMappings)) {
            //  No mappings, nothing to locate
            return [];
        }

        $aWidgets = array_keys($aMappings);

        $oModel = $this->getModel();
        if (!$oModel->isDestructiveDelete()) {
            $oModel->includeDeleted();
        }

        /** @var Entity[] $aResults */
        $aResults = $oModel
            ->getAll(array_merge(
                $this->getQuerySelect(),
                $this->getQueryConditions($oObject, $aMappings, $aWidgets),
                $this->getQuerySort(),
            ));

        $aDetails = [];
        foreach ($aResults as $oEntity) {

            //  Only return rows where the object is actually used
            $aWidgetData = $this->extractWidgetData($oEntity);

            $aDetails = array_merge(
                $aDetails,
                $this->extractDetailsFromWidgetData(
                    $aWidgets,
                    $aMappings,
                    $aWidgetData,
                    $oObject,
                    $oEntity,
                )
            );
        }

        return $aDetails;
    }

    // --------------------------------------------------------------------------

    /**
     * @return Select[]
     */
    protected function getQuerySelect(): array
    {
        return [];
    }

    // --------------------------------------------------------------------------

    /**
     * @return Where[]|Condition[]
     */
    protected function getQueryConditions(CdnObject $oObject, array $aMappings = [], array $aWidgets = []): array
    {
        $aConditions = array_map(
            fn(string $sSlug) => $this->getJsonExtractPath($sSlug),
            $aWidgets
        );

        return [
            new Condition(implode(PHP_EOL . ' OR ', $aConditions)),
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * @return Sort[]
     */
    protected function getQuerySort(): array
    {
        return [];
    }

    // --------------------------------------------------------------------------

    /**
     * @throws FactoryException
     */
    protected function getWidgetMappings(): array
    {
        /** @var Cdn $oCdnMonitor */
        $oCdnMonitor = Factory::service('MonitorCdn', Constants::MODULE_SLUG);

        return $oCdnMonitor->getWidgetMappings();
    }

    // --------------------------------------------------------------------------

    protected function getJsonExtractPath(string $sSlug): string
    {
        //  $**: widgets may be nested in slots, see D17
        return $this->compileSlugCondition($this->getColumn(), '$**.slug', $sSlug);
    }

    // --------------------------------------------------------------------------

    /**
     * Compiles a condition matching rows whose JSON column holds the slug at the given path
     *
     * @throws FactoryException
     */
    protected function compileSlugCondition(string $sColumn, string $sJsonPath, string $sSlug): string
    {
        /** @var Database $oDb */
        $oDb = Factory::service('Database');

        return sprintf(
            'JSON_EXTRACT(`%s`, %s) LIKE %s ESCAPE %s',
            $sColumn,
            $oDb->escape($sJsonPath),
            $oDb->escape(Monitor::likePattern($sSlug)),
            $oDb->escape(Monitor::LIKE_ESCAPE)
        );
    }

    // --------------------------------------------------------------------------

    protected function extractWidgetData(Entity $oEntity): object|array
    {
        return json_decode($oEntity->{$this->getColumn()}) ?? [];
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

        //  Visit every instance, including those nested in slots; $sNodePath is e.g. "0.data.left.2"
        Registry::walk(
            $aWidgetData,
            function (object|array $oNode, string $sNodePath) use (&$aDetails, $aWidgets, $aMappings, $oObject, $oEntity): void {

                $aNode = Registry::toArray($oNode);
                $sSlug = $aNode['slug'];

                if (!in_array($sSlug, $aWidgets, true)) {
                    return;
                }

                $aPaths    = $aMappings[$sSlug] ?? [];
                $aDataFlat = is_array($aNode['data'] ?? null) ? $this->flattenOwnData($aNode['data']) : [];

                foreach ($aPaths as $sPath) {
                    foreach ($aDataFlat as $sResolvedPath => $mValue) {
                        if (preg_match('/' . str_replace('*', '\d+', $sPath) . '/', $sResolvedPath)) {

                            $iValue = (int) $mValue ?: null;

                            if ($iValue === $oObject->id) {
                                $aDetails[] = $this->createDetail(
                                    $oEntity,
                                    $this->getModel(),
                                    [
                                        'widget' => $sSlug,
                                        'path'   => $sNodePath . '.data.' . $sResolvedPath,
                                    ]
                                );
                            }
                        }
                    }
                }
            }
        );

        return $aDetails;
    }

    // --------------------------------------------------------------------------

    /**
     * Flattens a node's own data to dot paths, stopping at nested instances, which are visited on their own
     *
     * Without this a container's child would match twice: once under the container and once under itself.
     *
     * @return array<string, mixed>
     */
    private function flattenOwnData(array $aData, string $sPrefix = ''): array
    {
        $aOut = [];
        foreach ($aData as $sKey => $mValue) {

            $sPath = $sPrefix === '' ? (string) $sKey : $sPrefix . '.' . $sKey;

            if (Registry::isNode($mValue)) {
                continue;
            } elseif (is_array($mValue) && !empty($mValue)) {
                $aOut = array_merge($aOut, $this->flattenOwnData($mValue, $sPath));
            } else {
                $aOut[$sPath] = $mValue;
            }
        }

        return $aOut;
    }

    // --------------------------------------------------------------------------

    /**
     * @throws CdnException
     * @throws FactoryException
     * @throws ModelException
     */
    public function delete(Detail $oDetail, CdnObject $oObject): void
    {
        $this->setObjectId($oDetail, null);
    }

    // --------------------------------------------------------------------------

    /**
     * @throws CdnException
     * @throws FactoryException
     * @throws ModelException
     */
    public function replace(Detail $oDetail, CdnObject $oObject, CdnObject $oReplacement): void
    {
        $this->setObjectId($oDetail, $oReplacement->id);
    }

    // --------------------------------------------------------------------------

    /**
     * @throws CdnException
     * @throws FactoryException
     * @throws ModelException
     */
    protected function setObjectId(Detail $oDetail, ?int $iReplacementId): void
    {
        $oModel = $this->getModel();

        if (!$oModel->isDestructiveDelete()) {
            $oModel->includeDeleted();
        }

        /** @var Entity $oEntity */
        $oEntity = $oModel
            ->getById($oDetail->getData()->id);

        if (!$oEntity) {
            throw new ModelException('Entity not found');
        }

        $aWidgetData = $this->extractWidgetData($oEntity);

        $this->setValueAtPath(
            $oDetail->getData()->path,
            $aWidgetData,
            $iReplacementId
        );

        $this->updateEntity(
            $oEntity,
            [
                $this->getColumn() => json_encode($aWidgetData),
            ]
        );
    }

    // --------------------------------------------------------------------------

    /**
     * @throws FactoryException
     * @throws ModelException
     */
    protected function updateEntity(Entity $oEntity, array $aData): void
    {
        $this
            ->getModel()
            ->update(
                $oEntity->id,
                $aData
            );
    }

    // --------------------------------------------------------------------------

    /**
     * @throws CdnException
     */
    protected function setValueAtPath(string $sPath, array|object &$aData, ?int $mValue): void
    {
        $aKeys = explode('.', $sPath);
        $iLast = count($aKeys) - 1;

        //  Walk by reference: assigning an array to a variable copies it, and the write would be lost
        $mCurrentValue = &$aData;

        for ($i = 0; $i < $iLast; $i++) {

            if (is_array($mCurrentValue)) {

                if (!isset($mCurrentValue[$aKeys[$i]])) {
                    $mCurrentValue[$aKeys[$i]] = [];
                }
                $mCurrentValue = &$mCurrentValue[$aKeys[$i]];

            } elseif (is_object($mCurrentValue) && isset($mCurrentValue->{$aKeys[$i]})) {
                $mCurrentValue = &$mCurrentValue->{$aKeys[$i]};

            } else {
                throw new CdnException('Unable to set value at path: ' . $sPath);
            }
        }

        if (is_array($mCurrentValue)) {
            //  Set as string as that is how values from the UI will be set
            $mCurrentValue[$aKeys[$iLast]] = (string) $mValue;

        } elseif (is_object($mCurrentValue)) {
            //  Set as string as that is how values from the UI will be set
            $mCurrentValue->{$aKeys[$iLast]} = (string) $mValue;

        } else {
            throw new CdnException('Unable to set value at path: ' . $sPath);
        }
    }
}
