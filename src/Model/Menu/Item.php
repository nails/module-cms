<?php

/**
 * This model handle CMS Menu Items
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Model\Menu;

use Nails\Cms\Constants;
use Nails\Common\Exception\ModelException;
use Nails\Common\Model\Base;
use Nails\Common\Traits\Model\Nestable;

/**
 * Class Item
 *
 * @package Nails\Cms\Model\Menu
 */
class Item extends Base
{
    use Nestable;

    // --------------------------------------------------------------------------

    const TABLE                     = NAILS_DB_PREFIX . 'cms_menu_item';
    const RESOURCE_NAME             = 'MenuItem';
    const RESOURCE_PROVIDER         = Constants::MODULE_SLUG;
    const DEFAULT_SORT_COLUMN       = 'order';
    const SORTABLE_SAVE_BREADCRUMBS = false;
    const SORTABLE_SAVE_ORDER       = false;

    // --------------------------------------------------------------------------

    /**
     * Item constructor.
     *
     * @throws ModelException
     */
    public function __construct()
    {
        parent::__construct();
        $this
            ->hasOne('menu', 'Menu', Constants::MODULE_SLUG)
            ->hasOne('parent', 'MenuItem', Constants::MODULE_SLUG)
            ->hasOne('page', 'Page', Constants::MODULE_SLUG)
            ->hasMany('children', 'MenuItem', 'parent_id', Constants::MODULE_SLUG, ['expand' => ['children']]);
    }
}
