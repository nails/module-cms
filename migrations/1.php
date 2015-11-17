<?php

/**
 * Migration:   1
 * Started:     06/11/2015
 * Finalised:   06/11/2015
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Database Migration
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Database\Migration\Nailsapp\ModuleCms;

use Nails\Common\Console\Migrate\Base;

class Migration1 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}cms_block` ADD `value` TEXT  NULL  AFTER `located`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}cms_block` CHANGE `title` `label` VARCHAR(150)  CHARACTER SET utf8  COLLATE utf8_general_ci  NOT NULL  DEFAULT '';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}cms_block` MODIFY COLUMN `created_by` INT(11) UNSIGNED DEFAULT NULL AFTER `created`;");
        $this->query("UPDATE `{{NAILS_DB_PREFIX}}cms_block` cb SET `value` = (SELECT `cbt`.`value` FROM `nails_cms_block_translation` cbt WHERE `cbt`.`block_id` = `cb`.`id` AND `cbt`.`language` = 'english');");
        $this->query("DROP TABLE `{{NAILS_DB_PREFIX}}cms_block_translation_revision`;");
        $this->query("DROP TABLE `{{NAILS_DB_PREFIX}}cms_block_translation`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}cms_slider_item` CHANGE `object_id` `object_id` INT(11)  UNSIGNED  NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}cms_slider_item` DROP FOREIGN KEY `{{NAILS_DB_PREFIX}}cms_slider_item_ibfk_7`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}cms_slider_item` DROP `page_id`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}cms_slider_item` DROP `title`;");
    }
}
