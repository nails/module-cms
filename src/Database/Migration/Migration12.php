<?php

/**
 * Migration: 12
 *
 * Favourite widgets for the WidgetEditor. Repeatable because `feature/pre-new-admin`
 * has no equivalent migration, so an app arriving from that branch resumes above this
 * number and would never run it.
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Database Migration
 * @author      Nails Dev Team
 */

namespace Nails\Cms\Database\Migration;

use Nails\Common\Interfaces;
use Nails\Common\Traits;

class Migration12 implements Interfaces\Database\Migration\Repeatable
{
    use Traits\Database\Migration;

    // --------------------------------------------------------------------------

    public function execute(): void
    {
        if ($this->tableExists('{{NAILS_DB_PREFIX}}cms_widget_favourite')) {
            return;
        }

        $this->query('
            CREATE TABLE `{{NAILS_DB_PREFIX}}cms_widget_favourite` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `user_id` int(11) unsigned NOT NULL,
                `widget_slug` varchar(255) NOT NULL,
                `sort_order` int(11) unsigned NOT NULL DEFAULT 0,
                `created` datetime NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `user_widget` (`user_id`, `widget_slug`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}cms_widget_favourite_user_fk`
                    FOREIGN KEY (`user_id`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ');
    }
}
