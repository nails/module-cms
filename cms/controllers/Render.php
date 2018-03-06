<?php

/**
 * This class renders CMS pages
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

use Nails\Factory;
use App\Controller\Base;
use Nails\Cms\Exception\RenderException;

class Render extends Base
{
    protected $pageId;
    protected $isPreview;
    protected $isHomepage;
    protected $oPageModel;
    protected $iHomepageId;

    // --------------------------------------------------------------------------

    /**
     * Constructs the controller
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $this->oPageModel = Factory::model('Page', 'nailsapp/module-cms');
        $this->lang->load('cms');

        // --------------------------------------------------------------------------

        $this->pageId      = $this->uri->rsegment(3);
        $this->isPreview   = false;
        $this->isHomepage  = false;
        $this->iHomepageId = $this->oPageModel->getHomepageId();
    }

    // --------------------------------------------------------------------------

    /**
     * Loads a published CMS page
     * @return void
     */
    public function page()
    {
        if ($this->isPreview) {

            $page = $this->oPageModel->getPreviewById($this->pageId);

        } else {

            $page = $this->oPageModel->getById($this->pageId);
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

        if ($page->id === $this->iHomepageId && uri_string() == $data->slug) {

            $oSession = Factory::service('Session', 'nailsapp/module-auth');
            $oSession->keep_flashdata();

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
        $this->data['page']->is_homepage      = $this->isHomepage;
        $this->data['page']->breadcrumbs      = $data->breadcrumbs;

        //  Set some meta tags for the header
        $this->meta->add('description', $data->seo_description);
        $this->meta->add('keywords', $data->seo_keywords);

        // --------------------------------------------------------------------------

        /**
         * If we're viewing a published page, but there are unpublished changes (and
         * the user is someone with edit permissions) then highlight this fact using
         * a system alert (which the templates *should* handle).
         */

        $hasDataAndNotPreview = !$this->data['message'] && !$this->isPreview;
        $hasUnublishedChanges = $page->has_unpublished_changes;
        $userHasPermission    = userHasPermission('admin:cms:pages:edit');

        if ($hasDataAndNotPreview && $hasUnublishedChanges && $userHasPermission) {

            $this->data['message'] = lang(
                'cms_notice_unpublished_changes',
                array(
                    site_url('admin/cms/pages/edit/' . $page->id)
                )
            );
        }

        // --------------------------------------------------------------------------

        //  Actually render
        $html = $this->oPageModel->render($data->template, $data->template_data, $data->template_options);
        if ($html !== false) {

            $oOutput = Factory::service('Output');
            $oOutput->set_output($html);

        } else {

            throw new RenderException('Failed to render CMS Page: ' . $this->oPageModel->lastError(), 1);
        }
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
        $homepage = $this->oPageModel->getHomepage();

        if ($homepage) {

            $this->isHomepage = true;
            $this->pageId     = $homepage->id;

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

            $page = $this->oPageModel->getById($id);

            if ($page && $page->is_published) {

                redirect($page->published->slug, 'location', 301);
            }
        }

        // --------------------------------------------------------------------------

        //  We don't know what to do, *falls over*
        show_404();
    }
}
