<?php

/**
 * Migration:   3
 * Started:     06/11/2015
 * Finalised:   06/11/2015
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
 * Class Migration3
 *
 * @package Nails\Database\Migration\Nails\ModuleCms
 */
class Migration3 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}cms_area` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `slug` varchar(150) DEFAULT NULL,
                `label` varchar(150) DEFAULT NULL,
                `description` text, `widget_data` text,
                `created` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified` datetime NOT NULL,
                `modified_by` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`), KEY `created_by` (`created_by`),
                KEY `modified_by` (`modified_by`), CONSTRAINT `{{NAILS_DB_PREFIX}}cms_area_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_area_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}
