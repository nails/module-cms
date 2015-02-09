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
    protected $nailsTemplatesDir;
    protected $appTemplatesDir;
    protected $nailsWidgetsDir;
    protected $appWidgetsDir;
    protected $nailsPrefix;
    protected $appPrefix;

    // --------------------------------------------------------------------------

    /**
     * Constuct the model
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $this->nailsTemplatesDir = NAILS_PATH . 'module-cms/cms/templates/';
        $this->appTemplatesDir   = FCPATH . APPPATH . 'modules/cms/templates/';

        $this->nailsWidgetsDir = NAILS_PATH . 'module-cms/cms/widgets/';
        $this->appWidgetsDir   = FCPATH . APPPATH . 'modules/cms/widgets/';

        //  @TODO: Load widgets from modules

        $this->nailsPrefix = 'NAILS_CMS_';
        $this->appPrefix   = 'CMS_';

        $this->_table         = NAILS_DB_PREFIX . 'cms_page';
        $this->_table_preview =  $this->_table . '_preview';
        $this->_table_prefix  = 'p';

        $this->_destructive_delete = false;

        // --------------------------------------------------------------------------

        //  Load the generic template & widget
        include_once $this->nailsTemplatesDir . '_template.php';
        include_once $this->nailsWidgetsDir . '_widget.php';
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new CMS page
     * @param  array  $data The data to create the page with
     * @return mixed        The ID of the page on success, false on failure
     */
    public function create($data)
    {
        if (empty($data->data->template)) {

            $this->_set_error('"data.template" is a required field.');
            return false;
        }

        // --------------------------------------------------------------------------

        $this->db->trans_begin();

        //  Create a new blank row to work with
        $id = parent::create();

        if (!$id) {

            $this->_set_error('Unable to create base page object.');
            $this->db->trans_rollback();
            return false;
        }

        //  Try and update it depending on how the update went, commit & update or rollback
        if ($this->update($id, $data)) {

            $this->db->trans_commit();
            return $id;

        } else {

            $this->db->trans_rollback();
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Update a CMS page
     * @param  int     $pageId The ID of the page to update
     * @param  array   $data   The data to update with
     * @return boolean
     */
    public function update($pageId, $data)
    {
        //  Check the data
        if (empty($data->data->template)) {

            $this->_set_error('"data.template" is a required field.');
            return false;
        }

        // --------------------------------------------------------------------------

        //  Fetch the current version of this page, for reference.
        $current = $this->get_by_id($pageId);

        if (!$current) {

            $this->_set_error('Invalid Page ID');
            return false;
        }

        // --------------------------------------------------------------------------

        /**
         * Clone the data object so we can mutate it without worry. Unset id and hash
         * as we don't need to store them
         */

        $clone = clone $data;
        unset($clone->id);
        unset($clone->hash);

        // --------------------------------------------------------------------------

        //  Start the transaction
        $this->db->trans_begin();

        // --------------------------------------------------------------------------

        //  Start prepping the data which doesn't require much thinking
        $data = new stdClass();

        $data->draft_parent_id       = !empty($clone->data->parent_id)       ? (int) $clone->data->parent_id       : null;
        $data->draft_title           = !empty($clone->data->title)           ? trim($clone->data->title)           : 'Untitled';
        $data->draft_seo_title       = !empty($clone->data->seo_title)       ? trim($clone->data->seo_title)       : '';
        $data->draft_seo_description = !empty($clone->data->seo_description) ? trim($clone->data->seo_description) : '';
        $data->draft_seo_keywords    = !empty($clone->data->seo_keywords)    ? trim($clone->data->seo_keywords)    : '';
        $data->draft_template        = $clone->data->template;
        $data->draft_template_data   = json_encode($clone, JSON_UNESCAPED_SLASHES);
        $data->draft_hash            = md5($data->draft_template_data);

        // --------------------------------------------------------------------------

        /**
         * Additional sanitising; encode HTML entities. Also encode the pipe character
         * in the title, so that it doesn't break our explode
         */

        $data->draft_title           = htmlentities(str_replace('|', '&#124;', $data->draft_title), ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
        $data->draft_seo_title       = htmlentities($data->draft_seo_title, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
        $data->draft_seo_description = htmlentities($data->draft_seo_description, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
        $data->draft_seo_keywords    = htmlentities($data->draft_seo_keywords, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);

        // --------------------------------------------------------------------------

        //  Prep data which requires a little more intensive processing

        // --------------------------------------------------------------------------

        //  Work out the slug
        if ($data->draft_parent_id) {

            //  There is a parent, so set it's slug as the prefix
            $parent = $this->get_by_id($data->draft_parent_id);

            if (!$parent) {

                $this->_set_error('Invalid Parent ID.');
                $this->db->trans_rollback();
                return false;
            }

            $prefix = $parent->draft->slug . '/';

        } else {

            //  No parent, no need for a prefix
            $prefix = '';
        }

        $data->draft_slug     = $this->_generate_slug($data->draft_title, $prefix, '', null, 'draft_slug', $current->id);
        $data->draft_slug_end = end(explode('/', $data->draft_slug));

        // --------------------------------------------------------------------------

        //  Generate the breadcrumbs
        $data->draft_breadcrumbs = array();

        if ($data->draft_parent_id) {

            /**
             * There is a parent, use it's breadcrumbs array as the starting point.
             * No need to fetch the parent again.
             */

            $data->draft_breadcrumbs = $parent->draft->breadcrumbs;
        }

        $temp        = new stdClass();
        $temp->id    = $current->id;
        $temp->title = $data->draft_title;
        $temp->slug  = $data->draft_slug;

        $data->draft_breadcrumbs[] = $temp;
        unset($temp);

        //  Encode the breadcrumbs for the database
        $data->draft_breadcrumbs = json_encode($this->generateBreadcrumbs($current->id));

        // --------------------------------------------------------------------------

        if (parent::update($current->id, $data)) {

            //  Update was successful, set the breadcrumbs
            $breadcrumbs = $this->generateBreadcrumbs($current->id);

            $this->db->set('draft_breadcrumbs', json_encode($breadcrumbs));
            $this->db->where('id', $current->id);
            if (!$this->db->update($this->_table)) {

                $this->_set_error('Failed to generate breadcrumbs.');
                $this->db->trans_rollback();
                return false;
            }

            //  For each child regenerate the breadcrumbs and slugs (only if the title or slug has changed)
            if ($current->draft->title != $data->draft_title || $current->draft->slug != $data->draft_slug) {

                $children = $this->get_ids_of_children($current->id);

                if ($children) {

                    //  Loop each child and update it's details
                    foreach ($children as $child_id) {

                        /**
                         * We can assume that the children are in a sensible order, loop
                         * them and process. For nested children, their parent will have
                         * been processed by the time we process it.
                         */

                        $child = $this->get_by_id($child_id);

                        if (!$child) {

                            continue;
                        }

                        $data = new stdClass();

                        //  Generate the breadcrumbs
                        $data->draft_breadcrumbs = json_encode($this->generateBreadcrumbs($child->id));

                        //  Generate the slug
                        if ($child->draft->parent_id) {

                            //  Child has a parent, fetch it and use it's slug as the prefix
                            $parent = $this->get_by_id($child->draft->parent_id);

                            if ($parent) {

                                $data->draft_slug = $parent->draft->slug . '/' . $child->draft->slug_end;

                            } else {

                                //  Parent is bad, make this a parent page. Poor wee orphan.
                                $data->draft_parent_id = null;
                                $data->draft_slug      = $child->draft->slug_end;
                            }

                        } else {

                            //  Would be weird if this happened, but ho hum handle it anyway
                            $data->draft_parent_id = null;
                            $data->draft_slug      = $child->draft->slug_end;
                        }

                        //  Update the child and move on
                        if (!parent::update($child->id, $data)) {

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
     * @param  int   $id The page to generate breadcrumbs for
     * @return mixed     Array of breadcrumbs, or false on failure
     */
    protected function generateBreadcrumbs($id)
    {
        $page = $this->get_by_id($id);

        if (!$page) {

            return false;
        }

        // --------------------------------------------------------------------------

        $breadcrumbs = array();

        if ($page->draft->parent_id) {

            $breadcrumbs = array_merge($breadcrumbs, $this->generateBreadcrumbs($page->draft->parent_id));
        }

        $temp        = new stdClass();
        $temp->id    = $page->id;
        $temp->title = $page->draft->title;

        $breadcrumbs[] = $temp;
        unset($temp);

        return $breadcrumbs;
    }

    // --------------------------------------------------------------------------

    /**
     * Render a template with the provided widgets and additional data
     * @param  string $template         The template to render
     * @param  array  $widgets          The widgets to render
     * @param  array  $additionalFields Any additional fields to pass to the template
     * @return mixed                    String (the rendered template) on success, false on failure
     */
    public function render_template($template, $widgets = array(), $additionalFields = array())
    {
        $template = $this->get_template($template, 'RENDER');

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

        if ($this->user_model->is_logged_in()) {

            $this->db->set('modified_by', active_user('id'));
        }

        $this->db->where('id', $page->id);

        if ($this->db->update($this->_table)) {

            //  Fetch the children, returning the data we need for the updates
            $children = $this->get_ids_of_children($page->id);

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

                    if (!$this->db->update($this->_table)) {

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
            $this->routes_model->update('cms');

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
        $this->db->select($this->_table_prefix . '.*');
        $this->db->select('ue.email, u.first_name, u.last_name, u.profile_img, u.gender');

        $this->db->join(NAILS_DB_PREFIX . 'user u', 'u.id = ' . $this->_table_prefix . '.modified_by', 'LEFT');
        $this->db->join(NAILS_DB_PREFIX . 'user_email ue', 'ue.user_id = u.id AND ue.is_primary = 1', 'LEFT');

        $this->db->order_by($this->_table_prefix . '.draft_slug');
    }

    // --------------------------------------------------------------------------

    /**
     * Gets all pages, nested
     * @param  boolean $useDraft Whther to use the published or draft version of pages
     * @return array
     */
    public function get_all_nested($useDraft = true)
    {
        return $this->nestPages($this->get_all(), null, $useDraft);
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
     * Get all pages nested, but as a flat array
     * @param  string  $separator               The seperator to use between pages
     * @param  boolean $murderParentsOfChildren Whether to include parents in the result
     * @return array
     */
    public function get_all_nested_flat($separator = ' &rsaquo; ', $murderParentsOfChildren = true)
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
     * @param  int    $pageId The ID of the page to look at
     * @param  string $format How to return the data, one of ID, ID_SLUG, ID_SLUG_TITLE or ID_SLUG_TITLE_PUBLISHED
     * @return array
     */
    public function get_ids_of_children($pageId, $format = 'ID')
    {
        $out = array();

        $this->db->select('id,draft_slug,draft_title,is_published');
        $this->db->where('draft_parent_id', $pageId);
        $children = $this->db->get(NAILS_DB_PREFIX . 'cms_page')->result();

        if ($children) {

            foreach ($children as $child) {

                switch ($format) {

                    case 'ID':

                        $out[] = $child->id;
                        break;

                    case 'ID_SLUG':

                        $out[] = array(
                            'id'   => $child->id,
                            'slug' => $child->draft_slug
                        );
                        break;

                    case 'ID_SLUG_TITLE':

                        $out[] = array(
                            'id'    => $child->id,
                            'slug'  => $child->draft_slug,
                            'title' => $child->draft_title
                        );
                        break;

                    case 'ID_SLUG_TITLE_PUBLISHED':

                        $out[] = array(
                            'id'           => $child->id,
                            'slug'         => $child->draft_slug,
                            'title'        => $child->draft_title,
                            'is_published' => (bool) $child->is_published
                        );
                        break;
                }

                $out = array_merge($out, $this->get_ids_of_children($child->id, $format));
            }

            return $out;

        } else {

            return $out;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Get all pages as a flat array
     * @param  boolean $useDraft Whether to use published data, or draft data
     * @return array
     */
    public function get_all_flat($useDraft = true)
    {
        $out   = array();
        $pages = $this->get_all();

        foreach ($pages as $page) {

            if ($useDraft) {

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
     * @param  boolean $useDraft Whether to use published data, or draft data
     * @return array
     */
    public function get_top_level($useDraft = true)
    {
        if ($useDraft) {

            $this->db->where('draft_parent_id', null);

        } else {

            $this->db->where('published_parent_id', null);
        }

        return $this->get_all();
    }

    // --------------------------------------------------------------------------

    /**
     * Get the siblings of a page, i.e those with the smame parent
     * @param  int     $id       The page whose sibilings to fetch
     * @param  boolean $useDraft Whether to use published data, or draft data
     * @return array
     */
    public function get_siblings($id, $useDraft = true)
    {
        $page = $this->get_by_id($id);

        if (!$page) {

            return array();
        }

        if ($useDraft) {

            $this->db->where('draft_parent_id', $page->draft->parent_id);

        } else {

            $this->db->where('published_parent_id', $page->published->parent_id);
        }

        return $this->get_all();
    }

    // --------------------------------------------------------------------------

    /**
     * Get the page marked as the homepage
     * @return mixed stdClass on success, false on failure
     */
    public function get_homepage()
    {
        $this->db->where($this->_table_prefix . '.is_homepage', true);
        $page = $this->get_all();

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
    protected function _format_object(&$page)
    {
        parent::_format_object($page);

        $page->is_published = (bool) $page->is_published;
        $page->is_deleted   = (bool) $page->is_deleted;

        //  Loop properties and sort into published data and draft data
        $page->published = new stdClass();
        $page->draft     = new stdClass();

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
        $page->modified_by              = new stdClass();
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
     * @param  boolean $loadAssets Whether or not to laod assets defined by the widgets
     * @return array
     */
    public function get_available_widgets($loadAssets = false)
    {
        //  Have we done this already? Don't do it again.
        $key   = 'cms-page-available-widgets';
        $cache = $this->_get_cache($key);

        if ($cache) {

            return $cache;
        }

        // --------------------------------------------------------------------------

        /**
         * Search the Nails. widget folder, and then the App's widget folder. Widgets
         *in the app folder trump widgets in the Nails folder
         */

        $this->load->helper('directory');

        $nailsWidgets = array();
        $appWidgets   = array();

        //  Look for nails widgets
        $nailsWidgets = is_dir($this->nailsWidgetsDir) ? directory_map($this->nailsWidgetsDir) : array();

        //  Look for app widgets
        if (is_dir($this->appWidgetsDir)) {

            $appWidgets = is_dir($this->appWidgetsDir) ? directory_map($this->appWidgetsDir) : array();
        }

        // --------------------------------------------------------------------------

        //  Test and merge widgets
        $widgets = array();
        foreach ($nailsWidgets as $widget => $details) {

            //  Ignore base template
            if ($details == '_widget.php') {

                continue;
            }

            //  Ignore malformed widgets
            if (!is_array($details) || array_search('widget.php', $details) === false) {

                log_message('error', 'Ignoring malformed NAILS CMS Widget "' . $widget . '"');
                continue;
            }

            //  Ignore widgets which have an app override
            if (isset($appWidgets[$widget]) && is_array($appWidgets[$widget])) {

                continue;
            }

            // --------------------------------------------------------------------------

            include_once $this->nailsWidgetsDir . $widget . '/widget.php';

            //  Can we call the static details method?
            $class = $this->nailsPrefix . 'Widget_' . $widget;

            if (!class_exists($class) || !method_exists($class, 'details')) {

                log_message('error', 'Cannot call static method "details()" on  NAILS CMS Widget: "' . $widget . '"');
                continue;
            }

            $details = $class::details();

            if ($details) {

                $widgets[$widget] = $class::details();
            }
        }

        //  Now test app widgets
        foreach ($appWidgets as $widget => $details) {

            //  Ignore malformed widgets
            if (!is_array($details) || array_search('widget.php', $details) === false) {

                log_message('error', 'Ignoring malformed APP CMS Widget "' . $widget . '"');
                continue;
            }

            // --------------------------------------------------------------------------

            include_once $this->appWidgetsDir . $widget . '/widget.php';

            //  Can we call the static details method?
            $class = $this->appPrefix . 'Widget_' . $widget;

            if (!class_exists($class) || !method_exists($class, 'details')) {

                log_message('error', 'Cannot call static method "details()" on  APP CMS Widget: "' . $widget . '"');
                continue;
            }

            $widgets[$widget] = $class::details();
        }

        // --------------------------------------------------------------------------

        //  Sort the widgets into their sub groupings and then alphabetically
        $out                   = array();
        $genericWidgets        = array();
        $genericWidgetGrouping = 'Generic';

        foreach ($widgets as $w) {

            if ($w->grouping) {

                $key = md5($w->grouping);

                if (!isset($out[$key])) {

                    $out[$key]          = new stdClass();
                    $out[$key]->label   = $w->grouping;
                    $out[$key]->widgets = array();
                }

                $out[$key]->widgets[] = $w;

            } else {

                $key = md5($genericWidgetGrouping);

                if (!isset($genericWidgets[$key])) {

                    $genericWidgets[$key]          = new stdClass();
                    $genericWidgets[$key]->label   = $genericWidgetGrouping;
                    $genericWidgets[$key]->widgets = array();
                }

                $genericWidgets[$key]->widgets[] = $w;
            }

            // --------------------------------------------------------------------------

            //  Load the widget's assets if requested
            if ($loadAssets) {

                //  What type of assets do we want to load, editor or render assets?
                switch ($loadAssets) {

                    case 'EDITOR':

                        $assets = $w->assets_editor;
                        break;

                    case 'RENDER':

                        $assets = $w->assets_render;
                        break;

                    default:

                        $assets = array();
                        break;
                }

                $this->loadAssets($assets);
            }
        }

        //  Sort non-generic widgets into alphabetical order
        foreach ($out as $o) {

            usort($o->widgets, array($this, 'sortWidgets'));
        }

        //  Sort generic
        usort($genericWidgets[md5($genericWidgetGrouping)]->widgets, array($this, 'sortWidgets'));

        /**
         * Sort the non-generic groupings
         * @TODO: Future Pabs, explain in comment why you're not using the sortWidgets method.
         * I'm sure there's a valid reason you handsome chap, you.
         */

        usort($out, function ($a, $b) use ($genericWidgetGrouping) {

            //  Equal?
            if (trim($a->label) == trim($b->label)) {

                return 0;
            }

            //  Not equal, work out which takes precedence
            $sort = array($a->label, $b->label);
            sort($sort);

            return $sort[0] == $a->label ? -1 : 1;
        });

        //  Glue generic groupings to the beginning of the array
        $out = array_merge($genericWidgets, $out);

        // --------------------------------------------------------------------------

        //  Save to the cache
        $this->_set_cache($key, $widgets);

        // --------------------------------------------------------------------------

        return array_values($out);
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
    public function get_widget($slug, $loadAssets = false)
    {
        $widgets = $this->get_available_widgets();

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
     * @param  boolean $loadAssets Whether or not to load template's assets
     * @return array
     */
    public function get_available_templates($loadAssets = false)
    {
        //  Have we done this already? Don't do it again.
        $key   = 'cms-page-available-templates';
        $cache = $this->_get_cache($key);

        if ($cache) {

            return $cache;
        }

        // --------------------------------------------------------------------------

        /**
         * Search the Nails. widget folder, and then the App's widget folder. Widgets
         * in the app folder trump widgets in the Nails folder
         */

        $this->load->helper('directory');

        $nailsTemplates = array();
        $appTemplates   = array();

        //  Look for nails widgets
        $nailsTemplates = is_dir($this->nailsTemplatesDir) ? directory_map($this->nailsTemplatesDir) : array();

        //  Look for app widgets
        if (is_dir($this->appTemplatesDir)) {

            $appTemplates = is_dir($this->appTemplatesDir) ? directory_map($this->appTemplatesDir) : array();
        }

        // --------------------------------------------------------------------------

        //  Test and merge templates
        $templates = array();
        foreach ($nailsTemplates as $template => $details) {

            //  Ignore base template
            if ($details == '_template.php') {

                continue;
            }

            //  Ignore malformed templates
            if (!is_array($details) || array_search('template.php', $details) === false) {

                log_message('error', 'Ignoring malformed NAILS CMS Template "' . $template . '"');
                continue;
            }

            //  Ignore templates which have an app override
            if (isset($appTemplates[$template]) && is_array($appTemplates[$template])) {

                continue;
            }

            // --------------------------------------------------------------------------

            include_once $this->nailsTemplatesDir . $template . '/template.php';

            //  Can we call the static details method?
            $class = $this->nailsPrefix . 'Template_' . $template;

            if (!class_exists($class) || !method_exists($class, 'details')) {

                log_message('error', 'Cannot call static method "details()" on  NAILS CMS Template: "' . $template . '"');
                continue;
            }

            $details = $class::details();

            if ($details) {

                $templates[$template] = $class::details();

            } else {

                //  This template returned no details, ignore it.
                log_message('warning', 'Static method "details()"" of Nails template "' . $template . '" returned empty data.');
            }

            // --------------------------------------------------------------------------

            //  Load the template's assets if requested
            if ($loadAssets) {

                //  What type of assets do we want to load, editor or render assets?
                switch ($loadAssets) {

                    case 'EDITOR':

                        $assets = $templates[$template]->assets_editor;
                        break;

                    case 'RENDER':

                        $assets = $templates[$template]->assets_render;
                        break;

                    default:

                        $assets = array();
                        break;
                }

                $this->loadAssets($assets);
            }
        }

        //  Now test app templates
        foreach ($appTemplates as $template => $details) {

            //  Ignore malformed templates
            if (!is_array($details) || array_search('template.php', $details) === false) {

                log_message('error', 'Ignoring malformed APP CMS Template "' . $template . '"');
                continue;
            }

            // --------------------------------------------------------------------------

            include_once $this->appTemplatesDir . $template . '/template.php';

            //  Can we call the static details method?
            $class = $this->appPrefix . 'Template_' . $template;

            if (!class_exists($class) || !method_exists($class, 'details')) {

                log_message('error', 'Cannot call static method "details()" on  NAILS CMS Template: "' . $template . '"');
                continue;
            }

            $details = $class::details();

            if ($details) {

                $templates[$template] = $class::details();

            } else {

                /**
                 * This template returned no details, ignore this template. Don't log
                 * anything as it's likely a developer override to hide a default
                 * template.
                 */

                continue;
            }

            // --------------------------------------------------------------------------

            //  Load the template's assets if requested
            if ($loadAssets) {

                switch ($loadAssets) {

                    case 'EDITOR':

                        $assets = $templates[$template]->assets_editor;
                        break;

                    case 'RENDER':

                        $assets = $templates[$template]->assets_render;
                        break;

                    default:

                        $assets = array();
                        break;

                }

                $this->loadAssets($assets);
            }
        }

        // --------------------------------------------------------------------------

        //  Sort into some alphabetical order
        ksort($templates);

        // --------------------------------------------------------------------------

        //  Save to the cache
        $this->_set_cache($key, $templates);

        // --------------------------------------------------------------------------

        return $templates;
    }

    // --------------------------------------------------------------------------

    /**
     * Get an individual template
     * @param  string  $slug       The template's slug
     * @param  boolean $loadAssets Whether or not to load the template's assets
     * @return mixed               stdClass on success, false on failure
     */
    public function get_template($slug, $loadAssets = false)
    {
        $templates = $this->get_available_templates();

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

        if ($this->user_model->is_logged_in()) {

            $this->db->set('modified_by', active_user('id'));
        }

        if ($this->db->update($this->_table)) {

            //  Success, update children
            $children = $this->get_ids_of_children($id);

            if ($children) {

                $this->db->where_in('id', $children);
                $this->db->set('is_deleted', true);
                $this->db->set('modified', 'NOW()', false);

                if ($this->user_model->is_logged_in()) {

                    $this->db->set('modified_by', active_user('id'));
                }

                if (!$this->db->update($this->_table)) {

                    $this->_set_error('Unable to delete children pages');
                    $this->db->trans_rollback();
                    return false;
                }
            }

            // --------------------------------------------------------------------------

            //  Rewrite routes
            $this->load->model('routes_model');
            $this->routes_model->update('cms');

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
     * @param  array $data The Page data
     * @return mixed       int on success, false on failure
     */
    public function create_preview($data)
    {
        if (empty($data->data->template)) {

            $this->_set_error('"data.template" is a required field.');
            return false;
        }

        // --------------------------------------------------------------------------

        $preview                 = new stdClass();
        $preview->draft_hash     = isset($data->hash) ? $data->hash : '';
        $preview->draft_template = isset($data->data->template) ? $data->data->template : '';

        // --------------------------------------------------------------------------

        //  Test to see if this preview has already been created
        $this->db->select('id');
        $this->db->where('draft_hash', $preview->draft_hash);
        $result = $this->db->get($this->_table_preview)->row();

        if ($result) {

            return $result->id;
        }

        // --------------------------------------------------------------------------

        $preview->draft_parent_id       = !empty($data->data->parent_id) ? $data->data->parent_id : null;
        $preview->draft_template_data   = json_encode($data, JSON_UNESCAPED_SLASHES);
        $preview->draft_title           = isset($data->data->title) ? $data->data->title : '';
        $preview->draft_seo_title       = isset($data->data->seo_title) ? $data->data->seo_title : '';
        $preview->draft_seo_description = isset($data->data->seo_description) ? $data->data->seo_description : '';
        $preview->draft_seo_keywords    = isset($data->data->seo_keywords) ? $data->data->seo_keywords : '';

        //  Generate the breadcrumbs
        $preview->draft_breadcrumbs = array();

        if ($preview->draft_parent_id) {

            /**
             * There is a parent, use it's breadcrumbs array as the starting point. No
             * need to fetch the parent again.
             */

            $parent = $this->get_by_id($preview->draft_parent_ud);

            if ($parent) {

                $preview->draft_breadcrumbs = $parent->published->breadcrumbs;
            }
        }

        $temp        = new stdClass();
        $temp->id    = null;
        $temp->title = $preview->draft_title;
        $temp->slug  = '';

        $preview->breadcrumbs[] = $temp;
        unset($temp);

        //  Encode the breadcrumbs for the database
        $preview->draft_breadcrumbs = json_encode($preview->draft_breadcrumbs);

        // --------------------------------------------------------------------------

        //  Save to the DB
        $this->db->trans_begin();

        $this->db->set($preview);
        $this->db->set('created', 'NOW()', false);
        $this->db->set('modified', 'NOW()', false);

        if ($this->user_model->is_logged_in()) {

            $this->db->set('created_by', active_user('id'));
            $this->db->set('modified_by', active_user('id'));
        }

        if (!$this->db->insert($this->_table_preview)) {

            $this->db->trans_rollback();
            $this->_set_error('Failed to create preview object.');
            return false;

        } else {

            $id = $this->db->insert_id();
            $this->db->trans_commit();
            return $id;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Get's a preview page by it's ID
     * @param  int   $previewId The Id of the preview to get
     * @return mixed            stdClass on success, false on failure
     */
    public function get_preview_by_id($previewId)
    {
        $this->db->where('id', $previewId);
        $result = $this->db->get($this->_table_preview)->row();

        // --------------------------------------------------------------------------

        if (!$result) {

            return false;
        }

        // --------------------------------------------------------------------------

        $this->_format_object($result);
        return $result;
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
