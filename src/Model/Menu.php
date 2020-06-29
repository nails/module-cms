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
use Nails\Common\Model\Base;
use Nails\Common\Service\Database;
use Nails\Config;
use Nails\Factory;

class Menu extends Base
{
    /**
     * The Database service
     *
     * @var Database
     */
    private $oDb;

    // --------------------------------------------------------------------------

    /**
     * Constructs the model
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $this->oDb = Factory::service('Database');

        // --------------------------------------------------------------------------

        $this->table             = Config::get('NAILS_DB_PREFIX') . 'cms_menu';
        $this->tableAlias        = 'm';
        $this->table_item        = Config::get('NAILS_DB_PREFIX') . 'cms_menu_item';
        $this->table_item_prefix = 'mi';
    }

    // --------------------------------------------------------------------------

    /**
     * This method applies the conditionals which are common across the get_*()
     * methods and the count() method.
     *
     * @param array $aData Data passed from the calling method
     *
     * @return void
     **/
    protected function getCountCommon(array $aData = []): void
    {
        $this->oDb->select($this->tableAlias . '.*,u.first_name,u.last_name,u.profile_img,u.gender,ue.email');
        $this->oDb->join(
            Config::get('NAILS_DB_PREFIX') . 'user u',
            $this->tableAlias . '.modified_by = u.id',
            'LEFT'
        );
        $this->oDb->join(
            Config::get('NAILS_DB_PREFIX') . 'user_email ue',
            $this->tableAlias . '.modified_by = ue.user_id AND ue.is_primary = 1',
            'LEFT'
        );

        // --------------------------------------------------------------------------

        if (!empty($aData['keywords'])) {

            if (empty($aData['or_like'])) {
                $aData['or_like'] = [];
            }

            $aData['or_like'][] = [
                'column' => $this->tableAlias . '.label',
                'value'  => $aData['keywords'],
            ];
            $aData['or_like'][] = [
                'column' => $this->tableAlias . '.description',
                'value'  => $aData['keywords'],
            ];
        }

        parent::getCountCommon($aData);
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a single object
     *
     * The getAll() method iterates over each returned item with this method so as to
     * correctly format the output. Use this to cast integers and bools and/or organise data into objects.
     *
     * @param  object $oObj      A reference to the object being formatted.
     * @param  array  $aData     The same data array which is passed to _getcount_common, for reference if needed
     * @param  array  $aIntegers Fields which should be cast as integers if numerical and not null
     * @param  array  $aBools    Fields which should be cast as bools if not null
     * @param  array  $aFloats   Fields which should be cast as floats if not null
     *
     * @return void
     */
    protected function formatObject(
        &$oObj,
        array $aData = [],
        array $aIntegers = [],
        array $aBools = [],
        array $aFloats = []
    ) {
        parent::formatObject($oObj, $aData, $aIntegers, $aBools, $aFloats);

        $oTemp = (object) [
            'id'          => (int) $oObj->modified_by,
            'email'       => $oObj->email,
            'first_name'  => $oObj->first_name,
            'last_name'   => $oObj->last_name,
            'gender'      => $oObj->gender,
            'profile_img' => (int) $oObj->profile_img ?: null,
        ];

        $oObj->modified_by = $oTemp;

        unset($oObj->email);
        unset($oObj->first_name);
        unset($oObj->last_name);
        unset($oObj->gender);
        unset($oObj->profile_img);

        // --------------------------------------------------------------------------

        $bNestMenuItems = !empty($aData['nestItems']);
        $oObj->items    = $this->getMenuItems($oObj->id, $bNestMenuItems);
    }

    // --------------------------------------------------------------------------

    /**
     * Gets the items of an individual menu
     *
     * @param  int  $iMenuId The Menu's ID
     * @param  bool $bNested Whether to nest the menu items or not
     *
     * @return array
     */
    public function getMenuItems($iMenuId, $bNested = false)
    {
        $this->oDb->where('menu_id', $iMenuId);
        $this->oDb->order_by('order');
        $aItems = $this->oDb->get($this->table_item)->result();

        foreach ($aItems as &$oItem) {
            $this->formatObjectItem($oItem);
        }

        if ($bNested) {
            $aItems = $this->nestItems($aItems);
        }

        return $aItems;
    }

    // --------------------------------------------------------------------------

    /**
     * Format a menu item
     *
     * @param  \stdClass &$oObj The menu item to format
     *
     * @return voud
     */
    protected function formatObjectItem(&$oObj)
    {
        parent::formatObject($oObj);

        // --------------------------------------------------------------------------

        $oObj->page_id = $oObj->page_id ? (int) $oObj->page_id : null;

        //  If the menu is tied to a page then fetch that page's URL
        if ($oObj->page_id) {
            $oPageModel    = Factory::model('Page', Constants::MODULE_SLUG);
            $oObj->pageUrl = $oPageModel->getUrl($oObj->page_id);
        } else {
            $oObj->pageUrl = null;
        }

        // --------------------------------------------------------------------------

        unset($oObj->menu_id);
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a new object
     *
     * @param  array $aData         The data to create the object with
     * @param  bool  $bReturnObject Whether to return just the new ID or the full object
     *
     * @return mixed
     */
    public function create(array $aData = [], $bReturnObject = false)
    {
        $this->oDb->trans_begin();

        if (isset($aData['items'])) {
            $items = $aData['items'];
            unset($aData['items']);
        }

        $aData['slug'] = $this->generateSlug($aData);

        $result = parent::create($aData, $bReturnObject);

        if ($result && $items) {

            if ($bReturnObject) {
                $iMenuId = $result->id;
            } else {
                $iMenuId = $result;
            }

            /**
             * Take a note of the table prefixes, we're swapping them quickly while
             * we do this update, so we can leverage the parent methods for the items.
             */

            $table      = $this->table;
            $tableAlias = $this->tableAlias;

            $this->table      = $this->table_item;
            $this->tableAlias = $this->table_item_prefix;

            $newIds   = [];
            $iCounter = 0;

            foreach ($items as $item) {

                $aData = [
                    'menu_id' => $iMenuId,
                    'page_id' => !empty($item['page_id']) ? $item['page_id'] : null,
                    'url'     => !empty($item['url']) ? $item['url'] : null,
                    'label'   => !empty($item['label']) ? $item['label'] : null,
                    'order'   => $iCounter,
                ];

                /**
                 * Is both a page_id _and_ url set? If so, complain
                 */
                if (!empty($aData['page_id']) && !empty($aData['url'])) {
                    $this->setError('Can only set a URL or a CMS Page for item #' . ($iCounter + 1) . ', not both.');
                    $this->oDb->trans_rollback();
                    return false;
                }

                /**
                 * Look at the parent_id, if it's numerica, then it's an existing menu item,
                 * if not, then it's a new menu item. Non-numerical parents will be processed
                 * _after_ their parents, so we can assume that the paren'ts [newly created]
                 * ID is in $newIds - if it's not, then bugger,
                 */

                if (!empty($item['parent_id']) && is_numeric($item['parent_id'])) {

                    $aData['parent_id'] = $item['parent_id'];

                } elseif (!empty($item['parent_id'])) {

                    $parentId           = $item['parent_id'];
                    $aData['parent_id'] = !empty($newIds[$parentId]) ? $newIds[$parentId] : null;

                    if (empty($aData['parent_id'])) {
                        $this->setError('Failed to determine the parent item of item #' . ($iCounter + 1));
                        $this->oDb->trans_rollback();
                        return false;
                    }
                }

                $result = parent::create($aData);

                if (!$result) {

                    $this->setError('Failed to create item #' . ($iCounter + 1));
                    $this->oDb->trans_rollback();
                    return false;

                } else {
                    $newIds[$item['id']] = $result;
                }

                $iCounter++;
            }

            //  Reset the table and table prefix
            $this->table      = $table;
            $this->tableAlias = $tableAlias;

            //  Commit the transaction
            $this->oDb->trans_commit();

        } elseif ($result) {
            $this->oDb->trans_commit();
        } else {
            $this->oDb->trans_rollback();
        }

        return $result;
    }

    // --------------------------------------------------------------------------

    /**
     * Updates an existing object
     *
     * @todo Add transactions
     *
     * @param int   $id    The ID of the object to update
     * @param array $aData The data to update the object with
     *
     * @return bool
     **/
    public function update($id, array $aData = []): bool
    {
        $this->oDb->trans_begin();

        if (isset($aData['items'])) {
            $items = $aData['items'];
            unset($aData['items']);
        }

        $aData['slug'] = $this->generateSlug($aData, $id);

        $result = parent::update($id, $aData);

        if ($result && $items) {

            /**
             * Take a note of the table prefixes, we're swapping them quickly while
             * we do this update, so we can leverage the parent methods for the items.
             */

            $table      = $this->table;
            $tableAlias = $this->tableAlias;

            $this->table      = $this->table_item;
            $this->tableAlias = $this->table_item_prefix;

            $aIdsUpdated = [];
            $newIds      = [];
            $iCounter    = 0;

            foreach ($items as $item) {

                $aData = [
                    'page_id' => !empty($item['page_id']) ? $item['page_id'] : null,
                    'url'     => !empty($item['url']) ? $item['url'] : null,
                    'label'   => !empty($item['label']) ? $item['label'] : null,
                    'order'   => $iCounter,
                ];

                /**
                 * Is both a page_id _and_ url set? If so, complain
                 */
                if (!empty($aData['page_id']) && !empty($aData['url'])) {
                    $this->setError('Can only set a URL or a CMS Page for item #' . ($iCounter + 1) . ', not both.');
                    $this->oDb->trans_rollback();
                    return false;
                }

                /**
                 * Look at the parent_id, if it's numerical, then it's an existing menu item,
                 * if not, then it's a new menu item. Non-numerical parents will be processed
                 * _after_ their parents, so we can assume that the parent's [newly created]
                 * ID is in $newIds - if it's not, then bugger,
                 */
                if (!empty($item['parent_id']) && is_numeric($item['parent_id'])) {

                    $aData['parent_id'] = $item['parent_id'];

                } elseif (!empty($item['parent_id'])) {

                    $parentId           = $item['parent_id'];
                    $aData['parent_id'] = !empty($newIds[$parentId]) ? $newIds[$parentId] : null;

                    if (empty($aData['parent_id'])) {
                        $this->setError('Failed to determine the parent item of item #' . ($iCounter + 1));
                        $this->oDb->trans_rollback();
                        return false;
                    }

                } else {
                    $aData['parent_id'] = null;
                }

                /**
                 * Look at the ID, is it numerical? If so it's an existing menu item, if
                 * not it's a new item - create it and remember it's ID (in case it has
                 * any kiddy winkles).
                 */

                //  Update or create? If create remember and save ID
                if (!empty($item['id']) && is_numeric($item['id'])) {

                    $result = parent::update($item['id'], $aData);

                    if (!$result) {

                        $this->setError('Failed to update item #' . ($iCounter + 1));
                        $this->oDb->trans_rollback();
                        return false;

                    } else {
                        $aIdsUpdated[] = $item['id'];
                    }

                } else {

                    $aData['menu_id'] = $id;
                    $result           = parent::create($aData);

                    if (!$result) {

                        $this->setError('Failed to create item #' . ($iCounter + 1));
                        $this->oDb->trans_rollback();
                        return false;

                    } else {

                        $aIdsUpdated[]       = $result;
                        $newIds[$item['id']] = $result;
                    }
                }

                $iCounter++;
            }

            //  Remove any items which weren't updated or created
            $aIdsUpdated = array_filter($aIdsUpdated);
            $aIdsUpdated = array_unique($aIdsUpdated);

            $this->oDb->where('menu_id', $id);
            if ($aIdsUpdated) {
                $this->oDb->where_not_in('id', $aIdsUpdated);
            }
            $this->oDb->delete($this->table);

            //  Reset the table and table prefix
            $this->table      = $table;
            $this->tableAlias = $tableAlias;

            //  Commit the transaction
            $this->oDb->trans_commit();

        } elseif ($result) {
            $this->oDb->trans_commit();
        } else {
            $this->oDb->trans_rollback();
        }

        return $result;
    }

    // --------------------------------------------------------------------------

    /**
     * Nests menu items
     * Hat tip to Timur; http://stackoverflow.com/a/9224696/789224
     *
     * @param  \stdClass &$list      The list to nest
     * @param  int        $iParentId The parent list item's ID
     *
     * @return array
     */
    protected function nestItems(&$list, $iParentId = null)
    {
        $aResult = [];

        for ($i = 0, $c = count($list); $i < $c; $i++) {
            if ($list[$i]->parent_id == $iParentId) {
                $list[$i]->children = $this->nestItems($list, $list[$i]->id);
                $aResult[]          = $list[$i];
            }
        }

        return $aResult;
    }
}
