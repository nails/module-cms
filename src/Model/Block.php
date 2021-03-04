<?php

/**
 * This model handle CMS Blocks
 *
 * @package    Nails
 * @subpackage module-cms
 * @category   Model
 * @author     Nails Dev Team
 */

namespace Nails\Cms\Model;

use Nails\Common\Model\Base;
use Nails\Config;

class Block extends Base
{
    const TABLE = NAILS_DB_PREFIX . 'cms_block';

    // --------------------------------------------------------------------------

    /**
     * Model constructor
     **/
    public function __construct()
    {
        parent::__construct();
        $this->searchableFields = ['label', 'value', 'located', 'description'];
    }
}
