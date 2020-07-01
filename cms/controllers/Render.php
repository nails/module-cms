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
use Nails\Cms\Constants;
use Nails\Cms\Exception\RenderException;
use Nails\Common\Exception\NailsException;
use Nails\Common\Service\Meta;
use Nails\Common\Service\Session;
use Nails\Factory;

/**
 * Class Render
 */
class Render extends Base
{
    /**
     * @var int
     */
    protected $iPageId;

    /**
     * @var bool
     */
    protected $bIsPreview;

    /**
     * @var bool
     */
    protected $bIsHomepage;

    /**
     * @var \Nails\Cms\Model\Page
     */
    protected $oPageModel;

    /**
     * @var int
     */
    protected $iHomepageId;

    // --------------------------------------------------------------------------

    /**
     * Constructs the controller
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        /** @var \Nails\Cms\Model\Page oPageModel */
        $this->oPageModel = Factory::model('Page', Constants::MODULE_SLUG);
        /** @var \Nails\Common\Service\Uri $oUri */
        $oUri = Factory::service('Uri');

        get_instance()->lang->load('cms');

        $this->iPageId     = $oUri->rsegment(3);
        $this->bIsPreview  = false;
        $this->bIsHomepage = false;
        $this->iHomepageId = $this->oPageModel->getHomepageId();
    }

    // --------------------------------------------------------------------------

    /**
     * Loads a published CMS page
     *
     * @return void
     * @throws RenderException
     */
    public function page()
    {
        if ($this->bIsPreview) {
            /** @var \Nails\Cms\Model\Page\Preview $oPagePreviewModel */
            $oPagePreviewModel = Factory::model('PagePreview', Constants::MODULE_SLUG);
            $oPage             = $oPagePreviewModel->getById($this->iPageId);
        } else {
            $oPage = $this->oPageModel->getById($this->iPageId);
        }

        if (!$oPage || $oPage->is_deleted) {
            show404();
        }

        // --------------------------------------------------------------------------

        //  If a page is not published and not being previewed, show404()
        if (!$oPage->is_published && !$this->bIsPreview) {
            show404();
        }

        // --------------------------------------------------------------------------

        //  Determine which data to use
        if ($this->bIsPreview) {
            $oData = $oPage->draft;
        } else {
            $oData = $oPage->published;
        }

        $this->data['oCmsPage']     =& $oPage;
        $this->data['oCmsPageData'] =& $oData;

        //  @todo (Pablo - 2020-06-05) - kept for backwards compatability
        $this->data['page_data'] =& $oData;

        // --------------------------------------------------------------------------

        /**
         * If the page is the homepage and we're viewing it by slug, then redirect to
         * the non slug'd version
         */

        if ($oPage->id === $this->iHomepageId && uri_string() == $oData->slug) {

            /** @var Session $oSession */
            $oSession = Factory::service('Session');
            $oSession->keepFlashData();
            redirect('', 'location', 301);
        }

        // --------------------------------------------------------------------------

        $this->setMeta(
            $oData->seo_title ?: $oData->title,
            $oData->seo_description,
            $oData->seo_image_id,
            $oData->seo_keywords
        );

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
                    siteUrl('admin/cms/pages/edit/' . $oPage->id),
                ]
            );
        }

        // --------------------------------------------------------------------------

        //  Actually render
        $sRenderedHtml = $this->oPageModel->render($oData->template, $oData->template_data, $oData->template_options);
        if ($sRenderedHtml !== false) {

            /** @var \Nails\Common\Service\Output $oOutput */
            $oOutput = Factory::service('Output');
            $oOutput->set_output($sRenderedHtml);

        } else {
            throw new RenderException('Failed to render CMS Page: ' . $this->oPageModel->lastError(), 1);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Loads a draft CMS page
     *
     * @return void
     */
    public function preview()
    {
        if (userHasPermission('admin:cms:pages:edit')) {
            $this->bIsPreview = true;
            $this->page();
        } else {
            show404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Loads the homepage
     *
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
            throw new NailsException('No homepage has been defined.');
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Loads a legacy slug and redirects to the new page if found
     *
     * @return void
     */
    public function legacy_slug()
    {
        //  Get the page and attempt to 301 redirect
        /** @var \Nails\Common\Service\Uri $oUri */
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
        show404();
    }
}
