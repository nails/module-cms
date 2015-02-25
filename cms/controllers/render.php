<?php

//  Include _cdn.php; executes common functionality
require_once '_cms.php';

/**
 * This class renders CMS pages
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Render extends NAILS_CMS_Controller
{
    protected $pageId;
    protected $isPreview;

    // --------------------------------------------------------------------------

    /**
     * Constructs the controller
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $this->load->model('cms/cms_page_model');

        // --------------------------------------------------------------------------

        $this->pageId    = $this->uri->rsegment(3);
        $this->isPreview = false;
    }

    // --------------------------------------------------------------------------

    /**
     * Loads a published CMS page
     * @return void
     */
    public function page()
    {
        if ($this->isPreview) {

            $page = $this->cms_page_model->get_preview_by_id($this->pageId);

        } else {

            $page = $this->cms_page_model->get_by_id($this->pageId);
        }

        if (!$page || $page->is_deleted) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  If a page is not published and not being previewed, show_404()
        if (!$page->is_published && !$this->isPreview) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Determine which data to use
        if ($this->isPreview) {

            $data = $page->draft;

        } else {

            $data = $page->published;
        }

        $this->data['page_data'] =& $data;

        // --------------------------------------------------------------------------

        /**
         * If the page is the homepage and we're viewing it by slug, then redirect to
         * the non slug'd version
         */

        if ($page->is_homepage && uri_string() == $data->slug) {

            redirect('', 'location', 301);
        }

        // --------------------------------------------------------------------------

        //  Set some page level data
        $this->data['page']->id               = $page->id;
        $this->data['page']->title            = $data->title;
        $this->data['page']->seo              = new stdClass();
        $this->data['page']->seo->title       = $data->seo_title;
        $this->data['page']->seo->description = $data->seo_description;
        $this->data['page']->seo->keywords    = $data->seo_keywords;
        $this->data['page']->is_preview       = $this->isPreview;

        //  Prepare data
        $render                   = new stdClass();
        $render->widgets          = new stdClass();
        $render->additionalFields = new stdClass();

        if (isset($data->template_data->widget_areas->{$data->template})) {

            $render->widgets = $data->template_data->widget_areas->{$data->template};
        }

        if (isset($data->template_data->data->additional_fields->{$data->template})) {

            $render->additionalFields = $data->template_data->data->additional_fields->{$data->template};
        }

        //  Decode manual config
        if (isset($render->additionalFields->manual_config)) {

            $render->additionalFields->manual_config = json_decode($render->additionalFields->manual_config);
        }

        // --------------------------------------------------------------------------

        /**
         * If we're viewing a published page, but there are unpublished changes (and
         * the user is someone with edit permissions) then highlight this fact using
         * a system alert (which the templates *should* handle).
         */

        if (
            !$this->data['message']
            && !$this->isPreview
            && $page->has_unpublished_changes
            && $this->user_model->isAdmin()
            && userHasPermission('admin:cms:pages:edit')
        ) {

            $this->data['message'] = lang('cms_notice_unpublished_changes',
                array(
                    site_url('admin/cms/pages/edit/' . $page->id)
                )
            );
        }

        // --------------------------------------------------------------------------

        /**
         * Add the page data as a reference to the additionalFields, so widgets can
         * have some contect about the page they're being rendered on.
         */

        $render->additionalFields->cmspage =& $data;

        // --------------------------------------------------------------------------

        //  Actually render
        $html = $this->cms_page_model->render_template($data->template, $render->widgets, $render->additionalFields);
        $this->output->set_output($html);
    }

    // --------------------------------------------------------------------------

    /**
     * Loads a draft CMS page
     * @return void
     */
    public function preview()
    {
        if (userHasPermission('admin:cms:pages:edit')) {

            $this->isPreview = true;
            return $this->page();

        } else {

            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Loads the homepage
     * @return void
     */
    public function homepage()
    {
        //  Attempt to get the site's homepage
        $homepage = $this->cms_page_model->get_homepage();

        if ($homepage) {

            $this->pageId = $homepage->id;
            $this->page();

        } else {

            showFatalError('No homepage has been defined.');
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Loads a legacy slug and redirects to the new page if found
     * @return void
     */
    public function legacy_slug()
    {
        //  Get the page and attempt to 301 redirect
        $id = $this->uri->rsegment(3);

        if ($id) {

            $page = $this->cms_page_model->get_by_id($id);

            if ($page && $page->is_published) {

                redirect($page->published->slug, 'location', 301);
            }
        }

        // --------------------------------------------------------------------------

        //  We don't know what to do, *falls over*
        show_404();
    }
}

// --------------------------------------------------------------------------

/**
 * OVERLOADING NAILS' CMS MODULE
 *
 * The following block of code makes it simple to extend one of the core auth
 * controllers. Some might argue it's a little hacky but it's a simple 'fix'
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

if (!defined('NAILS_ALLOW_EXTENSION_CMS_RENDER')) {

    class Render extends NAILS_Render
    {
    }
}
