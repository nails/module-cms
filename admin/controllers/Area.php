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

namespace Nails\Admin\Cms;

use Nails\Common\Exception\NailsException;
use Nails\Admin\Controller\DefaultController;
use Nails\Cms\Constants;

/**
 * Class Area
 *
 * @package Nails\Admin\Cms
 */
class Area extends DefaultController
{
    const CONFIG_MODEL_NAME     = 'Area';
    const CONFIG_MODEL_PROVIDER = Constants::MODULE_SLUG;
    const CONFIG_PERMISSION     = 'cms:area';
    const CONFIG_SIDEBAR_GROUP  = 'CMS';
    const CONFIG_SIDEBAR_ICON   = 'fa-file-alt';

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
