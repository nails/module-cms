<?php

/**
 * Admin API end points: Pages
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Api\Cms;

use Nails\Factory;

class Widgets extends \Nails\Api\Controller\Base
{
    public static $requiresAuthentication = true;

    // --------------------------------------------------------------------------

    public function getIndex()
    {
        if (userHasPermission('admin:cms:pages:*') || userHasPermission('admin:cms:area:*')) {

            $oWidgetModel  = Factory::model('Widget', 'nailsapp/module-cms');
            $aOut          = array();
            $aWidgetGroups = $oWidgetModel->getAvailable();

            foreach ($aWidgetGroups as $oWidgetGroup) {
                $aOut[] = json_decode($oWidgetGroup->toJson());
            }
            return array('widgets' => $aOut);

        } else {

            return array(
                'status' => 401,
                'error'  => 'You do not have permission to view widgets.'
            );
        }
    }
}