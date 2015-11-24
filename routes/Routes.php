<?php

/**
 * Generates CMS routes
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Routes\Cms;

use Nails\Factory;

class Routes
{
    /**
     * Returns an array of routes for this module
     * @return array
     */
    public function getRoutes()
    {
        $oPageModel = Factory::model('Page', 'nailsapp/module-cms');
        $oDb        = Factory::service('Database');

        $aRoutes = array();
        $aPages = $oPageModel->getAll();

        foreach ($aPages as $oPage) {

            if ($oPage->is_published) {

                $aRoutes[$oPage->published->slug] = 'cms/render/page/' . $oPage->id;
            }
        }

        // --------------------------------------------------------------------------

        /**
         *  Make a route for each slug history item, don't overwrite any existing route
         *  Doing them second and checking (instead of letting the real pages overwrite
         *  the key) in an attempt to optimise, the router takes the first route it comes
         *  across so, the logic is that the "current" slug is the one which is getting
         *  hit most often, so place it first, if a legacy slug is used (in theory less
         *  often) then the router can work a little harder.
         **/

        $oDb->select('sh.slug,sh.page_id');
        $oDb->join(NAILS_DB_PREFIX . 'cms_page p', 'p.id = sh.page_id');
        $oDb->where('p.is_deleted', false);
        $oDb->where('p.is_published', true);
        $aSlugs = $oDb->get(NAILS_DB_PREFIX . 'cms_page_slug_history sh')->result();

        foreach ($aSlugs as $oRoute) {

            if (!isset($aRoutes[$oRoute->slug])) {

                $aRoutes[$oRoute->slug] = 'cms/render/legacy_slug/' . $oRoute->page_id;
            }
        }

        return $aRoutes;
    }
}
