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

use App\Controller\Base;
use Nails\Cms\Exception\RenderException;
use Nails\Factory;

class Render extends Base
{
    protected $iPageId;
    protected $bIsPreview;
    protected $bIsHomepage;
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

        $oUri              = Factory::service('Uri');
        $this->iPageId     = $oUri->rsegment(3);
        $this->bIsPreview  = false;
        $this->bIsHomepage = false;
        $this->iHomepageId = $this->oPageModel->getHomepageId();
    }

    // --------------------------------------------------------------------------

    /**
     * Loads a published CMS page
     * @throws RenderException
     * @return void
     */
    public function page()
    {
        if ($this->bIsPreview) {
            $oPage = $this->oPageModel->getPreviewById($this->iPageId);
        } else {
            $oPage = $this->oPageModel->getById($this->iPageId);
        }

        if (!$oPage || $oPage->is_deleted) {
            show_404();
        }

        // --------------------------------------------------------------------------

        //  If a page is not published and not being previewed, show_404()
        if (!$oPage->is_published && !$this->bIsPreview) {
            show_404();
        }

        // --------------------------------------------------------------------------

        //  Determine which data to use
        if ($this->bIsPreview) {
            $oData = $oPage->draft;
        } else {
            $oData = $oPage->published;
        }

        $this->data['page_data'] =& $oData;

        // --------------------------------------------------------------------------

        /**
         * If the page is the homepage and we're viewing it by slug, then redirect to
         * the non slug'd version
         */

        if ($oPage->id === $this->iHomepageId && uri_string() == $oData->slug) {
            $oSession = Factory::service('Session', 'nailsapp/module-auth');
            $oSession->keep_flashdata();
            redirect('', 'location', 301);
        }

        // --------------------------------------------------------------------------

        //  Set some page level data
        $this->data['page']->id          = $oPage->id;
        $this->data['page']->title       = $oData->title;
        $this->data['page']->seo         = (object) [
            'title'       => $oData->seo_title,
            'description' => $oData->seo_description,
            'keywords'    => $oData->seo_keywords,
        ];
        $this->data['page']->is_preview  = $this->bIsPreview;
        $this->data['page']->is_homepage = $this->bIsHomepage;
        $this->data['page']->breadcrumbs = $oData->breadcrumbs;

        //  Set some meta tags for the header
        $oMeta = Factory::service('Meta');
        $oMeta->add('description', $oData->seo_description);
        $oMeta->add('keywords', $oData->seo_keywords);

        // --------------------------------------------------------------------------

        /**
         * If we're viewing a published page, but there are unpublished changes (and
         * the user is someone with edit permissions) then highlight this fact using
         * a system alert (which the templates *should* handle).
         */

        $bHasDataAndNotPreview  = !$this->data['message'] && !$this->bIsPreview;
        $bHasUnpublishedChanges = $oPage->has_unpublished_changes;
        $bUserHasPermission     = userHasPermission('admin:cms:pages:edit');

        if ($bHasDataAndNotPreview && $bHasUnpublishedChanges && $bUserHasPermission) {
            $this->data['message'] = lang(
                'cms_notice_unpublished_changes',
                [
                    site_url('admin/cms/pages/edit/' . $oPage->id),
                ]
            );
        }

        // --------------------------------------------------------------------------

        //  Actually render
        $sRenderedHtml = $this->oPageModel->render($oData->template, $oData->template_data, $oData->template_options);
        if ($sRenderedHtml !== false) {

            $oOutput = Factory::service('Output');
            $oOutput->set_output($sRenderedHtml);

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
            $this->bIsPreview = true;
            $this->page();
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
        $oHomepage = $this->oPageModel->getHomepage();

        if ($oHomepage) {
            $this->bIsHomepage = true;
            $this->iPageId     = $oHomepage->id;
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
        $oUri = Factory::service('Uri');
        $iId  = (int) $oUri->rsegment(3);

        if ($iId) {
            $oPage = $this->oPageModel->getById($iId);
            if ($oPage && $oPage->is_published) {
                redirect($oPage->published->slug, 'location', 301);
            }
        }

        // --------------------------------------------------------------------------

        //  We don't know what to do, *falls over*
        show_404();
    }
}
