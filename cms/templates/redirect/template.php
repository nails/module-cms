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

        $this->oPageModel = Factory::model('Page', 'nailsapp/module-cms');

        $this->label       = 'Redirect';
        $this->description = 'Redirects to another URL.';

        // --------------------------------------------------------------------------

        //  Additional fields
        $this->additional_fields[0] = Factory::factory('TemplateOption', 'nailsapp/module-cms');
        $this->additional_fields[0]->setType('dropdown');
        $this->additional_fields[0]->setKey('redirect_page_id');
        $this->additional_fields[0]->setLabel('Redirect To Page');
        $this->additional_fields[0]->setClass('select2');
        $this->additional_fields[0]->setOptions(
            ['None'] + $this->oPageModel->getAllNestedFlat()
        );

        $this->additional_fields[1] = Factory::factory('TemplateOption', 'nailsapp/module-cms');
        $this->additional_fields[1]->setType('text');
        $this->additional_fields[1]->setKey('redirect_url');
        $this->additional_fields[1]->setLabel('Redirect To URL');
        $this->additional_fields[1]->setPlaceholder(
            'Manually set the URL to redirect to, this will override any option set above.'
        );
        $this->additional_fields[1]->setTip(
            'URLs which do not begin with http(s):// will automatically be prefixed with ' . site_url()
        );

        $this->additional_fields[2] = Factory::factory('TemplateOption', 'nailsapp/module-cms');
        $this->additional_fields[2]->setType('dropdown');
        $this->additional_fields[2]->setKey('redirect_code');
        $this->additional_fields[2]->setLabel('Redirect Type');
        $this->additional_fields[2]->setClass('select2');
        $this->additional_fields[2]->setOptions(
            [
                '302' => '302 Moved Temporarily',
                '301' => '301 Moved Permanently',
            ]
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Executes the redirect
     *
     * @param  array $aTplData    The widgets to include in the template
     * @param  array $aTplOptions Additional data created by the template
     *
     * @return void
     */
    public function render(array $aTplData = [], array $aTplOptions = [])
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

            show_404();
        }
    }
}
