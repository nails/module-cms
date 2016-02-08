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

use Nails\Factory;
use Nails\Common\Model\Base;

class Page extends Base
{
    protected $oDb;

    // --------------------------------------------------------------------------

    /**
     * Constuct the model
     */
    public function __construct()
    {
        parent::__construct();

        Factory::helper('directory');

        $this->oDb               = Factory::service('Database');
        $this->table             = NAILS_DB_PREFIX . 'cms_page';
        $this->tablePreview      = $this->table . '_preview';
        $this->tablePrefix       = 'p';
        $this->destructiveDelete = false;
        $this->defaultSortColumn = null;
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new CMS page
     * @param  array  $aData The data to create the page with
     * @return mixed         The ID of the page on success, false on failure
     */
    public function create($aData)
    {
        $this->oDb->trans_begin();

        //  Create a new blank row to work with
        $iId = parent::create();

        if (!$iId) {

            $this->setError('Unable to create base page object. ' . $this->lastError());
            $this->oDb->trans_rollback();
            return false;
        }

        //  Try and update it depending on how the update went, commit & update or rollback
        if ($this->update($iId, $aData)) {

            $this->oDb->trans_commit();
            return $iId;

        } else {

            $this->oDb->trans_rollback();
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Update a CMS page
     * @param  int     $iPageId The ID of the page to update
     * @param  array   $data   The data to update with
     * @return boolean
     */
    public function update($iPageId, $aData)
    {
        //  Fetch the current version of this page, for reference.
        $oCurrent = $this->getById($iPageId);

        if (!$oCurrent) {

            $this->setError('Invalid Page ID');
            return false;
        }

        // --------------------------------------------------------------------------

        //  Start the transaction
        $this->oDb->trans_begin();

        // --------------------------------------------------------------------------

        //  Start prepping the data which doesn't require much thinking
        $aUpdateData = array(
            'draft_parent_id' => !empty($aData['parent_id']) ? (int) $aData['parent_id'] : null,
            'draft_title' => !empty($aData['title']) ? trim($aData['title']) : 'Untitled',
            'draft_seo_title' => !empty($aData['seo_title']) ? trim($aData['seo_title']) : '',
            'draft_seo_description' => !empty($aData['seo_description']) ? trim($aData['seo_description']) : '',
            'draft_seo_keywords' => !empty($aData['seo_keywords']) ? trim($aData['seo_keywords']) : '',
            'draft_template' => !empty($aData['template']) ? trim($aData['template']) : null,
            'draft_template_data' => !empty($aData['template_data']) ? trim($aData['template_data']) : null,
            'draft_template_options' => !empty($aData['template_options']) ? trim($aData['template_options']) : null
        );

        // --------------------------------------------------------------------------

        /**
         * Additional sanitising; encode HTML entities. Also encode the pipe character
         * in the title, so that it doesn't break our explode
         */

        $iFlag = ENT_COMPAT | ENT_HTML401;
        $aUpdateData['draft_title'] = htmlentities(str_replace('|', '&#124;', $aUpdateData['draft_title']), $iFlag, 'UTF-8', false);
        $aUpdateData['draft_seo_title'] = htmlentities($aUpdateData['draft_seo_title'], $iFlag, 'UTF-8', false);
        $aUpdateData['draft_seo_description'] = htmlentities($aUpdateData['draft_seo_description'], $iFlag, 'UTF-8', false);
        $aUpdateData['draft_seo_keywords'] = htmlentities($aUpdateData['draft_seo_keywords'], $iFlag, 'UTF-8', false);

        // --------------------------------------------------------------------------

        //  Prep data which requires a little more intensive processing

        //  There is a parent, get some basics about it for use below
        if ($aUpdateData['draft_parent_id']) {

            $this->oDb->select('draft_slug, draft_breadcrumbs');
            $this->oDb->where('id', $aUpdateData['draft_parent_id']);
            $oParent = $this->db->get($this->table)->row();

            if (!$oParent) {

                $this->setError('Invalid Parent ID.');
                $this->oDb->trans_rollback();
                return false;
            }
        }

        $sSlugPrefix = !empty($oParent) ? $oParent->draft_slug . '/' : '';

        //  Work out the slug
        if (empty($aData['slug']) || $this->table === $this->tablePreview) {

            $aUpdateData['draft_slug'] = $this->generateSlug(
                $aUpdateData['draft_title'],
                $sSlugPrefix,
                '',
                null,
                'draft_slug',
                $oCurrent->id
            );

        } else {

            //  Test slug is valid
            $aUpdateData['draft_slug'] = $sSlugPrefix . $aData['slug'];
            $this->db->where('draft_slug', $aUpdateData['draft_slug']);
            $this->db->where('id !=', $oCurrent->id);
            if ($this->db->count_all_results($this->table)) {

                $this->setError('Slug is already in use.');
                $this->oDb->trans_rollback();
                return false;
            }
        }

        $aUpdateData['draft_slug_end'] = end(
            explode(
                '/',
                $aUpdateData['draft_slug']
            )
        );

        // --------------------------------------------------------------------------

        //  Generate the breadcrumbs
        $aUpdateData['draft_breadcrumbs'] = array();

        if (!empty($oParent->draft_breadcrumbs)) {

            $aUpdateData['draft_breadcrumbs'] = json_decode($oParent->draft_breadcrumbs);
        }

        $oTemp        = new \stdClass();
        $oTemp->id    = $oCurrent->id;
        $oTemp->title = $aUpdateData['draft_title'];
        $oTemp->slug  = $aUpdateData['draft_slug'];

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
                $aUpdateData  = array();
                $aParentCache = array(
                    $oCurrent->id => array(
                        'slug'  => $oCurrent->draft->slug,
                        'crumb' => $oCurrent->draft->breadcrumbs
                    )
                );

                if ($aChildren) {

                    /**
                     * For each child we need to update it's slug and it's breadcrumbs. We'll do this by appending
                     * it's details onto the parent's slug/breadcrumbs. If we don't know the parent's details
                     * (shouldn't happen as kids will be in a hierarchial order) then we need to look it up.
                     */
                    foreach ($aChildren as $iChildId) {

                        $oChild = $this->getById($iChildId);
                        if (!$oChild) {
                            continue;
                        }

                        $aParentCache[$oChild->id] = array('slug' => '', 'crumb' => '');

                        $oChildSlug = $aParentCache[$oChild->draft->parent_id]['slug'] . '/' . $oChild->draft->slug_end;
                        $aParentCache[$oChild->id]['slug'] = $oChildSlug;

                        $oChildCrumb        = new \stdClass();
                        $oChildCrumb->id    = $oChild->id;
                        $oChildCrumb->title = $oChild->draft->title;
                        $oChildCrumb->slug  = $oChildSlug;

                        $aChildCrumbs = $aParentCache[$oChild->draft->parent_id]['crumb'];
                        array_push($aChildCrumbs, $oChildCrumb);

                        $aParentCache[$oChild->id]['crumb'] = $aChildCrumbs;
                        $aUpdateData[$oChild->id] = $aParentCache[$oChild->id];
                    }

                    //  Update each child
                    foreach ($aUpdateData as $iPageId => $aCache) {

                        $aData = array(
                            'draft_slug' => $aCache['slug'],
                            'draft_breadcrumbs' => json_encode($aCache['crumb'])
                        );

                        if (!parent::update($iPageId, $aData)) {

                            $this->setError('Failed to update child page\'s slug and breadcrumbs');
                            $this->oDb->trans_rollback();
                            return false;
                        }
                    }
                }
            }

            // --------------------------------------------------------------------------

            //  Finish up.
            $this->oDb->trans_commit();
            return true;

        } else {

            $this->setError('Failed to update page object.');
            $this->oDb->trans_rollback();
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Create a page as normal but do so in the preview table.
     * @param  array $aData The data to create the preview with
     * @return object
     */
    public function createPreview($aData)
    {
        $sTableName  = $this->table;
        $this->table = $this->tablePreview;
        $oResult     = $this->create($aData);
        $this->table = $sTableName;

        return $oResult;
    }

    // --------------------------------------------------------------------------

    /**
     * Render a template with the provided widgets and additional data
     * @param  string $sTemplate        The template to render
     * @param  array  $oTemplateData    The template data (i.e. areas and widgets)
     * @param  array  $oTemplateOptions The template options
     * @return mixed                    String (the rendered template) on success, false on failure
     */
    public function render($sTemplate, $oTemplateData = array(), $oTemplateOptions = array())
    {
        $oTemplateModel = Factory::model('Template', 'nailsapp/module-cms');
        $oTemplate      = $oTemplateModel->getBySlug($sTemplate, 'RENDER');

        if (!$oTemplate) {
            $this->setError('"' . $sTemplate .'" is not a valid template.');
            return false;
        }

        return $oTemplate->render((array) $oTemplateData, (array) $oTemplateOptions);
    }

    // --------------------------------------------------------------------------

    /**
     * Publish a page
     * @param  int     $iId The ID of the page to publish
     * @return boolean
     */
    public function publish($iId)
    {
        //  Check the page is valid
        $oPage = $this->getById($iId);
        $oDate = Factory::factory('DateTime');

        if (!$oPage) {

            $this->_set_message('Invalid Page ID');
            return false;
        }

        // --------------------------------------------------------------------------

        //  Start the transaction
        $this->oDb->trans_begin();

        // --------------------------------------------------------------------------

        //  If the slug has changed add an entry to the slug history page
        $aSlugHistory = array();
        if ($oPage->published->slug && $oPage->published->slug != $oPage->draft->slug) {
            $aSlugHistory[] = array(
                'slug'    => $oPage->published->slug,
                'page_id' => $oPage->id
            );
        }

        // --------------------------------------------------------------------------

        //  Update the published_* columns to be the same as the draft columns
        $this->oDb->set('published_hash', 'draft_hash', false);
        $this->oDb->set('published_parent_id', 'draft_parent_id', false);
        $this->oDb->set('published_slug', 'draft_slug', false);
        $this->oDb->set('published_slug_end', 'draft_slug_end', false);
        $this->oDb->set('published_template', 'draft_template', false);
        $this->oDb->set('published_template_data', 'draft_template_data', false);
        $this->oDb->set('published_template_options', 'draft_template_options', false);
        $this->oDb->set('published_title', 'draft_title', false);
        $this->oDb->set('published_breadcrumbs', 'draft_breadcrumbs', false);
        $this->oDb->set('published_seo_title', 'draft_seo_title', false);
        $this->oDb->set('published_seo_description', 'draft_seo_description', false);
        $this->oDb->set('published_seo_keywords', 'draft_seo_keywords', false);
        $this->oDb->set('is_published', true);
        $this->oDb->set('modified', $oDate->format('Y-m-d H:i:s'));

        if ($this->user_model->isLoggedIn()) {
            $this->oDb->set('modified_by', activeUser('id'));
        }

        $this->oDb->where('id', $oPage->id);

        if ($this->oDb->update($this->table)) {

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
                        $aSlugHistory[] = array(
                            'slug'    => $oChild->draft->slug,
                            'page_id' => $oChild->id
                        );
                    }

                    //  Next we set the appropriate fields
                    $this->oDb->set('published_slug', $oChild->draft->slug);
                    $this->oDb->set('published_slug_end', $oChild->draft->slug_end);
                    $this->oDb->set('published_breadcrumbs', json_encode($oChild->draft->breadcrumbs));
                    $this->oDb->set('modified', $oDate->format('Y-m-d H:i:s'));

                    $this->oDb->where('id', $oChild->id);

                    if (!$this->oDb->update($this->table)) {

                        $this->setError('Failed to update a child page\'s data.');
                        $this->oDb->trans_rollback();
                        return false;
                    }
                }
            }

            //  Add any slug_history thingmys
            foreach ($aSlugHistory as $item) {

                $this->oDb->set('hash', md5($item['slug'] . $item['page_id']));
                $this->oDb->set('slug', $item['slug']);
                $this->oDb->set('page_id', $item['page_id']);
                $this->oDb->set('created', 'NOW()', false);
                $this->oDb->replace(NAILS_DB_PREFIX . 'cms_page_slug_history');
            }

            // --------------------------------------------------------------------------

            //  Rewrite routes
            $oRoutesModel = Factory::model('Routes');
            $oRoutesModel->update();

            // --------------------------------------------------------------------------

            //  Regenerate sitemap
            if (isModuleEnabled('nailsapp/module-sitemap')) {

                $this->load->model('sitemap/sitemap_model');
                $this->sitemap_model->generate();
            }

            $this->oDb->trans_commit();

            //  @TODO: Kill caches for this page and all children
            return true;

        } else {

            $this->oDb->trans_rollback();
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Applies common conditionals
     *
     * This method applies the conditionals which are common across the get_*()
     * methods and the count() method.
     * @param array $data Data passed from the calling method
     * @return void
     **/
    public function getCountCommon($data = array())
    {
        if (empty($data['select'])) {

            $data['select'] = array(

                //  Main Table
                $this->tablePrefix . '.id',
                $this->tablePrefix . '.published_hash',
                $this->tablePrefix . '.published_slug',
                $this->tablePrefix . '.published_slug_end',
                $this->tablePrefix . '.published_parent_id',
                $this->tablePrefix . '.published_template',
                $this->tablePrefix . '.published_template_data',
                $this->tablePrefix . '.published_template_options',
                $this->tablePrefix . '.published_title',
                $this->tablePrefix . '.published_breadcrumbs',
                $this->tablePrefix . '.published_seo_title',
                $this->tablePrefix . '.published_seo_description',
                $this->tablePrefix . '.published_seo_keywords',
                $this->tablePrefix . '.draft_hash',
                $this->tablePrefix . '.draft_slug',
                $this->tablePrefix . '.draft_slug_end',
                $this->tablePrefix . '.draft_parent_id',
                $this->tablePrefix . '.draft_template',
                $this->tablePrefix . '.draft_template_data',
                $this->tablePrefix . '.draft_template_options',
                $this->tablePrefix . '.draft_title',
                $this->tablePrefix . '.draft_breadcrumbs',
                $this->tablePrefix . '.draft_seo_title',
                $this->tablePrefix . '.draft_seo_description',
                $this->tablePrefix . '.draft_seo_keywords',
                $this->tablePrefix . '.is_published',
                $this->tablePrefix . '.is_deleted',
                $this->tablePrefix . '.created',
                $this->tablePrefix . '.created_by',
                $this->tablePrefix . '.modified',
                $this->tablePrefix . '.modified_by',

                //  Join table
                'ue.email',
                'u.first_name',
                'u.last_name',
                'u.profile_img',
                'u.gender'
            );
        }

        $this->oDb->join(NAILS_DB_PREFIX . 'user u', 'u.id = ' . $this->tablePrefix . '.modified_by', 'LEFT');
        $this->oDb->join(NAILS_DB_PREFIX . 'user_email ue', 'ue.user_id = u.id AND ue.is_primary = 1', 'LEFT');

        if (empty($data['sort'])) {

            $data['sort'] = array($this->tablePrefix . '.draft_slug', 'asc');
        }

        if (!empty($data['keywords'])) {

            if (empty($data['or_like'])) {

                $data['or_like'] = array();
            }

            $data['or_like'][] = array(
                'column' => $this->tablePrefix . '.draft_title',
                'value'  => $data['keywords']
            );
            $data['or_like'][] = array(
                'column' => $this->tablePrefix . '.draft_template_data',
                'value'  => $data['keywords']
            );
        }

        parent::getCountCommon($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Gets all pages, nested
     * @param  boolean $useDraft Whther to use the published or draft version of pages
     * @return array
     */
    public function getAllNested($useDraft = true)
    {
        return $this->nestPages($this->getAll(), null, $useDraft);
    }

    // --------------------------------------------------------------------------

    /**
     * Get all pages nested, but as a flat array
     * @param  string  $separator               The seperator to use between pages
     * @param  boolean $murderParentsOfChildren Whether to include parents in the result
     * @return array
     */
    public function getAllNestedFlat($separator = ' &rsaquo; ', $murderParentsOfChildren = true)
    {
        $out   = array();
        $pages = $this->getAll();

        foreach ($pages as $page) {

            $out[$page->id] = $this->findParents($page->draft->parent_id, $pages, $separator) . $page->draft->title;
        }

        asort($out);

        // --------------------------------------------------------------------------

        //  Remove parents from the array if they have any children
        if ($murderParentsOfChildren) {

            foreach ($out as $key => &$page) {

                $found  = false;
                $needle = $page . $separator;

                //  Hat tip - http://uk3.php.net/manual/en/function.array-search.php#90711
                foreach ($out as $item) {

                    if (strpos($item, $needle) !== false) {

                        $found = true;
                        break;
                    }
                }

                if ($found) {

                    unset($out[$key]);
                }
            }
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Nests pages
     * Hat tip to Timur; http://stackoverflow.com/a/9224696/789224
     * @param  array   &$list    The pages to nest
     * @param  int     $parentId The parent ID of the page
     * @param  boolean $useDraft Whether to use published data or draft data
     * @return array
     */
    protected function nestPages(&$list, $parentId = null, $useDraft = true)
    {
        $result = array();

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
     * @param  int      $parentId  The page to find parents for
     * @param  stdClass &$source   The source page
     * @param  string   $separator The seperator to use
     * @return string
     */
    protected function findParents($parentId, &$source, $separator)
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
                    $return = $this->findParents($parent->draft->parent_id, $source, $separator);

                    return $return ? $return . $parent->draft->title . $separator : $parent->draft->title;

                } else {

                    //  Nope, end of the line mademoiselle
                    return $parent->draft->title . $separator;
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
     * @param  int    $iPageId The ID of the page to look at
     * @param  string $sFormat How to return the data, one of ID, ID_SLUG, ID_SLUG_TITLE or ID_SLUG_TITLE_PUBLISHED
     * @return array
     */
    public function getIdsOfChildren($iPageId, $sFormat = 'ID')
    {
        $aOut = array();

        $this->oDb->select('id,draft_slug,draft_title,is_published');
        $this->oDb->where('draft_parent_id', $iPageId);
        $aChildren = $this->oDb->get(NAILS_DB_PREFIX . 'cms_page')->result();

        if ($aChildren) {

            foreach ($aChildren as $oChild) {

                switch ($sFormat) {

                    case 'ID':
                        $aOut[] = $oChild->id;
                        break;

                    case 'ID_SLUG':
                        $aOut[] = array(
                            'id'   => $oChild->id,
                            'slug' => $oChild->draft_slug
                        );
                        break;

                    case 'ID_SLUG_TITLE':
                        $aOut[] = array(
                            'id'    => $oChild->id,
                            'slug'  => $oChild->draft_slug,
                            'title' => $oChild->draft_title
                        );
                        break;

                    case 'ID_SLUG_TITLE_PUBLISHED':
                        $aOut[] = array(
                            'id'           => $oChild->id,
                            'slug'         => $oChild->draft_slug,
                            'title'        => $oChild->draft_title,
                            'is_published' => (bool) $oChild->is_published
                        );
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
     * @param int    $page           The page number of the results, if null then no pagination
     * @param int    $perPage        How many items per page of paginated results
     * @param mixed  $data           Any data to pass to getCountCommon()
     * @param bool   $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return array
     */
    public function getAllFlat($page = null, $perPage = null, $data = array(), $includeDeleted = false)
    {
        $out   = array();
        $pages = $this->getAll($page, $perPage, $data, $includeDeleted);

        foreach ($pages as $page) {

            if (!empty($data['useDraft'])) {

                $out[$page->id] = $page->draft->title;

            } else {

                $out[$page->id] = $page->published->title;
            }
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Get the top level pages, i.e., those without a parent
     * @param int    $page           The page number of the results, if null then no pagination
     * @param int    $perPage        How many items per page of paginated results
     * @param mixed  $data           Any data to pass to getCountCommon()
     * @param bool   $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return array
     */
    public function getTopLevel($page = null, $perPage = null, $data = array(), $includeDeleted = false)
    {
        if (empty($data['where'])) {

            $data['were'] = array();
        }

        if (!empty($data['useDraft'])) {

            $data['where'][] = array('draft_parent_id', null);

        } else {

            $data['where'][] = array('published_parent_id', null);
        }

        return $this->getAll($page, $perPage, $data, $includeDeleted);
    }

    // --------------------------------------------------------------------------

    /**
     * Get the siblings of a page, i.e those with the smame parent
     * @param  int     $id       The page whose sibilings to fetch
     * @param  boolean $useDraft Whether to use published data, or draft data
     * @return array
     */
    public function getSiblings($id, $useDraft = true)
    {
        $page = $this->getById($id);

        if (!$page) {

            return array();
        }

        if (empty($data['where'])) {

            $data['were'] = array();
        }

        if (!empty($data['useDraft'])) {

            $data['where'][] = array('draft_parent_id', $page->draft->parent_id);

        } else {

            $data['where'][] = array('published_parent_id', $page->published->parent_id);
        }

        return $this->getAll(null, null, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Get the ID of the configured homepage
     * @return integer
     */
    public function getHomepageId()
    {
        return appSetting('homepage', 'nailsapp/module-cms');
    }

    // --------------------------------------------------------------------------

    /**
     * Get the page marked as the homepage
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
     * @param  object $oObj      A reference to the object being formatted.
     * @param  array  $aData     The same data array which is passed to _getcount_common, for reference if needed
     * @param  array  $aIntegers Fields which should be cast as integers if numerical and not null
     * @param  array  $aBools    Fields which should be cast as booleans if not null
     * @param  array  $aFloats   Fields which should be cast as floats if not null
     * @return void
     */
    protected function formatObject(
        &$oObj,
        $aData = array(),
        $aIntegers = array(),
        $aBools = array(),
        $aFloats = array()
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
        $oObj->published->url   = site_url($oObj->published->slug);
        $oObj->draft->depth     = count(explode('/', $oObj->draft->slug)) - 1;
        $oObj->draft->url       = site_url($oObj->draft->slug);

        //  Decode JSON
        $oObj->published->template_data      = json_decode($oObj->published->template_data);
        $oObj->draft->template_data          = json_decode($oObj->draft->template_data);
        $oObj->published->template_options = json_decode($oObj->published->template_options);
        $oObj->draft->template_options     = json_decode($oObj->draft->template_options);
        $oObj->published->breadcrumbs      = json_decode($oObj->published->breadcrumbs);
        $oObj->draft->breadcrumbs          = json_decode($oObj->draft->breadcrumbs);

        //  Unpublished changes?
        $oObj->has_unpublished_changes = $oObj->is_published && $oObj->draft->hash != $oObj->published->hash;

        // --------------------------------------------------------------------------

        //  Owner
        $modifiedBy                     = (int) $oObj->modified_by;
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
            $oObj->seo_title = $page->title;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a page and it's children
     * @param  int     $id The ID of the page to delete
     * @return boolean
     */
    public function delete($id)
    {
        $page = $this->getById($id);

        if (!$page) {

            $this->setError('Invalid page ID');
            return false;
        }

        // --------------------------------------------------------------------------

        $this->oDb->trans_begin();

        $this->oDb->where('id', $id);
        $this->oDb->set('is_deleted', true);
        $this->oDb->set('modified', 'NOW()', false);

        if ($this->user_model->isLoggedIn()) {

            $this->oDb->set('modified_by', activeUser('id'));
        }

        if ($this->oDb->update($this->table)) {

            //  Success, update children
            $children = $this->getIdsOfChildren($id);

            if ($children) {

                $this->oDb->where_in('id', $children);
                $this->oDb->set('is_deleted', true);
                $this->oDb->set('modified', 'NOW()', false);

                if ($this->user_model->isLoggedIn()) {

                    $this->oDb->set('modified_by', activeUser('id'));
                }

                if (!$this->oDb->update($this->table)) {

                    $this->setError('Unable to delete children pages');
                    $this->oDb->trans_rollback();
                    return false;
                }
            }

            // --------------------------------------------------------------------------

            //  Rewrite routes
            $oRoutesModel = Factory::model('Routes');
            $oRoutesModel->update();

            // --------------------------------------------------------------------------

            //  Regenerate sitemap
            if (isModuleEnabled('nailsapp/module-sitemap')) {

                $this->load->model('sitemap/sitemap_model');
                $this->sitemap_model->generate();
            }

            // --------------------------------------------------------------------------

            $this->oDb->trans_commit();
            return true;

        } else {

            //  Failed
            $this->oDb->trans_rollback();
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Permenantly delete a page and it's children
     * @param  int     $id The ID of the page to destroy
     * @return boolean
     */
    public function destroy($id)
    {
        //  @TODO: implement this?
        $this->setError('It is not possible to destroy pages using this system.');
        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * Get's a preview page by it's ID
     * @param  integer   $iPreviewId The Id of the preview to get
     * @return mixed                 stdClass on success, false on failure
     */
    public function getPreviewById($iPreviewId)
    {
        $this->oDb->where('id', $iPreviewId);
        $oResult = $this->oDb->get($this->tablePreview)->row();

        // --------------------------------------------------------------------------

        if (!$oResult) {

            return false;
        }

        // --------------------------------------------------------------------------

        $this->formatObject($oResult);
        return $oResult;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the URL of a page
     * @param  integer $iPageId       The ID of the page to look up
     * @param  boolean $usePublished Whether to use the `published` data, or the `draft` data
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
}
