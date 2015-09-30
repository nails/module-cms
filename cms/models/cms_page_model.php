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

class NAILS_Cms_page_model extends NAILS_Model
{
    protected $availableWidgets;
    protected $widgetsDirs;
    protected $templatesDirs;
    protected $nailsPrefix;
    protected $appPrefix;
    protected $loadedTemplates;
    protected $loadedWidgets;

    // --------------------------------------------------------------------------

    /**
     * Constuct the model
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $this->load->helper('directory');

        // --------------------------------------------------------------------------

        //  Discover templates and widgets
        $aModules = _NAILS_GET_MODULES();

        $this->widgetsDirs   = array();
        $this->templatesDirs = array();

        foreach ($aModules as $oModule) {

            $this->templatesDirs[] = $oModule->path . 'cms/templates/';
            $this->widgetsDirs[]   = $oModule->path . 'cms/widgets/';
        }

        /**
         * Load App templates and widgets afterwards so that they may override
         * the module supplied ones.
         */

        $this->templatesDirs[] = FCPATH . APPPATH . 'modules/cms/templates/';
        $this->widgetsDirs[]   = FCPATH . APPPATH . 'modules/cms/widgets/';

        // --------------------------------------------------------------------------

        $this->nailsPrefix = 'NAILS_CMS_';
        $this->appPrefix   = 'CMS_';

        $this->table         = NAILS_DB_PREFIX . 'cms_page';
        $this->table_preview =  $this->table . '_preview';
        $this->tablePrefix   = 'p';

