<?php

/**
 * This is the "Redirect" CMS template definition
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Template
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cms\Cms\Template;

use Nails\Cms\Constants;
use Nails\Cms\Template\TemplateBase;
use Nails\Factory;

class Redirect extends TemplateBase
{
    protected $oPageModel;

    // --------------------------------------------------------------------------

    /**
     * Construct and define the template
     */
    public function __construct()
    {
        parent::__construct();

        $this->oPageModel = Factory::model('Page', Constants::MODULE_SLUG);

        $this->label       = 'Redirect';
        $this->description = 'Redirects to another URL.';

        // --------------------------------------------------------------------------

        //  Additional fields
        $oOptionPage = Factory::factory('TemplateOption', Constants::MODULE_SLUG);
        $oOptionPage->setType('dropdown');
        $oOptionPage->setKey('redirect_page_id');
        $oOptionPage->setLabel('Redirect To Page');
        $oOptionPage->setClass('select2');
        $oOptionPage->setOptions(
            ['None'] + $this->oPageModel->getAllNestedFlat()
        );

        $oOptionUrl = Factory::factory('TemplateOption', Constants::MODULE_SLUG);
        $oOptionUrl->setType('text');
        $oOptionUrl->setKey('redirect_url');
        $oOptionUrl->setLabel('Redirect To URL');
        $oOptionUrl->setPlaceholder(
            'Manually set the URL to redirect to, this will override any option set above.'
        );
        $oOptionUrl->setTip(
            'URLs which do not begin with http(s):// will automatically be prefixed with ' . siteUrl()
        );

        $oOptionCode = Factory::factory('TemplateOption', Constants::MODULE_SLUG);
        $oOptionCode->setType('dropdown');
        $oOptionCode->setKey('redirect_code');
        $oOptionCode->setLabel('Redirect Type');
        $oOptionCode->setClass('select2');
        $oOptionCode->setOptions(
            [
                '302' => '302 Moved Temporarily',
                '301' => '301 Moved Permanently',
            ]
        );

        $this->additional_fields = [
            $oOptionPage,
            $oOptionUrl,
            $oOptionCode,
        ];
    }

    // --------------------------------------------------------------------------

    /**
     * Executes the redirect
     *
     * @param array $aTplData    The widgets to include in the template
     * @param array $aTplOptions Additional data created by the template
     *
     * @return string
     */
    public function render(array $aTplData = [], array $aTplOptions = []): string
    {
        $sUrl = '';

        if (!empty($aTplOptions['redirect_url'])) {
            $sUrl = $aTplOptions['redirect_url'];

        } elseif (!empty($aTplOptions['redirect_page_id'])) {

            $oPage = $this->oPageModel->getById($aTplOptions['redirect_page_id']);

            if ($oPage && !$oPage->is_deleted && $oPage->is_published) {
                $sUrl = $oPage->published->url;
            }
        }

        // --------------------------------------------------------------------------

        $iCode = !empty($aTplOptions['redirect_code']) ? $aTplOptions['redirect_code'] : null;

        if ($sUrl) {
            redirect($sUrl, 'location', $iCode);

        } else {
            show404();
        }
    }
}
