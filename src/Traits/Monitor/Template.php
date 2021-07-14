<?php

namespace Nails\Cms\Traits\Monitor;

use Nails\Cms\Constants;
use Nails\Cms\Factory\Monitor\Detail;
use Nails\Cms\Interfaces;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Service\Database;
use Nails\Factory;

/**
 * Trait Template
 *
 * @package Nails\Cms\Traits\Monitor
 */
trait Template
{
    /**
     * Returns the name of the table to inspect
     *
     * @return string
     */
    abstract protected function getTableName(): string;

    // --------------------------------------------------------------------------

    /**
     * Returns the columns which contain template slug
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
     * Returns a Usage object for each row which contains an instance of the template
     *
     * @param \stdClass $oRow The database row, properties defined by getQueryColumns()
     *
     * @return Detail\Usage
     */
    abstract protected function compileUsage(\stdClass $oRow): Detail\Usage;

    // --------------------------------------------------------------------------

    /**
     * Counts the number of rows which contain a particular template
     *
     * @param Interfaces\Template $oTemplate
     *
     * @return int
     * @throws FactoryException
     */
    public function countUsages(Interfaces\Template $oTemplate): int
    {
        /** @var Database $oDb */
        $oDb = Factory::service('Database');
        $this->compileQuery($oTemplate);
        return $oDb->count_all_results();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of usage objects for a given template
     *
     * @param Interfaces\Template $oTemplate
     *
     * @return Detail\Usage[]
     * @throws FactoryException
     */
    public function getUsages(Interfaces\Template $oTemplate): array
    {
        /** @var Database $oDb */
        $oDb = Factory::service('Database');
        $oDb->select($this->getQueryColumns());
        $this->compileQuery($oTemplate);

        return array_map(function (\stdClass $oRow) {

            return $this->compileUsage($oRow);

        }, $oDb->get()->result());
    }

    // --------------------------------------------------------------------------

    /**
     * Compiles the database query
     *
     * @param Interfaces\Template $oTemplate
     *
     * @throws FactoryException
     */
    private function compileQuery(Interfaces\Template $oTemplate): void
    {
        /** @var Database $oDb */
        $oDb = Factory::service('Database');

        $oDb->from($this->getTableName());

        foreach ($this->getDataColumns() as $sColumn) {
            $oDb->or_where($sColumn, $oTemplate->getSlug());
        }
    }
}
