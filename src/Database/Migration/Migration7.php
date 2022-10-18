<?php

/**
 * Migration: 7
 * Started:   01/07/2020
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Database Migration
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Database\Migration;

use Nails\Common\Console\Migrate\Base;

/**
 * Class Migration7
 *
 * @package Nails\Cms\Database\Migration
 */
class Migration7 extends Base
{
    /**
     * Execute the migration
     *
     * @return Void
     */
    public function execute()
    {
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page` ADD `draft_seo_image_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `draft_seo_keywords`;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page` ADD `published_seo_image_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `published_seo_keywords`;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page` ADD FOREIGN KEY (`draft_seo_image_id`) REFERENCES `{{NAILS_DB_PREFIX}}cdn_object` (`id`) ON DELETE SET NULL;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page` ADD FOREIGN KEY (`published_seo_image_id`) REFERENCES `{{NAILS_DB_PREFIX}}cdn_object` (`id`) ON DELETE SET NULL;');

        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page_preview` ADD `draft_seo_image_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `draft_seo_keywords`;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page_preview` ADD `published_seo_image_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `published_seo_keywords`;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page_preview` ADD FOREIGN KEY (`draft_seo_image_id`) REFERENCES `{{NAILS_DB_PREFIX}}cdn_object` (`id`) ON DELETE SET NULL;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page_preview` ADD FOREIGN KEY (`published_seo_image_id`) REFERENCES `{{NAILS_DB_PREFIX}}cdn_object` (`id`) ON DELETE SET NULL;');
    }
}
