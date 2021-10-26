<?php

/**
 * This model handle CMS Pages
 *
 * @package    Nails
 * @subpackage module-cms
 * @category   Model
 * @author     Nails Dev Team
 */

namespace Nails\Cms\Model;

use Nails\Cms\Constants;
use Nails\Cms\Events;
use Nails\Cms\Service\Template;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Exception\NailsException;
use Nails\Common\Factory\Model\Field;
use Nails\Common\Model\Base;
use Nails\Cms\Resource;
use Nails\Common\Service\Database;
use Nails\Common\Service\Event;
use Nails\Common\Service\Routes;
use Nails\Config;
use Nails\Factory;

/**
 * Class Page
 *
 * @package Nails\Cms\Model
 */
class Page extends Base
{
    /**
     * The table this model represents
     *
     * @var string
     */
    const TABLE = NAILS_DB_PREFIX . 'cms_page';

    /**
     * The name of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_NAME = 'Page';

    /**
     * The provider of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_PROVIDER = Constants::MODULE_SLUG;

    /**
     * Whether the model is a preview
     *
     * @var bool
     */
    const IS_PREVIEW = false;

    /**
     * Whether this model uses destructive delete or not
     *
     * @var bool
     */
    const DESTRUCTIVE_DELETE = false;

    /**
     * The default column to sort on
     *
     * @var string|null
     */
    const DEFAULT_SORT_COLUMN = 'draft_slug';

    /**
     * The default sort order
     *
     * @var string
     */
    const DEFAULT_SORT_ORDER = self::SORT_ASC;

    // --------------------------------------------------------------------------

