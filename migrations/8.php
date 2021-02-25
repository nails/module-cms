<?php

/**
 * Migration: 8
 * Started:   24/02/2021
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Database Migration
 * @author      Nails Dev Team
 */

namespace Nails\Database\Migration\Nails\ModuleCms;

use Nails\Common\Console\Migrate\Base;

/**
 * Class Migration8
 *
 * @package Nails\Database\Migration\Nails\ModuleCms
 */
class Migration8 extends Base
{
    /**
     * Execute the migration
     *
     * @return Void
     */
    public function execute()
    {
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_menu_item` CHANGE `order` `order` INT(11) UNSIGNED NOT NULL;');
    }
}
