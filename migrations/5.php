<?php

/**
 * Migration:   5
 * Started:     08/02/2016
 * Finalised:   08/02/2016
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Database Migration
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Database\Migration\Nails\ModuleCms;

use Nails\Cms\Constants;
use Nails\Common\Console\Migrate\Base;

class Migration5 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        //  Delete any existing setting
        $this->query("DELETE FROM `{{NAILS_DB_PREFIX}}app_setting` WHERE `grouping` = '" . Constants::MODULE_SLUG . "' AND `key` = 'homepage';");

        //  Add the new one, if there is one
        $oResult = $this->query('SELECT * FROM {{NAILS_DB_PREFIX}}cms_page WHERE is_homepage=1');
        while ($oRow = $oResult->fetch(\PDO::FETCH_OBJ)) {

            $this->query("INSERT INTO `{{NAILS_DB_PREFIX}}app_setting` (`grouping`, `key`, `value`, `is_encrypted`) VALUES ('" . Constants::MODULE_SLUG . "', 'homepage', " . $oRow->id . ", 0);");
            break;
        }

        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}cms_page` DROP `is_homepage`;");
    }
}
