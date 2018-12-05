<?php

/**
 * This model handle CMS Blocks
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Model;

use Nails\Common\Model\Base;

class Block extends Base
{
    /**
     * Model constructor
     **/
    public function __construct()
    {
        parent::__construct();
        $this->table            = NAILS_DB_PREFIX . 'cms_block';
        $this->searchableFields = ['label', 'value', 'located', 'description'];
    }
}
