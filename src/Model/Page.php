<?php

/**
 * This model handle CMS Pages
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Model;

use Nails\Cms\Events;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Exception\NailsException;
use Nails\Common\Model\Base;
use Nails\Common\Service\Database;
use Nails\Common\Service\Event;
use Nails\Common\Service\Routes;
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
     * Whether the model is a preview
     *
     * @var bool
     */
    const IS_PREVIEW = true;

    /**
     * Whether this model uses destructive delete or not
     *
     * @var bool
     */
    const DESTRUCTIVE_DELETE = false;

    // --------------------------------------------------------------------------

    /**
     * Page constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->searchableFields = ['draft_title', 'draft_template_data'];
        $this->tableSlugColumn  = 'draft_slug';
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a new CMS Page
     *
     * @param array $aData         The data to create the page with
     * @param bool  $bReturnObject Whether to return the ID or the object
     *
     * @return bool|mixed
     */
    public function create(array $aData = [], $bReturnObject = false)
    {
        $oDb = Factory::service('Database');
        $oDb->trans_begin();

        //  Create a new blank row to work with
        $iId = parent::create();

        if (!$iId) {
            $this->setError('Unable to create base page object. ' . $this->lastError());
            $oDb->trans_rollback();
            return false;
        }

        //  Try and update it depending on how the update went, commit & update or rollback
        if ($this->update($iId, $aData)) {
            $oDb->trans_commit();
            return $bReturnObject ? $this->getById($iId) : $iId;

        } else {
            $oDb->trans_rollback();
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Update a CMS Page
     *
     * @param array|int $mIds
     * @param array     $aData
     *
     * @return bool
     * @throws NailsException
     */
    public function update($mIds, array $aData = []): bool
    {
        if (is_array($mIds)) {
            throw new NailsException('This model does not support updating multiple items at once');
        } else {
            $iId = $mIds;
        }

        //  Fetch the current version of this page, for reference.
        $oCurrent = $this->getById($iId);

        if (!$oCurrent) {
            $this->setError('Invalid Page ID');
            return false;
        }

        // --------------------------------------------------------------------------

        //  Start the transaction
        $oDb = Factory::service('Database');
        $oDb->trans_begin();

        // --------------------------------------------------------------------------

        //  Start prepping the data which doesn't require much thinking
        $aUpdateData = [
            'draft_parent_id'        => !empty($aData['parent_id']) ? (int) $aData['parent_id'] : null,
            'draft_title'            => !empty($aData['title']) ? trim($aData['title']) : 'Untitled',
            'draft_seo_title'        => !empty($aData['seo_title']) ? trim($aData['seo_title']) : '',
            'draft_seo_description'  => !empty($aData['seo_description']) ? trim($aData['seo_description']) : '',
            'draft_seo_keywords'     => !empty($aData['seo_keywords']) ? trim($aData['seo_keywords']) : '',
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
                $oDb->trans_rollback();

                return false;
            }
        }

        $sSlugPrefix = !empty($oParent) ? $oParent->draft_slug . '/' : '';

        //  Work out the slug
        if (empty($aData['slug']) || static::IS_PREVIEW) {

            $aUpdateData['draft_slug'] = $sSlugPrefix . $this->generateSlug(
                    $aUpdateData['draft_title'],
                    $oCurrent->id
                );

        } else {

            //  Test slug is valid
            $aUpdateData['draft_slug'] = $sSlugPrefix . $aData['slug'];
            $oDb->where('draft_slug', $aUpdateData['draft_slug']);
            $oDb->where('id !=', $oCurrent->id);
            if ($oDb->count_all_results($this->getTableName())) {

                $this->setError('Slug is already in use.');
                $oDb->trans_rollback();

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
                            $oDb->trans_rollback();

                            return false;
                        }
                    }
                }
            }

            // --------------------------------------------------------------------------

            //  Finish up.
            $oDb->trans_commit();

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
            $oDb->trans_rollback();

            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Render a template with the provided widgets and additional data
     *
     * @param string $sTemplate        The template to render
     * @param array  $oTemplateData    The template data (i.e. areas and widgets)
     * @param array  $oTemplateOptions The template options
     *
     * @return mixed                    String (the rendered template) on success, false on failure
     */
    public function render($sTemplate, $oTemplateData = [], $oTemplateOptions = [])
    {
        $oTemplateService = Factory::service('Template', 'nails/module-cms');
        $oTemplate        = $oTemplateService->getBySlug($sTemplate, 'RENDER');

        if (!$oTemplate) {
            $this->setError('"' . $sTemplate . '" is not a valid template.');

            return false;
        }

        return $oTemplate->render((array) $oTemplateData, (array) $oTemplateOptions);
    }

    // --------------------------------------------------------------------------

    /**
     * Publish a page
     *
     * @param int $iId The ID of the page to publish
     *
     * @return boolean
     */
    public function publish($iId)
    {
        //  Check the page is valid
        $oPage = $this->getById($iId);
        $oDate = Factory::factory('DateTime');

        if (!$oPage) {
            $this->setError('Invalid Page ID');
            return false;
        }

        // --------------------------------------------------------------------------

        //  Start the transaction
        $oDb = Factory::service('Database');
        $oDb->trans_begin();

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
        $oDb->set('is_published', true);
        $oDb->set('modified', $oDate->format('Y-m-d H:i:s'));

        if (isLoggedIn()) {
            $oDb->set('modified_by', activeUser('id'));
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
                    $oDb->set('modified', $oDate->format('Y-m-d H:i:s'));

                    $oDb->where('id', $oChild->id);

                    if (!$oDb->update($this->getTableName())) {

                        $this->setError('Failed to update a child page\'s data.');
                        $oDb->trans_rollback();

                        return false;
                    }
                }
            }

            //  Add any slug_history items
            foreach ($aSlugHistory as $item) {
                $oDb->set('hash', md5($item['slug'] . $item['page_id']));
                $oDb->set('slug', $item['slug']);
                $oDb->set('page_id', $item['page_id']);
                $oDb->set('created', 'NOW()', false);
                $oDb->replace(NAILS_DB_PREFIX . 'cms_page_slug_history');
            }

            $oDb->trans_commit();

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
            $oDb->trans_rollback();
            return false;
        }
    }

    // --------------------------------------------------------------------------

    public function unpublish(int $iId)
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
        $oDb->set('modified', $oNow->format('Y-m-d H:i:s'));
        $oDb->set('modified_by', activeUser('id') ?: null);
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
     * Applies common conditionals
     *
     * This method applies the conditionals which are common across the get_*()
     * methods and the count() method.
     *
     * @param array $data Data passed from the calling method
     *
     * @return void
     **/
    public function getCountCommon(array $data = []): void
    {
        if (empty($data['select'])) {

            $data['select'] = [

                //  Main Table
                $this->getTableAlias() . '.id',
                $this->getTableAlias() . '.published_hash',
                $this->getTableAlias() . '.published_slug',
                $this->getTableAlias() . '.published_slug_end',
                $this->getTableAlias() . '.published_parent_id',
                $this->getTableAlias() . '.published_template',
                $this->getTableAlias() . '.published_template_data',
                $this->getTableAlias() . '.published_template_options',
                $this->getTableAlias() . '.published_title',
                $this->getTableAlias() . '.published_breadcrumbs',
                $this->getTableAlias() . '.published_seo_title',
                $this->getTableAlias() . '.published_seo_description',
                $this->getTableAlias() . '.published_seo_keywords',
                $this->getTableAlias() . '.draft_hash',
                $this->getTableAlias() . '.draft_slug',
                $this->getTableAlias() . '.draft_slug_end',
                $this->getTableAlias() . '.draft_parent_id',
                $this->getTableAlias() . '.draft_template',
                $this->getTableAlias() . '.draft_template_data',
                $this->getTableAlias() . '.draft_template_options',
                $this->getTableAlias() . '.draft_title',
                $this->getTableAlias() . '.draft_breadcrumbs',
                $this->getTableAlias() . '.draft_seo_title',
                $this->getTableAlias() . '.draft_seo_description',
                $this->getTableAlias() . '.draft_seo_keywords',
                $this->getTableAlias() . '.is_published',
                $this->getTableAlias() . '.is_deleted',
                $this->getTableAlias() . '.created',
                $this->getTableAlias() . '.created_by',
                $this->getTableAlias() . '.modified',
                $this->getTableAlias() . '.modified_by',

                //  Join table
                'ue.email',
                'u.first_name',
                'u.last_name',
                'u.profile_img',
                'u.gender',
            ];
        }

        $oDb = Factory::service('Database');
        $oDb->join(NAILS_DB_PREFIX . 'user u', 'u.id = ' . $this->getTableAlias() . '.modified_by', 'LEFT');
        $oDb->join(NAILS_DB_PREFIX . 'user_email ue', 'ue.user_id = u.id AND ue.is_primary = 1', 'LEFT');

        if (empty($data['sort'])) {

            $data['sort'] = [$this->getTableAlias() . '.draft_slug', 'asc'];
        }

        if (!empty($data['keywords'])) {

            if (empty($data['or_like'])) {

                $data['or_like'] = [];
            }

            $data['or_like'][] = [
                'column' => $this->getTableAlias() . '.draft_title',
                'value'  => $data['keywords'],
            ];
            $data['or_like'][] = [
                'column' => $this->getTableAlias() . '.draft_template_data',
                'value'  => $data['keywords'],
            ];
        }

        parent::getCountCommon($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Gets all pages, nested
     *
     * @param boolean $useDraft Whether to use the published or draft version of pages
     *
     * @return array
     */
    public function getAllNested($useDraft = true)
    {
        return $this->nestPages($this->getAll(), null, $useDraft);
    }

    // --------------------------------------------------------------------------

    /**
     * Get all pages nested, but as a flat array
     *
     * @param string  $sSeparator               The separator to use between pages
     * @param boolean $bMurderParentsOfChildren Whether to include parents in the result
     *
     * @return array
     */
    public function getAllNestedFlat($sSeparator = null, $bMurderParentsOfChildren = true)
    {
        $sSeparator = $sSeparator ?: ' &rsaquo; ';
        $aOut       = [];
        $aPages     = $this->getAll();

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
     * @param array   &$list     The pages to nest
     * @param int      $parentId The parent ID of the page
     * @param boolean  $useDraft Whether to use published data or draft data
     *
     * @return array
     */
    protected function nestPages(&$list, $parentId = null, $useDraft = true)
    {
        $result = [];

        for ($i = 0, $c = count($list); $i < $c; $i++) {

            $curParentId = $useDraft ? $list[$i]->draft->parent_id : $list[$i]->published->parent_id;

            if ($curParentId == $parentId) {

                $list[$i]->children = $this->nestPages($list, $list[$i]->id, $useDraft);
                $result[]           = $list[$i];
            }
        }

        return $result;
    }

    // --------------------------------------------------------------------------

    /**
     * Find the parents of a page
     *
     * @param int        $parentId   The page to find parents for
     * @param \stdClass &$source     The source page
     * @param string     $sSeparator The separator to use
     *
     * @return string
     */
    protected function findParents($parentId, &$source, $sSeparator)
    {
        if (!$parentId) {

            //  No parent ID, end of the line señor!
            return '';

        } else {

            //  There is a parent, look for it
            foreach ($source as $src) {

                if ($src->id == $parentId) {

                    $parent = $src;
                }
            }

            if (isset($parent) && $parent) {

                //  Parent was found, does it have any parents?
                if ($parent->draft->parent_id) {

                    //  Yes it does, repeat!
                    $return = $this->findParents($parent->draft->parent_id, $source, $sSeparator);

                    return $return ? $return . $parent->draft->title . $sSeparator : $parent->draft->title;

                } else {

                    //  Nope, end of the line mademoiselle
                    return $parent->draft->title . $sSeparator;
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
    public function getIdsOfChildren($iPageId, $sFormat = 'ID')
    {
        $aOut = [];
        $oDb  = Factory::service('Database');

        $oDb->select('id,draft_slug,draft_title,is_published');
        $oDb->where('draft_parent_id', $iPageId);
        $aChildren = $oDb->get(NAILS_DB_PREFIX . 'cms_page')->result();

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
     * @return array
     */
    public function getAllFlat($iPage = null, $iPerPage = null, array $aData = [], $bIncludeDeleted = false)
    {
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
     * @param int   $page           The page number of the results, if null then no pagination
     * @param int   $perPage        How many items per page of paginated results
     * @param mixed $data           Any data to pass to getCountCommon()
     * @param bool  $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted
     *                              items
     *
     * @return array
     */
    public function getTopLevel($page = null, $perPage = null, $data = [], $includeDeleted = false)
    {
        if (empty($data['where'])) {

            $data['were'] = [];
        }

        if (!empty($data['useDraft'])) {

            $data['where'][] = ['draft_parent_id', null];

        } else {

            $data['where'][] = ['published_parent_id', null];
        }

        return $this->getAll($page, $perPage, $data, $includeDeleted);
    }

    // --------------------------------------------------------------------------

    /**
     * Get the siblings of a page, i.e those with the same parent
     *
     * @param int     $id       The page whose siblings to fetch
     * @param boolean $useDraft Whether to use published data, or draft data
     *
     * @return array
     */
    public function getSiblings($id, $useDraft = true)
    {
        $page = $this->getById($id);

        if (!$page) {
            return [];
        }

        if (empty($data['where'])) {
            $data['were'] = [];
        }

        if ($useDraft) {

            $data['where'][] = ['draft_parent_id', $page->draft->parent_id];

        } else {

            $data['where'][] = ['published_parent_id', $page->published->parent_id];
        }

        return $this->getAll(null, null, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Get the ID of the configured homepage
     *
     * @return integer
     */
    public function getHomepageId()
    {
        return appSetting('homepage', 'nails/module-cms');
    }

    // --------------------------------------------------------------------------

    /**
     * Get the page marked as the homepage
     *
     * @return mixed stdClass on success, false on failure
     */
    public function getHomepage()
    {
        $iHomepageId = $this->getHomepageId();

        if (empty($iHomepageId)) {
            return false;
        }

        $oPage = $this->getById($iHomepageId);

        if (!$oPage) {

            return false;
        }

        return $oPage;
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a single object
     *
     * The getAll() method iterates over each returned item with this method so as to
     * correctly format the output. Use this to cast integers and booleans and/or organise data into objects.
     *
     * @param object $oObj      A reference to the object being formatted.
     * @param array  $aData     The same data array which is passed to _getcount_common, for reference if needed
     * @param array  $aIntegers Fields which should be cast as integers if numerical and not null
     * @param array  $aBools    Fields which should be cast as booleans if not null
     * @param array  $aFloats   Fields which should be cast as floats if not null
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

        //  Loop properties and sort into published data and draft data
        $oObj->published = new \stdClass();
        $oObj->draft     = new \stdClass();

        foreach ($oObj as $property => $value) {

            preg_match('/^(published|draft)_(.*)$/', $property, $match);

            if (!empty($match[1]) && !empty($match[2]) && $match[1] == 'published') {

                $oObj->published->{$match[2]} = $value;
                unset($oObj->{$property});

            } elseif (!empty($match[1]) && !empty($match[2]) && $match[1] == 'draft') {

                $oObj->draft->{$match[2]} = $value;
                unset($oObj->{$property});
            }
        }

        //  Other data
        $oObj->published->depth = count(explode('/', $oObj->published->slug)) - 1;
        $oObj->published->url   = siteUrl($oObj->published->slug);
        $oObj->draft->depth     = count(explode('/', $oObj->draft->slug)) - 1;
        $oObj->draft->url       = siteUrl($oObj->draft->slug);

        //  Decode JSON
        $oObj->published->template_data    = json_decode($oObj->published->template_data);
        $oObj->draft->template_data        = json_decode($oObj->draft->template_data);
        $oObj->published->template_options = json_decode($oObj->published->template_options);
        $oObj->draft->template_options     = json_decode($oObj->draft->template_options);
        $oObj->published->breadcrumbs      = json_decode($oObj->published->breadcrumbs) ?: [];
        $oObj->draft->breadcrumbs          = json_decode($oObj->draft->breadcrumbs) ?: [];

        //  Unpublished changes?
        $oObj->has_unpublished_changes = $oObj->is_published && $oObj->draft->hash != $oObj->published->hash;

        // --------------------------------------------------------------------------

        //  Owner
        $modifiedBy = (int) (is_object($oObj->modified_by) ? $oObj->modified_by->id : $oObj->modified_by);

        $oObj->modified_by              = new \stdClass();
        $oObj->modified_by->id          = $modifiedBy;
        $oObj->modified_by->first_name  = isset($oObj->first_name) ? $oObj->first_name : '';
        $oObj->modified_by->last_name   = isset($oObj->last_name) ? $oObj->last_name : '';
        $oObj->modified_by->email       = isset($oObj->email) ? $oObj->email : '';
        $oObj->modified_by->profile_img = isset($oObj->profile_img) ? $oObj->profile_img : '';
        $oObj->modified_by->gender      = isset($oObj->gender) ? $oObj->gender : '';

        unset($oObj->first_name);
        unset($oObj->last_name);
        unset($oObj->email);
        unset($oObj->profile_img);
        unset($oObj->gender);
        unset($oObj->template_data);
        unset($oObj->template_options);

        // --------------------------------------------------------------------------

        //  SEO Title; If not set then fallback to the page title
        if (empty($oObj->seo_title) && !empty($oObj->title)) {
            $oObj->seo_title = $oObj->title;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a page and it's children
     *
     * @param int $iId The ID of the page to delete
     *
     * @return boolean
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
        $oDb->trans_begin();

        try {

            $oDb->where('id', $iId);
            $oDb->set('is_deleted', true);
            $oDb->set('modified', 'NOW()', false);

            if (isLoggedIn()) {
                $oDb->set('modified_by', activeUser('id'));
            }

            if (!$oDb->update($this->getTableName())) {
                throw new NailsException('Failed to delete item');
            }

            //  Success, update children
            $aChildren = $this->getIdsOfChildren($iId);

            if ($aChildren) {

                $oDb->where_in('id', $aChildren);
                $oDb->set('is_deleted', true);
                $oDb->set('modified', 'NOW()', false);

                if (isLoggedIn()) {
                    $oDb->set('modified_by', activeUser('id'));
                }

                if (!$oDb->update($this->getTableName())) {
                    throw new NailsException('Unable to delete children pages');
                }

            }

            $oDb->trans_commit();

        } catch (\Exception $e) {
            $oDb->trans_rollback();
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
     * @param int $id The ID of the page to destroy
     *
     * @return boolean
     */
    public function destroy($id): bool
    {
        //  @TODO: implement this?
        $this->setError('It is not possible to destroy pages using this system.');

        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the URL of a page
     *
     * @param integer $iPageId      The ID of the page to look up
     * @param boolean $usePublished Whether to use the `published` data, or the `draft` data
     *
     * @return mixed                 String on success, false on failure
     */
    public function getUrl($iPageId, $usePublished = true)
    {
        $page = $this->getById($iPageId);

        if ($page) {

            return $usePublished ? $page->published->url : $page->draft->url;

        } else {

            return false;
        }
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
}
