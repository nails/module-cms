<?php

/**
 * Migration:   2
 * Started:     06/11/2015
 * Finalised:   06/11/2015
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
 * Class Migration2
 *
 * @package Nails\Database\Migration\Nails\ModuleCms
 */
class Migration2 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}cms_block` CHANGE `type` `type` ENUM('plaintext','richtext','image','file','number','url', 'email')  CHARACTER SET utf8  COLLATE utf8_general_ci  NOT NULL  DEFAULT 'plaintext';");
    }
}
