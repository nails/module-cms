<?php

/**
 * Migration: 10
 * Started:   29/04/2021
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Database Migration
 * @author      Nails Dev Team
 */

namespace Nails\Cms\Database\Migration;

use Nails\Common\Console\Migrate\Base;

/**
 * Class Migration10
 *
 * @package Nails\Cms\Database\Migration
 */
class Migration10 extends Base
{
    /**
     * Execute the migration
     *
     * @return Void
     */
    public function execute()
    {
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page` CHANGE `published_template_data` `published_template_data` JSON NULL;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page` CHANGE `published_template_options` `published_template_options` JSON NULL;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page` CHANGE `published_breadcrumbs` `published_breadcrumbs` JSON NULL;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page` CHANGE `draft_template_data` `draft_template_data` JSON NULL;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page` CHANGE `draft_template_options` `draft_template_options` JSON NULL;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page` CHANGE `draft_breadcrumbs` `draft_breadcrumbs` JSON NULL;');

        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page_preview` CHANGE `published_template_data` `published_template_data` JSON NULL;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page_preview` CHANGE `published_template_options` `published_template_options` JSON NULL;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page_preview` CHANGE `published_breadcrumbs` `published_breadcrumbs` JSON NULL;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page_preview` CHANGE `draft_template_data` `draft_template_data` JSON NULL;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page_preview` CHANGE `draft_template_options` `draft_template_options` JSON NULL;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page_preview` CHANGE `draft_breadcrumbs` `draft_breadcrumbs` JSON NULL;');

        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}cms_area` CHANGE `widget_data` `widget_data` JSON NULL;');
    }
}
