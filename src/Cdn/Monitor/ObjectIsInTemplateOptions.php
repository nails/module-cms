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
use Nails\Factory;

abstract class ObjectIsInTemplateOptions extends ObjectIsInColumn
{
    const STATE_DRAFT     = 'draft';
    const STATE_PUBLISHED = 'published';

    // --------------------------------------------------------------------------

    abstract protected function getState(): string;

    // --------------------------------------------------------------------------

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
                    'JSON_EXTRACT(`%s_%s`, \'$.%s\') LIKE \'%%"%s"%%\'',
                    $this->getState(),
                    $this->getColumn(),
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

            $aOptions = $oPage->{$this->getState()}->template_options;

            foreach ($aMappings as $sTemplate => $aPaths) {
                foreach ($aPaths as $sPath) {
                    if ($oPage->{$this->getState()}->template === $sTemplate) {
                        if ($oObject->id === (int) ($aOptions->{$sPath} ?? null)) {
                            $aDetails[] = $this->createDetail($oPage, ['path' => $sPath]);
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
