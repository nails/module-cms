<?php

namespace Nails\Cms\Cdn\Monitor;

use Nails\Cdn\Cdn\Monitor\ObjectIsInColumn;
use Nails\Cdn\Exception\CdnException;
use Nails\Cdn\Factory\Monitor\Detail;
use Nails\Cdn\Resource\CdnObject;
use Nails\Cms\Constants;
use Nails\Cms\Service\Monitor\Cdn;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Helper\ArrayHelper;
use Nails\Common\Helper\Model\Condition;
use Nails\Common\Resource\Entity;
use Nails\Factory;

abstract class ObjectIsInWidgetData extends ObjectIsInColumn
{
    /**
     * @return Detail[]
     * @throws FactoryException
     * @throws ModelException
     */
    public function locate(CdnObject $oObject): array
    {
        $aMappings = $this->getWidgetMappings();
        if (empty($aMappings)) {
            return [];
        }

        $aWidgets = array_keys($aMappings);

        $aConditions = array_map(
            fn(string $sSlug) => $this->getJsonExtractPath($sSlug),
            $aWidgets
        );

        /** @var Entity[] $aResults */
        $aResults = $this
            ->getModel()
            ->getAll([
                new Condition(implode(PHP_EOL . ' OR ', $aConditions)),
            ]);

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
                    $aDetails
                )
            );
        }

        return $aDetails;
    }

    // --------------------------------------------------------------------------

    protected function getWidgetMappings(): array
    {
        /** @var Cdn $oCdnMonitor */
        $oCdnMonitor = Factory::service('MonitorCdn', Constants::MODULE_SLUG);

        return $oCdnMonitor->getWidgetMappings();
    }

    // --------------------------------------------------------------------------

    protected function getJsonExtractPath(string $sSlug): string
    {
        return sprintf(
            'JSON_EXTRACT(`%s`, \'$[*].slug\') LIKE \'%%"%s"%%\'',
            $this->getColumn(),
            $sSlug
        );
    }

    // --------------------------------------------------------------------------

    protected function extractWidgetData(Entity $oEntity): object|array
    {
        return json_decode($oEntity->{$this->getColumn()}) ?? [];
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
        foreach ($aWidgetData as $iIndex => $oWidget) {

            if (!in_array($oWidget->slug, $aWidgets)) {
                continue;
            }

            $aPaths = $aMappings[$oWidget->slug] ?? [];

            foreach ($aPaths as $sPath) {

                $aDataFlat = ArrayHelper::arrayFlattenWithDotNotation($oWidget->data);

                foreach ($aDataFlat as $sResolvedPath => $mValue) {
                    if (preg_match('/' . str_replace('*', '\d+', $sPath) . '/', $sResolvedPath)) {

                        $iValue = (int) $mValue ?: null;

                        if ($iValue === $oObject->id) {
                            $aDetails[] = $this->createDetail(
                                $oEntity,
                                [
                                    'widget' => $oWidget->slug,
                                    'path'   => $iIndex . '.data.' . $sResolvedPath,
                                ]
                            );
                        }
                    }
                }
            }
        }

        return $aDetails;
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
    protected function setObjectId(Detail $oDetail, ?int $iObjectId): void
    {
        $oEntity = $this
            ->getModel()
            ->getById($oDetail->getData()->id);

        $aWidgetData = $this->extractWidgetData($oEntity);

        $this->setValueAtPath(
            $oDetail->getData()->path,
            $aWidgetData,
            $iObjectId
        );

        $this
            ->getModel()
            ->update(
                $oEntity->id,
                [
                    $this->getColumn() => json_encode($aWidgetData),
                ]
            );
    }

    // --------------------------------------------------------------------------

    /**
     * @throws CdnException
     */
    protected function setValueAtPath(string $sPath, $aData, ?int $mValue): void
    {
        $aKeys         = explode('.', $sPath);
        $mCurrentValue = $aData;

        for ($i = 0; $i < count($aKeys) - 1; $i++) {

            if (is_array($mCurrentValue)) {

                if (!isset($mCurrentValue[$aKeys[$i]])) {
                    $mCurrentValue[$aKeys[$i]] = [];
                }
                $mCurrentValue = $mCurrentValue[$aKeys[$i]];

            } elseif (is_object($mCurrentValue)) {
                $mCurrentValue = $mCurrentValue->{$aKeys[$i]};

            } else {
                throw new CdnException('Unable to set value at path: ' . $sPath);
            }
        }

        if (is_array($mCurrentValue)) {
            //  Set as string as that is how values from the UI will be set
            $mCurrentValue[$aKeys[count($aKeys) - 1]] = (string) $mValue;

        } elseif (is_object($mCurrentValue)) {
            //  Set as string as that is how values from the UI will be set
            $mCurrentValue->{$aKeys[count($aKeys) - 1]} = (string) $mValue;

        } else {
            throw new CdnException('Unable to set value at path: ' . $sPath);
        }
    }
}
