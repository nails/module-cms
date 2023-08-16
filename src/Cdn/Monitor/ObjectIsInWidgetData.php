<?php

namespace Nails\Cms\Cdn\Monitor;

use Nails\Cdn\Cdn\Monitor\ObjectIsInColumn;
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
        /** @var Cdn $oCdnMonitor */
        $oCdnMonitor = Factory::service('MonitorCdn', Constants::MODULE_SLUG);

        $aMappings = $oCdnMonitor->getWidgetMappings();
        $aWidgets  = array_keys($aMappings);

        $aConditions = array_map(
            fn(string $sSlug) => sprintf(
                'JSON_EXTRACT(`%s`, \'$[*].slug\') LIKE \'%%"%s"%%\'',
                $this->getColumn(),
                $sSlug
            ),
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
            $aWidgetData = json_decode($oEntity->{$this->getColumn()});

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
                                        'widget'   => $oWidget->slug,
                                        'path'     => $sResolvedPath,
                                        'position' => $iIndex + 1,
                                    ]
                                );
                            }
                        }
                    }
                }
            }
        }

        return $aDetails;
    }

    // --------------------------------------------------------------------------

    public function delete(Detail $oDetail, CdnObject $oObject): void
    {
        dd(__FILE__, __LINE__);
    }

    // --------------------------------------------------------------------------

    public function replace(Detail $oDetail, CdnObject $oObject, CdnObject $oReplacement): void
    {
        dd(__FILE__, __LINE__);
    }
}
