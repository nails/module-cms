<?php

namespace Nails\Cms\Cdn\Monitor;

use Nails\Cdn\Cdn\Monitor\ObjectIsInColumn;
use Nails\Cdn\Factory\Monitor\Detail;
use Nails\Cdn\Interfaces\Monitor;
use Nails\Cdn\Resource\CdnObject;
use Nails\Cms\Constants;
use Nails\Cms\Service\Monitor\Cdn;
use Nails\Common\Helper\ArrayHelper;
use Nails\Common\Helper\Model\Where;
use Nails\Common\Model\Base;
use Nails\Common\Resource\Entity;
use Nails\Common\Service\Database;
use Nails\Factory;

abstract class ObjectIsInWidgetData extends ObjectIsInColumn
{
    public function locate(CdnObject $oObject): array
    {
        /** @var Cdn $oCdnMonitor */
        $oCdnMonitor = Factory::service('MonitorCdn', Constants::MODULE_SLUG);
        /** @var Database $oDb */
        $oDb = Factory::service('Database');

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

        $aResults = $oDb
            ->where(implode(PHP_EOL . ' OR ', $aConditions))
            ->get($this->getModel()->getTableName())
            ->result();

        $aDetails = [];
        foreach ($aResults as $oRow) {

            //  Only return rows where the object is actually used
            $aWidgetData = json_decode($oRow->{$this->getColumn()});

            foreach ($aWidgetData as $iIndex => $oWidget) {

                if (!in_array($oWidget->slug, $aWidgets)) {
                    continue;
                }

                $aPaths = $aMappings[$oWidget->slug] ?? [];
                foreach ($aPaths as $sPath) {

                    //  @todo (Pablo 2023-08-14) - handle nested arrays, using * to represent iterables

                    $aDataFlat = ArrayHelper::arrayFlattenWithDotNotation($oWidget->data);

                    foreach ($aDataFlat as $sResolvedPath => $mValue) {
                        if (preg_match('/' . str_replace('*', '\d+', $sPath) . '/', $sResolvedPath)) {

                            $iValue = (int) $mValue ?: null;

                            if ($iValue === $oObject->id) {
                                /** @var Detail $oDetail */
                                $oDetail = Factory::factory('MonitorDetail', \Nails\Cdn\Constants::MODULE_SLUG, $this);
                                $oDetail->setData((object) [
                                    'id'       => $oRow->id,
                                    /**
                                     * Label isn't necessary, but helps humans
                                     * understand what the ID is referring to
                                     */
                                    'label'    => $oRow->label ?? '<no label>',
                                    'widget'   => $oWidget->slug,
                                    'path'     => $sResolvedPath,
                                    'position' => $iIndex + 1,
                                ]);
                                $aDetails[] = $oDetail;
                            }

                        }
                    }
                }
            }
        }

        return $aDetails;
    }

    // --------------------------------------------------------------------------

    public function delete(Detail $oDetail): void
    {
        dd(__METHOD__, $oDetail);
    }

    // --------------------------------------------------------------------------

    public function replace(CdnObject $oObject, Detail $oDetail): void
    {
        dd(__METHOD__, $oObject, $oDetail);
    }
}
