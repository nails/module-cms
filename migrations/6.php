<?php

/**
 * Migration: 6
 * Started:   31/01/2020
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Database Migration
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Database\Migration\Nails\ModuleCms;

use Nails\Common\Console\Migrate\Base;

/**
 * Class Migration6
 *
 * @package Nails\Database\Migration\Nails\ModuleCms
 */
class Migration6 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_block` CHANGE `slug` `slug` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT "";');
    }
}
