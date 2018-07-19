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
        $this->table = NAILS_DB_PREFIX . 'cms_block';
        $this->tableAlias = 'b';
    }

    // --------------------------------------------------------------------------

    /**
     * This method applies the conditionals which are common across the get_*()
     * methods and the count() method.
     * @param array  $data Data passed from the calling method
     * @return void
     **/
    protected function getCountCommon(array $data = array())
    {
        if (!empty($data['keywords'])) {

            if (empty($data['or_like'])) {

                $data['or_like'] = array();
            }

            $data['or_like'][] = array(
                'column' => $this->tableAlias . '.label',
                'value'  => $data['keywords']
            );
            $data['or_like'][] = array(
                'column' => $this->tableAlias . '.value',
                'value'  => $data['keywords']
            );
            $data['or_like'][] = array(
                'column' => $this->tableAlias . '.located',
                'value'  => $data['keywords']
            );
            $data['or_like'][] = array(
                'column' => $this->tableAlias . '.description',
                'value'  => $data['keywords']
            );
        }

        parent::getCountCommon($data);
    }
}
