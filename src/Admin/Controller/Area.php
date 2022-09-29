<?php

/**
 * This class provides CMS Area management functionality
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Admin\Controller;

use Nails\Common\Exception\NailsException;
use Nails\Admin\Controller\DefaultController;
use Nails\Cms\Admin\Permission;
use Nails\Cms\Constants;

/**
 * Class Area
 *
 * @package Nails\Admin\Cms
 */
class Area extends DefaultController
{
    const CONFIG_MODEL_NAME        = 'Area';
    const CONFIG_MODEL_PROVIDER    = Constants::MODULE_SLUG;
    const CONFIG_SIDEBAR_GROUP     = 'CMS';
    const CONFIG_SIDEBAR_ICON      = 'fa-file-alt';
    const CONFIG_PERMISSION_CREATE = Permission\Area\Create::class;
    const CONFIG_PERMISSION_EDIT   = Permission\Area\Edit::class;
    const CONFIG_PERMISSION_BROWSE = Permission\Area\Browse::class;
    const CONFIG_PERMISSION_DELETE = Permission\Area\Delete::class;

    // --------------------------------------------------------------------------

    /**
     * Area constructor.
     *
     * @throws NailsException
     */
    public function __construct()
    {
        parent::__construct();

        $this->aConfig['INDEX_FIELDS']['Label'] = function (\Nails\Cms\Resource\Area $oArea) {
            return sprintf(
                '%s (<code>%s</code>)<small>%s</small>',
                $oArea->label,
                $oArea->slug,
                $oArea->description
            );
        };
    }
}
