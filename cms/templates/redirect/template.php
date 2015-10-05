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

namespace Nails\Cms\Template;

class Redirect extends TemplateBase
{
    /**
     * Construct and define the template
     */
    public function __construct()
    {
        parent::__construct();

        $this->label       = 'Redirect';
        $this->description = 'Redirects to another URL.';

        // --------------------------------------------------------------------------

        //  Additional fields
        $this->additional_fields[0] = \Nails\Factory::factory('TemplateOption', 'nailsapp/module-cms');
        $this->additional_fields[0]->setType('dropdown');
        $this->additional_fields[0]->setKey('redirect_page_id');
        $this->additional_fields[0]->setLabel('Redirect To Page');
        $this->additional_fields[0]->setClass('select2');
        $this->additional_fields[0]->setOptions(
            array('None') + get_instance()->cms_page_model->getAllNestedFlat()
        );

        $this->additional_fields[1] = \Nails\Factory::factory('TemplateOption', 'nailsapp/module-cms');
        $this->additional_fields[1]->setType('text');
        $this->additional_fields[1]->setKey('redirect_url');
        $this->additional_fields[1]->setLabel('Redirect To URL');
        $this->additional_fields[1]->setPlaceholder(
            'Manually set the URL to redirect to, this will override any option set above.'
        );
        $this->additional_fields[1]->setTip(
            'URLs which do not begin with http(s):// will automatically be prefixed with ' . site_url()
        );

        $this->additional_fields[2] = \Nails\Factory::factory('TemplateOption', 'nailsapp/module-cms');
        $this->additional_fields[2]->setType('dropdown');
        $this->additional_fields[2]->setKey('redirect_code');
        $this->additional_fields[2]->setLabel('Redirect Type');
        $this->additional_fields[2]->setClass('select2');
        $this->additional_fields[2]->setOptions(
            array(
                '302' => '302 Moved Temporarily',
                '301' => '301 Moved Permanently'
            )
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Executes the redirect
     * @param  array  $tplWidgets          The widgets to include in the template
     * @param  array  $tplAdditionalFields Additional data created by the template
     * @return void
     */
    public function render($tplWidgets = array(), $tplAdditionalFields = array())
    {
        die('todo');
        $url = '';

        if (!empty($tplAdditionalFields['redirect_url'])) {

            $url = $tplAdditionalFields['redirect_url'];

        } elseif (!empty($tplAdditionalFields['redirect_page_id'])) {

            $page = get_instance()->cms_page_model->get_by_id($tplAdditionalFields['redirect_page_id']);

            if ($page && ! $page->is_deleted && $page->is_published) {

                $url = $page->published->url;
            }
        }

        // --------------------------------------------------------------------------

        $code = ! empty($tplAdditionalFields['redirect_code']) ? $tplAdditionalFields['redirect_code'] : '';

        if ($url) {

            redirect($url, 'location', $code);

        } else {

            show_404();
        }
    }
}