    /**
     * The name of the "slug" column
     *
     * @var string
     */
    protected $tableSlugColumn = 'draft_slug';

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     * @return string[]
     */
    public function getSearchableColumns(): array
    {
        return [
            'draft_title',
            'published_title',
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a new CMS Page
     *
     * @param array $aData         The data to create the page with
     * @param bool  $bReturnObject Whether to return the ID or the object
     *
     * @return bool|int|Resource\Page
     */
    public function create(array $aData = [], $bReturnObject = false)
    {
        /** @var Database $oDb */
        $oDb = Factory::service('Database');
        $oDb->transaction()->start();

        //  Create a new blank row to work with
        $iId = parent::create();

        if (!$iId) {
            $this->setError('Unable to create base page object. ' . $this->lastError());
            $oDb->transaction()->rollback();
            return false;
        }

        //  Try and update it depending on how the update went, commit & update or rollback
        if ($this->update($iId, $aData)) {
            $oDb->transaction()->commit();
            return $bReturnObject ? $this->getById($iId) : $iId;

        } else {
            $oDb->transaction()->rollback();
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Update a CMS Page
     *
     * @param int[]|int $mIds
     * @param array     $aData
     *
     * @return bool
     * @throws NailsException
     */
    public function update($mIds, array $aData = []): bool
    {
        if (is_array($mIds)) {
            throw new NailsException('This model does not support updating multiple items at once');
        }

        $iId = $mIds;

        //  Fetch the current version of this page, for reference.
        $oCurrent = $this->getById($iId);

        if (!$oCurrent) {
            $this->setError('Invalid Page ID');
            return false;
        }

        // --------------------------------------------------------------------------

        /** @var Database $oDb */
        $oDb = Factory::service('Database');
        $oDb->transaction()->start();

        // --------------------------------------------------------------------------

        //  Start prepping the data which doesn't require much thinking
        $aUpdateData = [
            'draft_parent_id'        => !empty($aData['parent_id']) ? (int) $aData['parent_id'] : null,
            'draft_title'            => !empty($aData['title']) ? trim($aData['title']) : 'Untitled',
            'draft_seo_title'        => !empty($aData['seo_title']) ? trim($aData['seo_title']) : '',
            'draft_seo_description'  => !empty($aData['seo_description']) ? trim($aData['seo_description']) : '',
            'draft_seo_keywords'     => !empty($aData['seo_keywords']) ? trim($aData['seo_keywords']) : '',
            'draft_seo_image_id'     => !empty($aData['seo_image_id']) ? (int) $aData['seo_image_id'] : null,
            'draft_template'         => !empty($aData['template']) ? trim($aData['template']) : null,
            'draft_template_data'    => !empty($aData['template_data']) ? trim($aData['template_data']) : null,
            'draft_template_options' => !empty($aData['template_options']) ? trim($aData['template_options']) : null,
        ];

        // --------------------------------------------------------------------------

        /**
         * Additional sanitising; encode HTML entities. Also encode the pipe character
         * in the title, so that it doesn't break our explode
         */

        $iFlag                                = ENT_COMPAT | ENT_HTML401;
        $aUpdateData['draft_title']           = htmlentities(str_replace('|', '&#124;', $aUpdateData['draft_title']), $iFlag, 'UTF-8', false);
        $aUpdateData['draft_seo_title']       = htmlentities($aUpdateData['draft_seo_title'], $iFlag, 'UTF-8', false);
        $aUpdateData['draft_seo_description'] = htmlentities($aUpdateData['draft_seo_description'], $iFlag, 'UTF-8', false);
        $aUpdateData['draft_seo_keywords']    = htmlentities($aUpdateData['draft_seo_keywords'], $iFlag, 'UTF-8', false);

        // --------------------------------------------------------------------------

        //  Prep data which requires a little more intensive processing

        //  There is a parent, get some basics about it for use below
        if ($aUpdateData['draft_parent_id']) {

            $oDb->select('draft_slug, draft_breadcrumbs');
            $oDb->where('id', $aUpdateData['draft_parent_id']);
            $oParent = $oDb->get($this->getTableName())->row();

            if (!$oParent) {

                $this->setError('Invalid Parent ID.');
                $oDb->transaction()->rollback();

                return false;
            }
        }

        $sSlugPrefix = !empty($oParent) ? $oParent->draft_slug . '/' : '';

        //  Work out the slug
        if (empty($aData['slug']) || static::IS_PREVIEW) {

            $aUpdateData['draft_slug'] = $sSlugPrefix . $this->generateSlug(
                    $aUpdateData,
                    $oCurrent->id
                );

        } else {

            //  Test slug is valid
            $aUpdateData['draft_slug'] = $sSlugPrefix . $aData['slug'];
            $oDb->where('draft_slug', $aUpdateData['draft_slug']);
            $oDb->where('id !=', $oCurrent->id);
            if ($oDb->count_all_results($this->getTableName())) {

                $this->setError('Slug is already in use.');
                $oDb->transaction()->rollback();

                return false;
            }
        }

        $aSegments                     = explode('/', $aUpdateData['draft_slug']);
        $aUpdateData['draft_slug_end'] = end($aSegments);

        // --------------------------------------------------------------------------

        //  Generate the breadcrumbs
        $aUpdateData['draft_breadcrumbs'] = [];

        if (!empty($oParent->draft_breadcrumbs)) {
            $aUpdateData['draft_breadcrumbs'] = json_decode($oParent->draft_breadcrumbs);
        }

        $oTemp = (object) [
            'id'    => $oCurrent->id,
            'title' => $aUpdateData['draft_title'],
            'slug'  => $aUpdateData['draft_slug'],
        ];

        $aUpdateData['draft_breadcrumbs'][] = $oTemp;
        unset($oTemp);

        $aUpdateData['draft_breadcrumbs'] = json_encode($aUpdateData['draft_breadcrumbs']);

        // --------------------------------------------------------------------------

        //  Set a hash for the draft
        $aUpdateData['draft_hash'] = md5(json_encode($aUpdateData));

        // --------------------------------------------------------------------------

        if (parent::update($oCurrent->id, $aUpdateData)) {

            //  For each child regenerate the breadcrumbs and slugs (only if the title or slug has changed)
            $bTitleChange = $oCurrent->draft->title != $aUpdateData['draft_title'];
            $bSlugChange  = $oCurrent->draft->slug != $aUpdateData['draft_slug'];
            if ($bTitleChange || $bSlugChange) {

                //  Refresh the current
                $oCurrent     = $this->getById($oCurrent->id);
                $aChildren    = $this->getIdsOfChildren($oCurrent->id);
                $aUpdateData  = [];
                $aParentCache = [
                    $oCurrent->id => [
                        'slug'  => $oCurrent->draft->slug,
                        'crumb' => $oCurrent->draft->breadcrumbs,
                    ],
                ];

                if ($aChildren) {

                    /**
                     * For each child we need to update it's slug and it's breadcrumbs. We'll do this by appending
                     * it's details onto the parent's slug/breadcrumbs. If we don't know the parent's details
                     * (should not happen as kids will be in a hierarchical order) then we need to look it up.
                     */
                    foreach ($aChildren as $iChildId) {

                        $oChild = $this->getById($iChildId);
                        if (!$oChild) {
                            continue;
                        }

                        $aParentCache[$oChild->id] = ['slug' => '', 'crumb' => ''];

                        $oChildSlug                        = $aParentCache[$oChild->draft->parent_id]['slug'] . '/' . $oChild->draft->slug_end;
                        $aParentCache[$oChild->id]['slug'] = $oChildSlug;

                        $oChildCrumb        = new \stdClass();
                        $oChildCrumb->id    = $oChild->id;
                        $oChildCrumb->title = $oChild->draft->title;
                        $oChildCrumb->slug  = $oChildSlug;

                        $aChildCrumbs = $aParentCache[$oChild->draft->parent_id]['crumb'];
                        array_push($aChildCrumbs, $oChildCrumb);

                        $aParentCache[$oChild->id]['crumb'] = $aChildCrumbs;
                        $aUpdateData[$oChild->id]           = $aParentCache[$oChild->id];
                    }

                    //  Update each child
                    foreach ($aUpdateData as $iId => $aCache) {

                        $aData = [
                            'draft_slug'        => $aCache['slug'],
                            'draft_breadcrumbs' => json_encode($aCache['crumb']),
                        ];

                        if (!parent::update($iId, $aData)) {

                            $this->setError('Failed to update child page\'s slug and breadcrumbs');
                            $oDb->transaction()->rollback();

                            return false;
                        }
                    }
                }
            }

            // --------------------------------------------------------------------------

            //  Finish up.
            $oDb->transaction()->commit();

            // --------------------------------------------------------------------------

            //  Rewrite routes
            //  If routes are generated with the preview table selected then the routes file will _empty_
            if (!static::IS_PREVIEW) {
                $this->rewriteRoutes();
            }

            // --------------------------------------------------------------------------

            $this->triggerEvent(
                static::EVENT_UPDATED,
                [$iId]
            );

            // --------------------------------------------------------------------------

            //  @todo - Kill caches for this page and all children
            return true;

        } else {
            $this->setError('Failed to update page object.');
            $oDb->transaction()->rollback();
        }

        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the slug's base, compiled from the label
     *
     * @param string $sLabel The item's label
     * @param string $sKey   The key to use from the supplied data array
     *
     * @return string
     */
    protected function generateSlugBase(array $aData, string $sKey = null): string
    {
        return parent::generateSlugBase($aData, $sKey ?? 'draft_title');
    }

    // --------------------------------------------------------------------------

    /**
     * Publish a page
     *
     * @param int $iId The ID of the page to publish
     *
     * @return bool
     */
    public function publish(int $iId): bool
    {
        //  Check the page is valid
        $oPage = $this->getById($iId);
        /** @var \DateTime $oDate */
        $oDate = Factory::factory('DateTime');

        if (!$oPage) {
            $this->setError('Invalid Page ID');
            return false;
        }

        // --------------------------------------------------------------------------

        //  Start the transaction
        $oDb = Factory::service('Database');
        $oDb->transaction()->start();

        // --------------------------------------------------------------------------

        //  If the slug has changed add an entry to the slug history page
        $aSlugHistory = [];
        if ($oPage->published->slug && $oPage->published->slug != $oPage->draft->slug) {
            $aSlugHistory[] = [
                'slug'    => $oPage->published->slug,
                'page_id' => $oPage->id,
            ];
        }

        // --------------------------------------------------------------------------

        //  Update the published_* columns to be the same as the draft columns
        $oDb->set('published_hash', 'draft_hash', false);
        $oDb->set('published_parent_id', 'draft_parent_id', false);
        $oDb->set('published_slug', 'draft_slug', false);
        $oDb->set('published_slug_end', 'draft_slug_end', false);
        $oDb->set('published_template', 'draft_template', false);
        $oDb->set('published_template_data', 'draft_template_data', false);
        $oDb->set('published_template_options', 'draft_template_options', false);
        $oDb->set('published_title', 'draft_title', false);
        $oDb->set('published_breadcrumbs', 'draft_breadcrumbs', false);
        $oDb->set('published_seo_title', 'draft_seo_title', false);
        $oDb->set('published_seo_description', 'draft_seo_description', false);
        $oDb->set('published_seo_keywords', 'draft_seo_keywords', false);
        $oDb->set('published_seo_image_id', 'draft_seo_image_id', false);
        $oDb->set('is_published', true);
        $oDb->set($this->getColumnModified(), $oDate->format('Y-m-d H:i:s'));

        if (isLoggedIn()) {
            $oDb->set($this->getColumnModifiedBy(), activeUser('id'));
        }

        $oDb->where('id', $oPage->id);

        if ($oDb->update($this->getTableName())) {

            //  Fetch the children, returning the data we need for the updates
            $aChildren = $this->getIdsOfChildren($oPage->id);

            if ($aChildren) {

                /**
                 * Loop each child and update it's published details, but only
                 * if they've changed.
                 */

                foreach ($aChildren as $iChildId) {

                    $oChild = $this->getById($iChildId);
                    if (!$oChild) {
                        continue;
                    }

                    $bTitleChanged = $oChild->published->title == $oChild->draft->title;
                    $bSlugChanged  = $oChild->published->slug == $oChild->draft->slug;
                    if (!$bTitleChanged && !$bSlugChanged) {
                        continue;
                    }

                    //  First make a note of the old slug
                    if ($oChild->is_published) {
                        $aSlugHistory[] = [
                            'slug'    => $oChild->draft->slug,
                            'page_id' => $oChild->id,
                        ];
                    }

                    //  Next we set the appropriate fields
                    $oDb->set('published_slug', $oChild->draft->slug);
                    $oDb->set('published_slug_end', $oChild->draft->slug_end);
                    $oDb->set('published_breadcrumbs', json_encode($oChild->draft->breadcrumbs));
                    $oDb->set($this->getColumnModified(), $oDate->format('Y-m-d H:i:s'));

                    $oDb->where('id', $oChild->id);

                    if (!$oDb->update($this->getTableName())) {

                        $this->setError('Failed to update a child page\'s data.');
                        $oDb->transaction()->rollback();

                        return false;
                    }
                }
            }

            //  Add any slug_history items
            foreach ($aSlugHistory as $item) {
                $oDb->set('hash', md5($item['slug'] . $item['page_id']));
                $oDb->set('slug', $item['slug']);
                $oDb->set('page_id', $item['page_id']);
                $oDb->set($this->getColumnCreated(), 'NOW()', false);
                $oDb->replace(Config::get('NAILS_DB_PREFIX') . 'cms_page_slug_history');
            }

            $oDb->transaction()->commit();

            // --------------------------------------------------------------------------

            $this->triggerEvent(
                Events::PAGE_PUBLISHED,
                [$iId]
            );

            // --------------------------------------------------------------------------

            $this->rewriteRoutes();

            //  @TODO: Kill caches for this page and all children
            return true;

        } else {
            $oDb->transaction()->rollback();
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Unpublish a page
     *
     * @param int $iId The page to unpublish
     *
     * @return bool
     * @throws FactoryException
     * @throws ModelException
     */
    public function unpublish(int $iId): bool
    {
        /** @var Database $oDb */
        $oDb = Factory::service('Database');
        /** @var \DateTime $oNow */
        $oNow = Factory::factory('DateTime');

        $oPage = $this->getById($iId);

        if (!$oPage) {
            $this->setError('Invalid Page ID');
            return false;
        }

        // --------------------------------------------------------------------------

        $oDb->set('is_published', false);
        $oDb->set($this->getColumnModified(), $oNow->format('Y-m-d H:i:s'));
        $oDb->set($this->getColumnModifiedBy(), activeUser('id') ?: null);
        $oDb->where('id', $iId);

        if (!$oDb->update($this->getTableName())) {
            $this->setError('Failed to');
            return false;
        }

        // --------------------------------------------------------------------------

        $this->triggerEvent(
            Events::PAGE_UNPUBLISHED,
            [$iId]
        );

        // --------------------------------------------------------------------------

        $this->rewriteRoutes();

        // --------------------------------------------------------------------------

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Gets all pages, nested
     *
     * @param bool $bUseDraft Whether to use the published or draft version of pages
     *
     * @return array
     */
    public function getAllNested(bool $bUseDraft = true): array
    {
        return $this->nestPages(
            $this->getAll([
                'select' => $this->describeFieldsExcludingData(),
            ]),
            null,
            $bUseDraft
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Get all pages nested, but as a flat array
     *
     * @param string $sSeparator               The separator to use between pages
     * @param bool   $bMurderParentsOfChildren Whether to include parents in the result
     *
     * @return array
     */
    public function getAllNestedFlat($sSeparator = null, $bMurderParentsOfChildren = true)
    {
        $sSeparator = $sSeparator ?: ' &rsaquo; ';
        $aOut       = [];
        $aPages     = $this->getAll([
            'select' => $this->describeFieldsExcludingData(),
        ]);

        foreach ($aPages as $oPage) {
            $aOut[$oPage->id] = $this->findParents($oPage->draft->parent_id, $aPages, $sSeparator) . $oPage->draft->title;
        }

        asort($aOut);

        // --------------------------------------------------------------------------

        //  Remove parents from the array if they have any children
        if ($bMurderParentsOfChildren) {

            foreach ($aOut as $key => &$page) {

                $bFound  = false;
                $sNeedle = $page . $sSeparator;

                //  Hat tip - http://uk3.php.net/manual/en/function.array-search.php#90711
                foreach ($aOut as $item) {
                    if (strpos($item, $sNeedle) !== false) {
                        $bFound = true;
                        break;
                    }
                }

                if ($bFound) {
                    unset($aOut[$key]);
                }
            }
        }

        return $aOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Nests pages
     * Hat tip to Timur; http://stackoverflow.com/a/9224696/789224
     *
     * @param array   &$aList     The pages to nest
     * @param int|null $iParentId The parent ID of the page
     * @param bool     $bUseDraft Whether to use published data or draft data
     *
     * @return array
     */
    protected function nestPages(array &$aList, int $iParentId = null, bool $bUseDraft = true): array
    {
        $aResult = [];

        for ($i = 0, $c = count($aList); $i < $c; $i++) {

            $iCurParentId = $bUseDraft ? $aList[$i]->draft->parent_id : $aList[$i]->published->parent_id;

            if ($iCurParentId == $iParentId) {
                $aList[$i]->children = $this->nestPages($aList, $aList[$i]->id, $bUseDraft);
                $aResult[]           = $aList[$i];
            }
        }

        return $aResult;
    }

    // --------------------------------------------------------------------------

    /**
     * Find the parents of a page
     *
     * @param int    $iParentId  The page to find parents for
     * @param array &$aSources   The source pages
     * @param string $sSeparator The separator to use
     *
     * @return string
     */
    protected function findParents(?int $iParentId, array &$aSources, string $sSeparator): string
    {
        if (!$iParentId) {

            //  No parent ID, end of the line señor!
            return '';

        } else {

            //  There is a parent, look for it
            foreach ($aSources as $oSource) {
                if ($oSource->id == $iParentId) {
                    $oParent = $oSource;
                }
            }

            if (!empty($oParent)) {

                //  Parent was found, does it have any parents?
                if ($oParent->draft->parent_id) {

                    //  Yes it does, repeat!
                    $sReturn = $this->findParents($oParent->draft->parent_id, $aSources, $sSeparator);

                    return $sReturn ? $sReturn . $oParent->draft->title . $sSeparator : $oParent->draft->title;

                } else {
                    //  Nope, end of the line mademoiselle
                    return $oParent->draft->title . $sSeparator;
                }

            } else {
                //  Did not find parent, give up.
                return '';
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Get the IDs of a page's children
     *
     * @param int    $iPageId The ID of the page to look at
     * @param string $sFormat How to return the data, one of ID, ID_SLUG, ID_SLUG_TITLE or ID_SLUG_TITLE_PUBLISHED
     *
     * @return array
     */
    public function getIdsOfChildren(int $iPageId, string $sFormat = 'ID'): array
    {
        $aOut = [];
        /** @var Database $oDb */
        $oDb = Factory::service('Database');

        $oDb->select('id,draft_slug,draft_title,is_published');
        $oDb->where('draft_parent_id', $iPageId);
        $aChildren = $oDb->get($this->getTableName())->result();

        if ($aChildren) {

            foreach ($aChildren as $oChild) {

                switch ($sFormat) {

                    case 'ID':
                        $aOut[] = $oChild->id;
                        break;

                    case 'ID_SLUG':
                        $aOut[] = [
                            'id'   => $oChild->id,
                            'slug' => $oChild->draft_slug,
                        ];
                        break;

                    case 'ID_SLUG_TITLE':
                        $aOut[] = [
                            'id'    => $oChild->id,
                            'slug'  => $oChild->draft_slug,
                            'title' => $oChild->draft_title,
                        ];
                        break;

                    case 'ID_SLUG_TITLE_PUBLISHED':
                        $aOut[] = [
                            'id'           => $oChild->id,
                            'slug'         => $oChild->draft_slug,
                            'title'        => $oChild->draft_title,
                            'is_published' => (bool) $oChild->is_published,
                        ];
                        break;
                }

                $aOut = array_merge($aOut, $this->getIdsOfChildren($oChild->id, $sFormat));
            }

            return $aOut;

        } else {
            return $aOut;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all objects as a flat array, optionally paginated.
     *
     * @param int   $iPage           The page number of the results, if null then no pagination
     * @param int   $iPerPage        How many items per page of paginated results
     * @param mixed $aData           Any data to pass to getCountCommon()
     * @param bool  $bIncludeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     *
     * @return string[]
     */
    public function getAllFlat($iPage = null, $iPerPage = null, array $aData = [], bool $bIncludeDeleted = false): array
    {
        //  If the first value is an array then treat as if called with getAll(null, null, $aData);
        if (is_array($iPage)) {
            $aData = $iPage;
            $iPage = null;
        }

        if (empty($aData['select'])) {
            $aData['select'] = $this->describeFieldsExcludingData();
        }

        $aOut   = [];
        $aPages = $this->getAll($iPage, $iPerPage, $aData, $bIncludeDeleted);

        foreach ($aPages as $oPage) {
            if (!empty($aData['useDraft'])) {
                $aOut[$oPage->id] = $oPage->draft->title;
            } else {
                $aOut[$oPage->id] = $oPage->published->title;
            }
        }

        return $aOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Get the top level pages, i.e., those without a parent
     *
     * @param int   $iPage           The page number of the results, if null then no pagination
     * @param int   $iPerPage        How many items per page of paginated results
     * @param array $aData           Any data to pass to getCountCommon()
     * @param bool  $bIncludeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     *
     * @return Resource\Page[]
     */
    public function getTopLevel($iPage = null, $iPerPage = null, array $aData = [], bool $bIncludeDeleted = false): array
    {
        if (empty($aData['where'])) {
            $aData['were'] = [];
        }

        $aData['where'][] = !empty($aData['useDraft'])
            ? ['draft_parent_id', null]
            : ['published_parent_id', null];

        return $this->getAll($iPage, $iPerPage, $aData, $bIncludeDeleted);
    }

    // --------------------------------------------------------------------------

    /**
     * Get the siblings of a page, i.e those with the same parent
     *
     * @param int  $iId       The page whose siblings to fetch
     * @param bool $bUseDraft Whether to use published data, or draft data
     *
     * @return Resource\Page[]
     */
    public function getSiblings(int $iId, bool $bUseDraft = true): array
    {
        /** @var Resource\Page|null $oPage */
        $oPage = $this->getById($iId);

        if (!$oPage) {
            return [];
        }

        return $this->getAll([
            'where' => [
                $bUseDraft
                    ? ['draft_parent_id', $oPage->draft->parent_id]
                    : ['published_parent_id', $oPage->published->parent_id],
            ],
        ]);
    }

    // --------------------------------------------------------------------------

    /**
     * Get the ID of the configured homepage
     *
     * @return int
     */
    public function getHomepageId(): ?int
    {
        return (int) appSetting('homepage', Constants::MODULE_SLUG) ?: null;
    }

    // --------------------------------------------------------------------------

    /**
     * Get the page marked as the homepage
     *
     * @return Resource\Page|null
     * @throws ModelException
     */
    public function getHomepage(): ?Resource\Page
    {
        $iHomepageId = $this->getHomepageId();

        if (empty($iHomepageId)) {
            return false;
        }

        /** @var Resource\Page|null $oPage */
        $oPage = $this->getById($iHomepageId);
        return $oPage;
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a page and it's children
     *
     * @param int $iId The ID of the page to delete
     *
     * @return bool
     */
    public function delete($iId): bool
    {
        $oPage = $this->getById($iId);

        if (!$oPage) {
            $this->setError('Invalid page ID');
            return false;
        }

        // --------------------------------------------------------------------------

        /** @var Database $oDb */
        $oDb = Factory::service('Database');
        $oDb->transaction()->start();

        try {

            $oDb->where('id', $iId);
            $oDb->set($this->getColumnIsDeleted(), true);
            $oDb->set($this->getColumnModified(), 'NOW()', false);

            if (isLoggedIn()) {
                $oDb->set($this->getColumnModifiedBy(), activeUser('id'));
            }

            if (!$oDb->update($this->getTableName())) {
                throw new NailsException('Failed to delete item');
            }

            //  Success, update children
            $aChildren = $this->getIdsOfChildren($iId);

            if ($aChildren) {

                $oDb->where_in('id', $aChildren);
                $oDb->set($this->getColumnIsDeleted(), true);
                $oDb->set($this->getColumnModified(), 'NOW()', false);

                if (isLoggedIn()) {
                    $oDb->set($this->getColumnModifiedBy(), activeUser('id'));
                }

                if (!$oDb->update($this->getTableName())) {
                    throw new NailsException('Unable to delete children pages');
                }

            }

            $oDb->transaction()->commit();

        } catch (\Exception $e) {
            $oDb->transaction()->rollback();
            $this->setError($e->getMessage());
            return false;
        }

        // --------------------------------------------------------------------------

        $this->rewriteRoutes();

        // --------------------------------------------------------------------------

        $this->triggerEvent(
            static::EVENT_DELETED,
            [$iId]
        );

        // --------------------------------------------------------------------------

        //  @todo - Kill caches for this page and all children
        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Permanently delete a page and it's children
     *
     * @param int $iId The ID of the page to destroy
     *
     * @return bool
     */
    public function destroy($iId): bool
    {
        //  @TODO: implement this?
        $this->setError('It is not possible to destroy pages using this system.');

        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the URL of a page
     *
     * @param int  $iPageId       The ID of the page to look up
     * @param bool $bUsePublished Whether to use the `published` data, or the `draft` data
     *
     * @return string|null
     */
    public function getUrl(int $iPageId, bool $bUsePublished = true): ?string
    {
        /** @var Resource\Page|null $oPage */
        $oPage = $this->getById($iPageId);
        if (!$oPage) {
            return null;
        }

        return $bUsePublished
            ? $oPage->published->url
            : $oPage->draft->url;
    }

    // --------------------------------------------------------------------------

    /**
     * Copies a CMS Page
     *
     * @param int  $iId           The ID of the page to copy
     * @param bool $bReturnObject Whether to return the new ID, or the new object
     *
     * @return bool|mixed
     * @throws NailsException
     * @throws FactoryException
     * @throws ModelException
     */
    public function copy(int $iId, bool $bReturnObject = false)
    {
        $oPage = $this->getById($iId);
        if (empty($oPage)) {
            throw new NailsException('Cannot copy page. Invalid ID "' . $iId . '"');
        }

        /** @var \DateTime $oNow */
        $oNow = Factory::factory('DateTime');

        $aPageData = [
            'title'            => $oPage->draft->title . sprintf(' (Copy %s)', toUserDatetime($oNow->format('Y-m-d H:i:s'))),
            'slug'             => $oPage->draft->slug . sprintf('-copy-%s', url_title(toUserDatetime($oNow->format('Y-m-d H:i:s')))),
            'parent_id'        => (int) $oPage->draft->parent_id,
            'template'         => $oPage->draft->template,
            'template_data'    => json_encode($oPage->draft->template_data),
            'template_options' => json_encode($oPage->draft->template_options),
            'seo_title'        => $oPage->draft->seo_title,
            'seo_description'  => $oPage->draft->seo_description,
            'seo_keywords'     => $oPage->draft->seo_keywords,
            'seo_image_id'     => $oPage->draft->seo_image_id,
        ];

        return $this->create($aPageData, $bReturnObject);
    }

    // --------------------------------------------------------------------------

    /**
     * Triggers the ROUTES:REWRITE event
     *
     * @throws FactoryException
     */
    protected function rewriteRoutes(): void
    {
        /** @var Event $oEventService */
        $oEventService = Factory::service('Event');
        $oEventService->trigger(\Nails\Common\Events::ROUTES_UPDATE);
    }

    // --------------------------------------------------------------------------

    /**
     * Describes the fields excluding the data fields (which can be very big and cause memory issues)
     *
     * @return Field[]
     */
    public function describeFieldsExcludingData()
    {
        return array_filter(array_keys($this->describeFields()), function (string $sField) {
            return !in_array($sField, $this->getDataColumns());
        });
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the columns which contain page data
     *
     * @return string[]
     */
    public function getDataColumns(): array
    {
        return [
            'published_template_data',
            'draft_template_data',
        ];
    }
}
