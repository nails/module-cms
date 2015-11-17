<?php

/**
 * Migration:   0
 * Started:     09/01/2015
 * Finalised:   09/01/2015
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Database Migration
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Database\Migration\Nailsapp\ModuleCms;

use Nails\Common\Console\Migrate\Base;

class Migration0 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        # INITIAL ADMIN DB
        # This is the schema of the CMS module database as of 09/01/2015
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}cms_block` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `type` enum('plaintext','richtext','image','file','number','url') NOT NULL DEFAULT 'plaintext',
                `slug` varchar(50) NOT NULL DEFAULT '',
                `title` varchar(150) NOT NULL DEFAULT '',
                `description` varchar(500) NOT NULL DEFAULT '',
                `located` varchar(500) NOT NULL DEFAULT '',
                `created` datetime NOT NULL,
                `modified` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified_by` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `created_by` (`created_by`),
                KEY `slug` (`slug`),
                KEY `modified_by` (`modified_by`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_block_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_block_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}cms_block_translation` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `block_id` int(11) unsigned NOT NULL,
                `language` varchar(20) NOT NULL DEFAULT '',
                `value` text NOT NULL,
                `created` datetime NOT NULL,
                `modified` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified_by` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `block_id` (`block_id`),
                KEY `created_by` (`created_by`),
                KEY `modified_by` (`modified_by`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_block_translation_ibfk_1` FOREIGN KEY (`block_id`) REFERENCES `{{NAILS_DB_PREFIX}}cms_block` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_block_translation_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_block_translation_ibfk_4` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}cms_block_translation_revision` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `block_translation_id` int(11) unsigned NOT NULL,
                `value` text NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `created` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `created_by` (`created_by`),
                KEY `block_translation_id` (`block_translation_id`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_block_translation_revision_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_block_translation_revision_ibfk_3` FOREIGN KEY (`block_translation_id`) REFERENCES `{{NAILS_DB_PREFIX}}cms_block_translation` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}cms_menu` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `slug` varchar(150) DEFAULT NULL,
                `label` varchar(150) DEFAULT NULL,
                `description` text,
                `created` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified` datetime NOT NULL,
                `modified_by` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `created_by` (`created_by`),
                KEY `modified_by` (`modified_by`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_menu_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_menu_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}cms_menu_item` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `menu_id` int(11) unsigned NOT NULL,
                `parent_id` int(11) unsigned DEFAULT NULL,
                `order` tinyint(1) unsigned NOT NULL,
                `page_id` int(11) unsigned DEFAULT NULL,
                `url` varchar(255) DEFAULT NULL,
                `label` varchar(150) DEFAULT NULL,
                `created` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified` datetime NOT NULL,
                `modified_by` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `menu_id` (`menu_id`),
                KEY `parent_id` (`parent_id`),
                KEY `page_id` (`page_id`),
                KEY `created_by` (`created_by`),
                KEY `modified_by` (`modified_by`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_menu_item_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `{{NAILS_DB_PREFIX}}cms_menu` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_menu_item_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `{{NAILS_DB_PREFIX}}cms_menu_item` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_menu_item_ibfk_3` FOREIGN KEY (`page_id`) REFERENCES `{{NAILS_DB_PREFIX}}cms_page` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_menu_item_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_menu_item_ibfk_5` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}cms_page` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `published_hash` char(32) DEFAULT NULL,
                `published_slug` varchar(500) DEFAULT NULL,
                `published_slug_end` varchar(150) DEFAULT NULL,
                `published_parent_id` int(11) unsigned DEFAULT NULL,
                `published_template` varchar(50) DEFAULT NULL,
                `published_template_data` longtext,
                `published_title` varchar(255) DEFAULT NULL,
                `published_breadcrumbs` text,
                `published_seo_title` varchar(150) DEFAULT NULL,
                `published_seo_description` varchar(300) DEFAULT NULL,
                `published_seo_keywords` varchar(150) DEFAULT NULL,
                `draft_hash` char(32) DEFAULT NULL,
                `draft_slug` varchar(500) DEFAULT NULL,
                `draft_slug_end` varchar(150) DEFAULT NULL,
                `draft_parent_id` int(11) unsigned DEFAULT NULL,
                `draft_template` varchar(50) DEFAULT NULL,
                `draft_template_data` longtext,
                `draft_title` varchar(255) DEFAULT NULL,
                `draft_breadcrumbs` text,
                `draft_seo_title` varchar(150) DEFAULT NULL,
                `draft_seo_description` varchar(300) DEFAULT NULL,
                `draft_seo_keywords` varchar(150) DEFAULT NULL,
                `is_published` tinyint(1) unsigned NOT NULL DEFAULT '0',
                `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
                `is_homepage` tinyint(1) unsigned NOT NULL DEFAULT '0',
                `created` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified` datetime NOT NULL,
                `modified_by` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `created_by` (`created_by`),
                KEY `modified_by` (`modified_by`),
                KEY `parent_id` (`published_parent_id`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_page_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_page_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_page_ibfk_3` FOREIGN KEY (`published_parent_id`) REFERENCES `{{NAILS_DB_PREFIX}}cms_page` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}cms_page_preview` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `published_hash` char(32) DEFAULT NULL,
                `published_slug` varchar(500) DEFAULT NULL,
                `published_slug_end` varchar(150) DEFAULT NULL,
                `published_parent_id` int(11) unsigned DEFAULT NULL,
                `published_template` varchar(50) DEFAULT NULL,
                `published_template_data` longtext,
                `published_title` varchar(255) DEFAULT NULL,
                `published_breadcrumbs` text,
                `published_seo_title` varchar(150) DEFAULT NULL,
                `published_seo_description` varchar(300) DEFAULT NULL,
                `published_seo_keywords` varchar(150) DEFAULT NULL,
                `draft_hash` char(32) DEFAULT NULL,
                `draft_slug` varchar(500) DEFAULT NULL,
                `draft_slug_end` varchar(150) DEFAULT NULL,
                `draft_parent_id` int(11) unsigned DEFAULT NULL,
                `draft_template` varchar(50) DEFAULT NULL,
                `draft_template_data` longtext,
                `draft_title` varchar(255) DEFAULT NULL,
                `draft_breadcrumbs` text,
                `draft_seo_title` varchar(150) DEFAULT NULL,
                `draft_seo_description` varchar(300) DEFAULT NULL,
                `draft_seo_keywords` varchar(150) DEFAULT NULL,
                `is_published` tinyint(1) unsigned NOT NULL DEFAULT '0',
                `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
                `is_homepage` tinyint(1) unsigned NOT NULL DEFAULT '0',
                `created` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified` datetime NOT NULL,
                `modified_by` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `created_by` (`created_by`),
                KEY `modified_by` (`modified_by`),
                KEY `parent_id` (`published_parent_id`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_page_preview_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_page_preview_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_page_preview_ibfk_3` FOREIGN KEY (`published_parent_id`) REFERENCES `{{NAILS_DB_PREFIX}}cms_page` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}cms_page_slug_history` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `hash` char(32) NOT NULL DEFAULT '',
                `slug` varchar(500) NOT NULL DEFAULT '',
                `page_id` int(11) unsigned NOT NULL,
                `created` datetime DEFAULT NULL,
                PRIMARY KEY (`id`), UNIQUE KEY `hash` (`hash`),
                KEY `page_id` (`page_id`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_page_slug_history_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `{{NAILS_DB_PREFIX}}cms_page` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}cms_slider` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `slug` varchar(150) DEFAULT NULL,
                `label` varchar(150) DEFAULT NULL,
                `description` text,
                `created` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified` datetime NOT NULL,
                `modified_by` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `created_by` (`created_by`),
                KEY `modified_by` (`modified_by`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_slider_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_slider_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}cms_slider_item` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `slider_id` int(11) unsigned NOT NULL,
                `object_id` int(11) unsigned NOT NULL,
                `page_id` int(11) unsigned DEFAULT NULL,
                `url` varchar(255) DEFAULT NULL,
                `title` varchar(150) DEFAULT NULL,
                `caption` varchar(150) DEFAULT NULL,
                `order` tinyint(1) unsigned NOT NULL,
                `created` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified` datetime NOT NULL,
                `modified_by` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `slider_id` (`slider_id`),
                KEY `created_by` (`created_by`),
                KEY `modified_by` (`modified_by`),
                KEY `object_id` (`object_id`),
                KEY `page_id` (`page_id`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_slider_item_ibfk_1` FOREIGN KEY (`slider_id`) REFERENCES `{{NAILS_DB_PREFIX}}cms_slider` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_slider_item_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_slider_item_ibfk_5` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_slider_item_ibfk_6` FOREIGN KEY (`object_id`) REFERENCES `{{NAILS_DB_PREFIX}}cdn_object` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_slider_item_ibfk_7` FOREIGN KEY (`page_id`) REFERENCES `{{NAILS_DB_PREFIX}}cms_page` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}
