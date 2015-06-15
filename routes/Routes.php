<?php

namespace Nails\Routes\Cms;

/**
 * Generates CMS routes
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class Routes
{
    /**
     * Returns an array of routes for this module
     * @return array
     */
    public function getRoutes()
    {
        $routes = array();

        get_instance()->load->model('cms/cms_page_model');
        $pages = get_instance()->cms_page_model->get_all();

        foreach ($pages as $page) {

            if ($page->is_published) {

                $routes[$page->published->slug] = 'cms/render/page/' . $page->id;
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

        get_instance()->db->select('sh.slug,sh.page_id');
        get_instance()->db->join(NAILS_DB_PREFIX . 'cms_page p', 'p.id = sh.page_id');
        get_instance()->db->where('p.is_deleted', false);
        get_instance()->db->where('p.is_published', true);
        $slugs = get_instance()->db->get(NAILS_DB_PREFIX . 'cms_page_slug_history sh')->result();

        foreach ($slugs as $route) {

            if (!isset($routes[$route->slug])) {

                $routes[$route->slug] = 'cms/render/legacy_slug/' . $route->page_id;
            }
        }

        return $routes;
    }
}