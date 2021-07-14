<?php

namespace Nails\Cms\Traits\Monitor;

use Nails\Cms\Constants;
use Nails\Cms\Interfaces;
use Nails\Cms\Factory\Monitor\Detail;
use Nails\Factory;
use Nails\Common\Exception\FactoryException;

/**
 * Trait Widget
 *
 * @package Nails\Cms\Traits\Monitor
 */
trait Widget
{
    /**
     * Returns the name of the table to inspect
     *
     * @return string
     */
    abstract protected function getTableName(): string;

    // --------------------------------------------------------------------------

    /**
     * Returns the columns which contain widget data
     *
     * @return string[]
     */
    abstract protected function getDataColumns(): array;

    // --------------------------------------------------------------------------

    /**
     * Returns which columns to use in the SELECT portion of the usage query
     * (row is passed to compileUsage())
     *
     * @return array
     */
    abstract protected function getQueryColumns(): array;

    // --------------------------------------------------------------------------

    /**
     * Returns the path component of the JSOM_EXTRACT function
     *
     * @return string
     */
    protected function getJsonPath(): string
    {
        return '$[*].slug';
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a Usage object for each row which contains an instance of the widget
     *
     * @param \stdClass $oRow The database row, properties defined by getQueryColumns()
     *
     * @return Detail\Usage
     */
    abstract protected function compileUsage(\stdClass $oRow): Detail\Usage;

    // --------------------------------------------------------------------------

    /**
     * Counts the number of rows which contain a particular widget
     *
     * @param Interfaces\Widget $oWidget
     *
     * @return int
     * @throws FactoryException
     */
    public function countUsages(Interfaces\Widget $oWidget): int
    {
        /** @var \Nails\Common\Service\Database $oDb */
        $oDb = Factory::service('Database');
        $this->compileQuery($oWidget);
        return $oDb->count_all_results();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of usage objects for a given widget
     *
     * @param Interfaces\Widget $oWidget
     *
     * @return Detail\Usage[]
     * @throws FactoryException
     */
    public function getUsages(Interfaces\Widget $oWidget): array
    {
        /** @var \Nails\Common\Service\Database $oDb */
        $oDb = Factory::service('Database');
        $oDb->select($this->getQueryColumns());
        $this->compileQuery($oWidget);

        return array_map(function (\stdClass $oRow) {

            return $this->compileUsage($oRow);

        }, $oDb->get()->result());
    }

    // --------------------------------------------------------------------------

    /**
     * Compiles the database query
     *
     * @param Interfaces\Widget $oWidget
     *
     * @throws FactoryException
     */
    private function compileQuery(Interfaces\Widget $oWidget): void
    {
        /** @var \Nails\Common\Service\Database $oDb */
        $oDb = Factory::service('Database');

        $oDb->from($this->getTableName());

        $aSql = [];
        foreach ($this->getDataColumns() as $sColumn) {
            $aSql[] = sprintf(
                'JSON_CONTAINS(JSON_EXTRACT(%s, "%s"), \'"%s"\', \'$\')',
                $sColumn,
                $this->getJsonPath(),
                $oWidget->getSlug()
            );
        }

        $oDb->where(sprintf(
            '(%s)',
            implode(' OR ', $aSql)
        ));
    }
}
