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

namespace Nails\Cms;

use Nails\Cms\Constants;
use Nails\Common\Interfaces\RouteGenerator;
use Nails\Config;
use Nails\Factory;
use PDO;

class Routes implements RouteGenerator
{
    /**
     * Returns an array of routes for this module
     *
     * @return string[]
     * @throws \Nails\Common\Exception\Database\ConnectionException
     * @throws \Nails\Common\Exception\FactoryException
     * @throws \Nails\Common\Exception\ModelException
     */
    public static function generate(): array
    {
        /** @var \Nails\Common\Service\PDODatabase $oDb */
        $oDb = Factory::service('PDODatabase');
        /** @var \Nails\Cms\Model\Page $oPageModel */
        $oPageModel = Factory::model('Page', Constants::MODULE_SLUG);
        $aRoutes    = [];

        $oPages = $oDb->query(
            'SELECT id, published_slug FROM ' . $oPageModel->getTableName() . ' WHERE is_published = 1;'
        );

        while ($oRow = $oPages->fetch(PDO::FETCH_OBJ)) {
            $aRoutes[$oRow->published_slug] = 'cms/render/page/' . $oRow->id;
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

        $oSlugs = $oDb->query('
            SELECT sh.slug, sh.page_id
            FROM ' . Config::get('NAILS_DB_PREFIX') . 'cms_page_slug_history sh
            JOIN ' . Config::get('NAILS_DB_PREFIX') . 'cms_page p ON p.id = sh.page_id
            WHERE
            p.is_deleted = 0
            AND p.is_published = 1
        ');

        while ($oRow = $oSlugs->fetch(PDO::FETCH_OBJ)) {
            if (!isset($aRoutes[$oRow->slug])) {
                $aRoutes[$oRow->slug] = 'cms/render/legacy_slug/' . $oRow->page_id;
            }
        }

        return $aRoutes;
    }
}
