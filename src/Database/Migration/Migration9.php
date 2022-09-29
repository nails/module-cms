<?php

/**
 * Migration: 9
 * Started:   04/03/2021
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Database Migration
 * @author      Nails Dev Team
 */

namespace Nails\Cms\Database\Migration;

use Nails\Common\Console\Migrate\Base;

/**
 * Class Migration9
 *
 * @package Nails\Cms\Database\Migration
 */
class Migration9 extends Base
{
    /**
     * Execute the migration
     *
     * @return Void
     */
    public function execute()
    {
        $this->query('DROP TABLE `{{NAILS_DB_PREFIX}}cms_slider_item`;');
        $this->query('DROP TABLE `{{NAILS_DB_PREFIX}}cms_slider`;');
    }
}