        $this->destructiveDelete = false;
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new CMS page
     * @param  array  $aData The data to create the page with
     * @return mixed         The ID of the page on success, false on failure
     */
    public function create($aData)
    {
        if (empty($aData['data']->template)) {

            $this->_set_error('"data.template" is a required field.');
            return false;
        }

        // --------------------------------------------------------------------------

        $this->db->trans_begin();

        //  Create a new blank row to work with
        $iId = parent::create();

        if (!$iId) {

            $this->_set_error('Unable to create base page object. ' . $this->last_error());
            $this->db->trans_rollback();
            return false;
        }

        //  Try and update it depending on how the update went, commit & update or rollback
        if ($this->update($iId, $aData)) {

            $this->db->trans_commit();
            return $iId;

        } else {

            $this->db->trans_rollback();
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
        //  Check the data
        if (empty($aData['data']->template)) {

            $this->_set_error('"data.template" is a required field.');
            return false;
        }

        // --------------------------------------------------------------------------

        //  Fetch the current version of this page, for reference.
        $oCurrent = $this->get_by_id($iPageId);

        if (!$oCurrent) {

            $this->_set_error('Invalid Page ID');
            return false;
        }

        // --------------------------------------------------------------------------

        //  Start the transaction
        $this->db->trans_begin();

        // --------------------------------------------------------------------------

        //  Start prepping the data which doesn't require much thinking
        $aInsertData = array();

        $aInsertData['draft_parent_id']       = !empty($aData['data']->parent_id)       ? (int) $aData['data']->parent_id       : null;
        $aInsertData['draft_title']           = !empty($aData['data']->title)           ? trim($aData['data']->title)           : 'Untitled';
        $aInsertData['draft_seo_title']       = !empty($aData['data']->seo_title)       ? trim($aData['data']->seo_title)       : '';
        $aInsertData['draft_seo_description'] = !empty($aData['data']->seo_description) ? trim($aData['data']->seo_description) : '';
        $aInsertData['draft_seo_keywords']    = !empty($aData['data']->seo_keywords)    ? trim($aData['data']->seo_keywords)    : '';
        $aInsertData['draft_template']        = $aData['data']->template;
        $aInsertData['draft_template_data']   = json_encode($aData, JSON_UNESCAPED_SLASHES);
        $aInsertData['draft_hash']            = md5($aInsertData['draft_template_data']);

        // --------------------------------------------------------------------------

        /**
         * Additional sanitising; encode HTML entities. Also encode the pipe character
         * in the title, so that it doesn't break our explode
         */

        $aInsertData['draft_title']           = htmlentities(str_replace('|', '&#124;', $aInsertData['draft_title']), ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
        $aInsertData['draft_seo_title']       = htmlentities($aInsertData['draft_seo_title'], ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
        $aInsertData['draft_seo_description'] = htmlentities($aInsertData['draft_seo_description'], ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
        $aInsertData['draft_seo_keywords']    = htmlentities($aInsertData['draft_seo_keywords'], ENT_COMPAT | ENT_HTML401, 'UTF-8', false);

        // --------------------------------------------------------------------------

        //  Prep data which requires a little more intensive processing

        // --------------------------------------------------------------------------

        //  Work out the slug
        if ($aInsertData['draft_parent_id']) {

            //  There is a parent, so set it's slug as the prefix
            $oParent = $this->get_by_id($aInsertData['draft_parent_id']);

            if (!$oParent) {

                $this->_set_error('Invalid Parent ID.');
                $this->db->trans_rollback();
                return false;
            }

            $sPrefix = $oParent->draft->slug . '/';

        } else {

            //  No parent, no need for a prefix
            $sPrefix = '';
        }

        $aInsertData['draft_slug'] = $this->_generate_slug(
            $aInsertData['draft_title'],
            $sPrefix,
            '',
            null,
            'draft_slug',
            $oCurrent->id
        );

        $aInsertData['draft_slug_end'] = end(
            explode(
                '/', $aInsertData['draft_slug']
            )
        );

        // --------------------------------------------------------------------------

        //  Generate the breadcrumbs
        $aInsertData['draft_breadcrumbs'] = array();

        if ($aInsertData['draft_parent_id']) {

            /**
             * There is a parent, use it's breadcrumbs array as the starting point.
             * No need to fetch the parent again.
             */

            $aInsertData['draft_breadcrumbs'] = $oParent->draft->breadcrumbs;
        }

        $oTemp        = new \stdClass();
        $oTemp->id    = $oCurrent->id;
        $oTemp->title = $aInsertData['draft_title'];
        $oTemp->slug  = $aInsertData['draft_slug'];

        $aInsertData['draft_breadcrumbs'][] = $oTemp;
        unset($oTemp);

        //  Encode the breadcrumbs for the database
        $aInsertData['draft_breadcrumbs'] = json_encode($this->generateBreadcrumbs($oCurrent->id));

        // --------------------------------------------------------------------------

        if (parent::update($oCurrent->id, $aInsertData)) {

            //  Update was successful, set the breadcrumbs
            $aBreadcrumbs = $this->generateBreadcrumbs($oCurrent->id);

            $this->db->set('draft_breadcrumbs', json_encode($aBreadcrumbs));
            $this->db->where('id', $oCurrent->id);
            if (!$this->db->update($this->table)) {

                $this->_set_error('Failed to generate breadcrumbs.');
                $this->db->trans_rollback();
                return false;
            }

            //  For each child regenerate the breadcrumbs and slugs (only if the title or slug has changed)
            if ($oCurrent->draft->title != $aInsertData['draft_title'] || $oCurrent->draft->slug != $aInsertData['draft_slug']) {

                $aChildren = $this->getIdsOfChildren($oCurrent->id);

                if ($aChildren) {

                    //  Loop each child and update it's details
                    foreach ($aChildren as $iChildId) {

                        /**
                         * We can assume that the children are in a sensible order, loop
                         * them and process. For nested children, their parent will have
                         * been processed by the time we process it.
                         */

                        $oChild = $this->get_by_id($iChildId);

                        if (!$oChild) {

                            continue;
                        }

                        $aUpdateData = array();

                        //  Generate the breadcrumbs
                        $aUpdateData['draft_breadcrumbs'] = json_encode($this->generateBreadcrumbs($oChild->id));

                        //  Generate the slug
                        if ($oChild->draft->parent_id) {

                            //  Child has a parent, fetch it and use it's slug as the prefix
                            $parent = $this->get_by_id($oChild->draft->parent_id);

                            if ($parent) {

                                $aUpdateData['draft_slug'] = $parent->draft->slug . '/' . $oChild->draft->slug_end;

                            } else {

                                //  Parent is bad, make this a parent page. Poor wee orphan.
                                $aUpdateData['draft_parent_id'] = null;
                                $aUpdateData['draft_slug']      = $oChild->draft->slug_end;
                            }

                        } else {

                            //  Would be weird if this happened, but ho hum handle it anyway
                            $aUpdateData['draft_parent_id'] = null;
                            $aUpdateData['draft_slug']      = $oChild->draft->slug_end;
                        }

                        //  Update the child and move on
                        if (!parent::update($oChild->id, $aUpdateData)) {

                            $this->_set_error('Failed to update breadcrumbs and/or slug of child page.');
                            $this->db->trans_rollback();
                            return false;
                        }
                    }
                }
            }

            // --------------------------------------------------------------------------

            //  Finish up.
            $this->db->trans_commit();
            return true;

        } else {

            $this->_set_error('Failed to update page object.');
            $this->db->trans_rollback();
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Generate breadcrumbs for the page
     * @param  integer $iId The page to generate breadcrumbs for
     * @return mixed   Array of breadcrumbs, or false on failure
     */
    protected function generateBreadcrumbs($iId)
    {
        $oPage = $this->get_by_id($iId);

        if (!$oPage) {

            return false;
        }

        // --------------------------------------------------------------------------

        $aBreadcrumbs = array();

        if ($oPage->draft->parent_id) {

            $aBreadcrumbs = array_merge($aBreadcrumbs, $this->generateBreadcrumbs($oPage->draft->parent_id));
        }

        $oTemp        = new \stdClass();
        $oTemp->id    = $oPage->id;
        $oTemp->title = $oPage->draft->title;

        $aBreadcrumbs[] = $oTemp;
        unset($oTemp);

        return $aBreadcrumbs;
    }

    // --------------------------------------------------------------------------

    /**
     * Render a template with the provided widgets and additional data
     * @param  string $template         The template to render
     * @param  array  $widgets          The widgets to render
     * @param  array  $additionalFields Any additional fields to pass to the template
     * @return mixed                    String (the rendered template) on success, false on failure
     */
    public function render($template, $widgets = array(), $additionalFields = array())
    {
        $template = $this->getTemplate($template, 'RENDER');

        if (!$template) {

            $this->_set_error('"' . $template .'" is not a valid template.');
            return false;
        }

        // --------------------------------------------------------------------------

        //  Look for manual config items
        if (!empty($additionalFields->manual_config->assets_render)) {

            if (!is_array($additionalFields->manual_config->assets_render)) {

                $additionalFields->manual_config->assets_render = (array) $additionalFields->manual_config->assets_render;
            }

            $this->loadAssets($additionalFields->manual_config->assets_render);
        }

        // --------------------------------------------------------------------------

        //  Attempt to instantiate and render the template
        try {

            require_once $template->path . 'template.php';

            $TEMPLATE = new $template->iam();

            try {

                return $TEMPLATE->render((array) $widgets, (array) $additionalFields);

            } catch (Exception $e) {

                $this->_set_error('Could not render template "' . $template . '".');
                return false;
            }

        } catch (Exception $e) {

            $this->_set_error('Could not instantiate template "' . $template . '".');
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Publish a page
     * @param  int     $id The page to publish
     * @return boolean
     */
    public function publish($id)
    {
        //  Check the page is valid
        $page = $this->get_by_id($id);

        if (!$page) {

            $this->_set_message('Invalid Page ID');
            return false;
        }

        // --------------------------------------------------------------------------

        //  Start the transaction
        $this->db->trans_begin();

        // --------------------------------------------------------------------------

        //  If the slug has changed add an entry to the slug history page
        $slugHistory = array();
        if ($page->published->slug && $page->published->slug != $page->draft->slug) {

            $slugHistory[] = array(
                'slug'    => $page->published->slug,
                'page_id' => $id
            );
        }

        // --------------------------------------------------------------------------

        //  Update the published_* columns to be the same as the draft columns
        $this->db->set('published_hash', 'draft_hash', false);
        $this->db->set('published_parent_id', 'draft_parent_id', false);
        $this->db->set('published_slug', 'draft_slug', false);
        $this->db->set('published_slug_end', 'draft_slug_end', false);
        $this->db->set('published_template', 'draft_template', false);
        $this->db->set('published_template_data', 'draft_template_data', false);
        $this->db->set('published_title', 'draft_title', false);
        $this->db->set('published_breadcrumbs', 'draft_breadcrumbs', false);
        $this->db->set('published_seo_title', 'draft_seo_title', false);
        $this->db->set('published_seo_description', 'draft_seo_description', false);
        $this->db->set('published_seo_keywords', 'draft_seo_keywords', false);
        $this->db->set('is_published', true);
        $this->db->set('modified', date('Y-m-d H:i{s'));

        if ($this->user_model->isLoggedIn()) {

            $this->db->set('modified_by', activeUser('id'));
        }

        $this->db->where('id', $page->id);

        if ($this->db->update($this->table)) {

            //  Fetch the children, returning the data we need for the updates
            $children = $this->getIdsOfChildren($page->id);

            if ($children) {

                /**
                 * Loop each child and update it's published details, but only
                 * if they've changed.
                 */

                foreach ($children as $child_id) {

                    $child = $this->get_by_id($child_id);

                    if (!$child) {

                        continue;
                    }

                    if ($child->published->title == $child->draft->title && $child->published->slug == $child->draft->slug) {

                        continue;
                    }

                    //  First make a note of the old slug
                    if ($child->is_published) {

                        $slugHistory[] = array(
                            'slug'    => $child->draft->slug,
                            'page_id' => $child->id
                        );

                    }

                    //  Next we set the appropriate fields
                    $this->db->set('published_slug', $child->draft->slug);
                    $this->db->set('published_slug_end', $child->draft->slug_end);
                    $this->db->set('published_breadcrumbs', json_encode($child->draft->breadcrumbs));
                    $this->db->set('modified', date('Y-m-d H:i{s'));

                    $this->db->where('id', $child->id);

                    if (!$this->db->update($this->table)) {

                        $this->_set_error('Failed to update a child page\'s data.');
                        $this->db->trans_rollback();
                        return false;
                    }
                }
            }

            //  Add any slug_history thingmys
            foreach ($slugHistory as $item) {

                $this->db->set('hash', md5($item['slug'] . $item['page_id']));
                $this->db->set('slug', $item['slug']);
                $this->db->set('page_id', $item['page_id']);
                $this->db->set('created', 'NOW()', false);
                $this->db->replace(NAILS_DB_PREFIX . 'cms_page_slug_history');
            }

            // --------------------------------------------------------------------------

            //  Rewrite routes
            $this->load->model('routes_model');
            $this->routes_model->update();

            // --------------------------------------------------------------------------

            //  Regenerate sitemap
            if (isModuleEnabled('nailsapp/module-sitemap')) {

                $this->load->model('sitemap/sitemap_model');
                $this->sitemap_model->generate();
            }

            $this->db->trans_commit();

            //  @TODO: Kill caches for this page and all children
            return true;

        } else {

            $this->db->trans_rollback();
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Applies common conditionals
     *
     * This method applies the conditionals which are common across the get_*()
     * methods and the count() method.
     * @param string $data Data passed from the calling method
     * @param string $_caller The name of the calling method
     * @return void
     **/
    public function _getcount_common($data = array(), $_caller = null)
    {
        $select = array(
            $this->tablePrefix . '.id',
            $this->tablePrefix . '.published_hash',
            $this->tablePrefix . '.published_slug',
            $this->tablePrefix . '.published_slug_end',
            $this->tablePrefix . '.published_parent_id',
            $this->tablePrefix . '.published_template',
            $this->tablePrefix . '.published_template_data',
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
            $this->tablePrefix . '.draft_title',
            $this->tablePrefix . '.draft_breadcrumbs',
            $this->tablePrefix . '.draft_seo_title',
            $this->tablePrefix . '.draft_seo_description',
            $this->tablePrefix . '.draft_seo_keywords',
            $this->tablePrefix . '.is_published',
            $this->tablePrefix . '.is_deleted',
            $this->tablePrefix . '.is_homepage',
            $this->tablePrefix . '.created',
            $this->tablePrefix . '.created_by',
            $this->tablePrefix . '.modified',
            $this->tablePrefix . '.modified_by'
        );

        $this->db->select($select);
        $this->db->select('ue.email, u.first_name, u.last_name, u.profile_img, u.gender');

        $this->db->join(NAILS_DB_PREFIX . 'user u', 'u.id = ' . $this->tablePrefix . '.modified_by', 'LEFT');
        $this->db->join(NAILS_DB_PREFIX . 'user_email ue', 'ue.user_id = u.id AND ue.is_primary = 1', 'LEFT');


        if (empty($data['sort'])) {

            $data['sort'] = array($this->tablePrefix . '.draft_slug', 'asc');
        }

        parent::_getcount_common($data, $_caller);
    }

    // --------------------------------------------------------------------------

    /**
     * Gets all pages, nested
     * @param  boolean $useDraft Whther to use the published or draft version of pages
     * @return array
     */
    public function getAllNested($useDraft = true)
    {
        return $this->nestPages($this->get_all(), null, $useDraft);
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
        $pages = $this->get_all();

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

        $this->db->select('id,draft_slug,draft_title,is_published');
        $this->db->where('draft_parent_id', $iPageId);
        $aChildren = $this->db->get(NAILS_DB_PREFIX . 'cms_page')->result();

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
     * @param mixed  $data           Any data to pass to _getcount_common()
     * @param bool   $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @param string $_caller        Internal flag to pass to _getcount_common(), contains the calling method
     * @return array
     */
    public function get_all_flat($page = null, $perPage = null, $data = array(), $includeDeleted = false, $_caller = 'GET_ALL_FLAT')
    {
        $out   = array();
        $pages = $this->get_all($page, $perPage, $data, $includeDeleted, $_caller);

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
     * @param mixed  $data           Any data to pass to _getcount_common()
     * @param bool   $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @param string $_caller        Internal flag to pass to _getcount_common(), contains the calling method
     * @return array
     */
    public function getTopLevel($page = null, $perPage = null, $data = array(), $includeDeleted = false, $_caller = 'GET_TOP_LEVEL')
    {
        if (empty($data['where'])) {

            $data['were'] = array();
        }

        if (!empty($data['useDraft'])) {

            $data['where'][] = array('draft_parent_id', null);

        } else {

            $data['where'][] = array('published_parent_id', null);
        }

        return $this->get_all($page, $perPage, $data, $includeDeleted, $_caller);
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
        $page = $this->get_by_id($id);

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

        return $this->get_all(null, null, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Get the page marked as the homepage
     * @return mixed stdClass on success, false on failure
     */
    public function getHomepage()
    {
        $data = array(
            'where' => array(
                array($this->tablePrefix . '.is_homepage', true)
            )
        );

        $page = $this->get_all(null, null, $data);

        if (!$page) {

            return false;
        }

        return $page[0];
    }

    // --------------------------------------------------------------------------

    /**
     * Format a page object
     * @param  stdClass &$page The page to format
     * @return void
     */
    protected function _format_object(&$page, $data = array())
    {
        $integers = array();

        $booleans = array(
            'is_homepage'
        );

        parent::_format_object($page, $data, $integers, $booleans);

        //  Loop properties and sort into published data and draft data
        $page->published = new \stdClass();
        $page->draft     = new \stdClass();

        foreach ($page as $property => $value) {

            preg_match('/^(published|draft)_(.*)$/', $property, $match);

            if (!empty($match[1]) && !empty($match[2]) && $match[1] == 'published') {

                $page->published->{$match[2]} = $value;
                unset($page->{$property});

            } elseif (!empty($match[1]) && !empty($match[2]) && $match[1] == 'draft') {

                $page->draft->{$match[2]} = $value;
                unset($page->{$property});
            }
        }

        //  Other data
        $page->published->depth = count(explode('/', $page->published->slug)) - 1;
        $page->published->url   = site_url($page->published->slug);
        $page->draft->depth     = count(explode('/', $page->draft->slug)) - 1;
        $page->draft->url       = site_url($page->draft->slug);

        //  Decode JSON
        $page->published->template_data = json_decode($page->published->template_data);
        $page->draft->template_data     = json_decode($page->draft->template_data);
        $page->published->breadcrumbs   = json_decode($page->published->breadcrumbs);
        $page->draft->breadcrumbs       = json_decode($page->draft->breadcrumbs);

        //  Unpublished changes?
        $page->has_unpublished_changes = $page->is_published && $page->draft->hash != $page->published->hash;

        // --------------------------------------------------------------------------

        //  Owner
        $modifiedBy                     = (int) $page->modified_by;
        $page->modified_by              = new \stdClass();
        $page->modified_by->id          = $modifiedBy;
        $page->modified_by->first_name  = isset($page->first_name) ? $page->first_name : '';
        $page->modified_by->last_name   = isset($page->last_name) ? $page->last_name : '';
        $page->modified_by->email       = isset($page->email) ? $page->email : '';
        $page->modified_by->profile_img = isset($page->profile_img) ? $page->profile_img : '';
        $page->modified_by->gender      = isset($page->gender) ? $page->gender : '';

        unset($page->first_name);
        unset($page->last_name);
        unset($page->email);
        unset($page->profile_img);
        unset($page->gender);
        unset($page->template_data);

        // --------------------------------------------------------------------------

        //  SEO Title; If not set then fallback to the page title
        if (empty($page->seo_title) && !empty($page->title)) {

            $page->seo_title = $page->title;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Get all available widgets to the system
     * @param  string $loadAssets Whether or not to load widget's assets, and
     *                            if so whether EDITOR or RENDER assets.
     * @return array
     */
    public function getAvailableWidgets($loadAssets = false)
    {
        if (!empty($this->loadedWidgets)) {

            return $this->loadedWidgets;
        }

        $aAvailableWidgets = array();

        foreach ($this->widgetsDirs as $sDir) {

            if (is_dir($sDir)) {

                $aWidgets = directory_map($sDir);

                foreach ($aWidgets as $sWidgetDir => $aWidgetFiles) {

                    if (is_file($sDir . $sWidgetDir . '/widget.php')) {

                        $aAvailableWidgets[$sWidgetDir] = array(
                            'path' => $sDir,
                            'name' => $sWidgetDir
                        );
                    }
                }
            }
        }

        //  Instantiate widgets
        $aLoadedWidgets = array();
        foreach ($aAvailableWidgets as $aWidget) {

            include_once $aWidget['path'] . $aWidget['name'] . '/widget.php';

            $sClassName = '\Nails\Cms\Widget\\' . ucfirst(strtolower($aWidget['name']));

            if (!class_exists($sClassName)) {

                log_message(
                    'error',
                    'CMS Template discovered at "' . $aWidget['path'] . $aWidget['name'] .
                    '" but does not contain class "' . $sClassName . '"'
                );

            } elseif (!empty($sClassName::isDisabled())) {

                /**
                 * This template is disabled, ignore this template. Don't log
                 * anything as it's likely a developer override to hide a default
                 * template.
                 */

            } else {

                $aLoadedWidgets[$aWidget['name']] = new $sClassName();

                //  Load the template's assets if requested
                if ($loadAssets) {

                    $aAssets = $aLoadedWidgets[$aWidget['name']]->getAssets($loadAssets);
                    $this->loadAssets($aAssets);
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Sort the widgets into their sub groupings and then alphabetically
        $aOut          = array();
        $aGeneric      = array();
        $sGenericLabel = 'Generic';

        foreach ($aLoadedWidgets as $sWidgetSlug => $oWidget) {

            $sWidgetGrouping = $oWidget->getGrouping();

            if (!empty($sWidgetGrouping)) {

                $sKey = md5($sWidgetGrouping);

                if (!isset($aOut[$sKey])) {

                    $aOut[$sKey] = \Nails\Factory::factory('WidgetGroup', 'nailsapp/module-cms');
                    $aOut[$sKey]->setLabel($sWidgetGrouping);
                }

                $aOut[$sKey]->add($oWidget);

            } else {

                $sKey = md5($sGenericLabel);

                if (!isset($aGeneric[$sKey])) {

                    $aGeneric[$sKey] = \Nails\Factory::factory('WidgetGroup', 'nailsapp/module-cms');
                    $aGeneric[$sKey]->setLabel($sGenericLabel);
                }

                $aGeneric[$sKey]->add($oWidget);
            }
        }

        //  Glue generic grouping to the beginning of the array
        $aOut = array_merge($aGeneric, $aOut);
        $aOut = array_values($aOut);

        $this->loadedWidgets = $aOut;

        return $this->loadedWidgets;
    }

    // --------------------------------------------------------------------------

    /**
     * The sorting function for widgets, called by usort()
     * @param  stdClass $a The first widget
     * @param  stdClass $b The second widget
     * @return int
     */
    protected function sortWidgets($a, $b)
    {
        //  Equal?
        if (trim($a->label) == trim($b->label)) {

            return 0;
        }

        //  Not equal, work out which takes precedence
        $sort = array($a->label, $b->label);
        sort($sort);

        return $sort[0] == $a->label ? -1 : 1;
    }

    // --------------------------------------------------------------------------

    /**
     * Get an individual widget
     * @param  string  $slug       The widget's slug
     * @param  boolean $loadAssets Whether or not to load the widget's assets
     * @return mixed               stdClass on success, false on failure
     */
    public function getWidget($slug, $loadAssets = false)
    {
        $widgets = $this->getAvailableWidgets();

        foreach ($widgets as $widget_group) {

            foreach ($widget_group->widgets as $widget) {

                if ($slug == $widget->slug) {

                    if ($loadAssets) {

                        switch ($loadAssets) {

                            case 'EDITOR':

                                $assets = $widget->assets_editor;
                                break;

                            case 'RENDER':

                                $assets = $widget->assets_render;
                                break;

                            default:

                                $assets = array();
                                break;
                        }

                        $this->loadAssets($assets);
                    }

                    return $widget;
                }
            }
        }

        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * Get all available templates to the system
     * @param  string $loadAssets Whether or not to load template's assets, and
     *                            if so whether EDITOR or RENDER assets.
     * @return array
     */
    public function getAvailableTemplates($loadAssets = '')
    {
        if (!empty($this->loadedTemplates)) {

            return $this->loadedTemplates;
        }

        $aAvailableTemplates = array();

        foreach ($this->templatesDirs as $sDir) {

            if (is_dir($sDir)) {

                $aTemplates = directory_map($sDir);

                foreach ($aTemplates as $sTemplateDir => $aTemplateFiles) {

                    if (is_file($sDir . $sTemplateDir . '/template.php')) {

                        $aAvailableTemplates[$sTemplateDir] = array(
                            'path' => $sDir,
                            'name' => $sTemplateDir
                        );
                    }
                }
            }
        }

        //  Instantiate templates
        $aLoadedTemplates = array();
        foreach ($aAvailableTemplates as $aTemplate) {

            include_once $aTemplate['path'] . $aTemplate['name'] . '/template.php';

            $sClassName = '\Nails\Cms\Template\\' . ucfirst(strtolower($aTemplate['name']));

            if (!class_exists($sClassName)) {

                log_message(
                    'error',
                    'CMS Template discovered at "' . $aTemplate['path'] . $aTemplate['name'] .
                    '" but does not contain class "' . $sClassName . '"'
                );

            } elseif ($sClassName::isDisabled()) {

                /**
                 * This template is disabled, ignore this template. Don't log
                 * anything as it's likely a developer override to hide a default
                 * template.
                 */

            } else {

                $aLoadedTemplates[$aTemplate['name']] = new $sClassName();

                //  Load the template's assets if requested
                if ($loadAssets) {

                    $aAssets = $aLoadedTemplates[$aTemplate['name']]->getAssets($loadAssets);
                    $this->loadAssets($aAssets);
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Sort into some alphabetical order and save for later
        ksort($aLoadedTemplates);

        $this->loadedTemplates = $aLoadedTemplates;

        return $this->loadedTemplates;
    }

    // --------------------------------------------------------------------------

    /**
     * Get an individual template
     * @param  string  $slug       The template's slug
     * @param  boolean $loadAssets Whether or not to load the template's assets
     * @return mixed               stdClass on success, false on failure
     */
    public function getTemplate($slug, $loadAssets = false)
    {
        $templates = $this->getAvailableTemplates();

        foreach ($templates as $template) {

            if ($slug == $template->slug) {

                if ($loadAssets) {

                    switch ($loadAssets) {

                        case 'EDITOR':

                            $assets = $template->assets_editor;
                            break;

                        case 'RENDER':

                            $assets = $template->assets_render;
                            break;

                        default:

                            $assets = array();
                            break;
                    }

                    $this->loadAssets($assets);
                }

                return $template;
            }
        }

        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * Load widget/template assets
     * @param  array  $assets An array of assets to load
     * @return void
     */
    protected function loadAssets($assets = array())
    {
        foreach ($assets as $asset) {

            if (is_array($asset)) {

                if (!empty($asset[1])) {

                    $isNails = $asset[1];

                } else {

                    $isNails = false;
                }

                $this->asset->load($asset[0], $isNails);

            } elseif (is_string($asset)) {

                $this->asset->load($asset);
            }
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
        $page = $this->get_by_id($id);

        if (!$page) {

            $this->_set_error('Invalid page ID');
            return false;
        }

        // --------------------------------------------------------------------------

        $this->db->trans_begin();

        $this->db->where('id', $id);
        $this->db->set('is_deleted', true);
        $this->db->set('modified', 'NOW()', false);

        if ($this->user_model->isLoggedIn()) {

            $this->db->set('modified_by', activeUser('id'));
        }

        if ($this->db->update($this->table)) {

            //  Success, update children
            $children = $this->getIdsOfChildren($id);

            if ($children) {

                $this->db->where_in('id', $children);
                $this->db->set('is_deleted', true);
                $this->db->set('modified', 'NOW()', false);

                if ($this->user_model->isLoggedIn()) {

                    $this->db->set('modified_by', activeUser('id'));
                }

                if (!$this->db->update($this->table)) {

                    $this->_set_error('Unable to delete children pages');
                    $this->db->trans_rollback();
                    return false;
                }
            }

            // --------------------------------------------------------------------------

            //  Rewrite routes
            $this->load->model('routes_model');
            $this->routes_model->update();

            // --------------------------------------------------------------------------

            //  Regenerate sitemap
            if (isModuleEnabled('nailsapp/module-sitemap')) {

                $this->load->model('sitemap/sitemap_model');
                $this->sitemap_model->generate();
            }

            // --------------------------------------------------------------------------

            $this->db->trans_commit();
            return true;

        } else {

            //  Failed
            $this->db->trans_rollback();
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
        $this->_set_error('It is not possible to destroy pages using this system.');
        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * Generate a page preview
     * @param  array $aData The Page data
     * @return mixed       int on success, false on failure
     */
    public function createPreview($aData)
    {
        if (empty($aData['data']->template)) {

            $this->_set_error('"data.template" is a required field.');
            return false;
        }

        // --------------------------------------------------------------------------

        $aInsertData                   = array();
        $aInsertData['draft_hash']     = isset($aData['hash']) ? $aData['hash'] : '';
        $aInsertData['draft_template'] = isset($aData['data']->template) ? $aData['data']->template : '';

        // --------------------------------------------------------------------------

        //  Test to see if this preview has already been created
        $this->db->select('id');
        $this->db->where('draft_hash', $aInsertData['draft_hash']);
        $oResult = $this->db->get($this->table_preview)->row();

        if ($oResult) {

            return (int) $oResult->id;
        }

        // --------------------------------------------------------------------------

        $aInsertData['draft_parent_id']       = !empty($aData['data']->parent_id) ? $aData['data']->parent_id : null;
        $aInsertData['draft_template_data']   = json_encode($aData, JSON_UNESCAPED_SLASHES);
        $aInsertData['draft_title']           = isset($aData['data']->title) ? $aData['data']->title : '';
        $aInsertData['draft_seo_title']       = isset($aData['data']->seo_title) ? $aData['data']->seo_title : '';
        $aInsertData['draft_seo_description'] = isset($aData['data']->seo_description) ? $aData['data']->seo_description : '';
        $aInsertData['draft_seo_keywords']    = isset($aData['data']->seo_keywords) ? $aData['data']->seo_keywords : '';

        //  Generate the breadcrumbs
        $aInsertData['draft_breadcrumbs'] = array();

        if ($aInsertData['draft_parent_id']) {

            /**
             * There is a parent, use it's breadcrumbs array as the starting point. No
             * need to fetch the parent again.
             */

            $oParent = $this->get_by_id($aInsertData['draft_parent_id']);

            if ($oParent) {

                $aInsertData['draft_breadcrumbs'] = $oParent->published->breadcrumbs;
            }
        }

        $oTemp        = new \stdClass();
        $oTemp->id    = null;
        $oTemp->title = $aInsertData['draft_title'];
        $oTemp->slug  = '';

        $aInsertData['draft_breadcrumbs'][] = $oTemp;
        unset($oTemp);

        //  Encode the breadcrumbs for the database
        $aInsertData['draft_breadcrumbs'] = json_encode($aInsertData['draft_breadcrumbs']);

        // --------------------------------------------------------------------------

        //  Meta data
        $aInsertData['created']  = date('Y-m-d H:i:s');
        $aInsertData['modified'] = date('Y-m-d H:i:s');

        if ($this->user_model->isLoggedIn()) {

            $aInsertData['created_by']  = activeUser('id');
            $aInsertData['modified_by'] = activeUser('id');
        }

        // --------------------------------------------------------------------------

        //  Save to the DB
        $this->db->trans_begin();
        $this->db->set($aInsertData);

        if (!$this->db->insert($this->table_preview)) {

            $this->db->trans_rollback();
            $this->_set_error('Failed to create preview object.');
            return false;

        } else {

            $iId = $this->db->insert_id();
            $this->db->trans_commit();
            return $iId;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Get's a preview page by it's ID
     * @param  integer   $iPreviewId The Id of the preview to get
     * @return mixed                 stdClass on success, false on failure
     */
    public function getPreviewById($iPreviewId)
    {
        $this->db->where('id', $iPreviewId);
        $oResult = $this->db->get($this->table_preview)->row();

        // --------------------------------------------------------------------------

        if (!$oResult) {

            return false;
        }

        // --------------------------------------------------------------------------

        $this->_format_object($oResult);
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
        $page = $this->get_by_id($iPageId);

        if ($page) {

            return $usePublished ? $page->published->url : $page->draft->url;

        } else {

            return false;
        }
    }
}

// --------------------------------------------------------------------------

/**
 * OVERLOADING NAILS' MODELS
 *
 * The following block of code makes it simple to extend one of the core Nails
 * models. Some might argue it's a little hacky but it's a simple 'fix'
 * which negates the need to massively extend the CodeIgniter Loader class
 * even further (in all honesty I just can't face understanding the whole
 * Loader class well enough to change it 'properly').
 *
 * Here's how it works:
 *
 * CodeIgniter instantiate a class with the same name as the file, therefore
 * when we try to extend the parent class we get 'cannot redeclare class X' errors
 * and if we call our overloading class something else it will never get instantiated.
 *
 * We solve this by prefixing the main class with NAILS_ and then conditionally
 * declaring this helper class below; the helper gets instantiated et voila.
 *
 * If/when we want to extend the main class we simply define NAILS_ALLOW_EXTENSION
 * before including this PHP file and extend as normal (i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if (!defined('NAILS_ALLOW_EXTENSION_CMS_PAGE_MODEL')) {

    class Cms_page_model extends NAILS_Cms_page_model
    {
    }
}
