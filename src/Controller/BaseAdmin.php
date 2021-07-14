<?php

/**
 * This class provides some common CMS controller functionality in admin
 *
 * @package    Nails
 * @subpackage module-cms
 * @category   Controller
 * @author     Nails Dev Team
 */

namespace Nails\Cms\Controller;

use Nails\Admin\Controller\Base;
use Nails\Cms\Constants;
use Nails\Factory;

abstract class BaseAdmin extends Base
{
    public function __construct()
    {
        parent::__construct();
        $oAsset = Factory::service('Asset');
        $oAsset->load('admin.min.css', Constants::MODULE_SLUG);
    }
}
