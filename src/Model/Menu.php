<?php

/**
 * This model handle CMS Menus
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Model;

use Nails\Cms\Constants;
use Nails\Common\Exception\ModelException;
use Nails\Common\Model\Base;

/**
 * Class Menu
 *
 * @package Nails\Cms\Model
 */
class Menu extends Base
{
    const TABLE             = NAILS_DB_PREFIX . 'cms_menu';
    const RESOURCE_NAME     = 'Menu';
    const RESOURCE_PROVIDER = Constants::MODULE_SLUG;
    const AUTO_SET_SLUG     = true;

    // --------------------------------------------------------------------------

    /**
     * Menu constructor.
     *
     * @throws ModelException
     */
    public function __construct()
    {
        parent::__construct();
        $this
            ->hasMany('items', 'MenuItem', 'menu_id', Constants::MODULE_SLUG, [
                'expand' => ['children'],
                'where'  => [
                    ['parent_id', null],
                ],
            ]);
    }
}
