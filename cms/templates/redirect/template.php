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

class NAILS_CMS_Template_redirect extends Nails_CMS_Template
{
    /**
     * Defines the basic template details object.
     * @return stdClass
     */
    public static function details()
    {
        //  Base object
        $d = parent::details();

        //  Basic details; describe the template for the user
        $d->label       = 'Redirect';
        $d->description = 'Redirects to another URL.';

        // --------------------------------------------------------------------------

        //  Additional fields
        $d->additional_fields               = array();
        $d->additional_fields[0]            = array();
        $d->additional_fields[0]['type']    = 'dropdown';
        $d->additional_fields[0]['key']     = 'redirect_page_id';
        $d->additional_fields[0]['label']   = 'Redirect To Page';
        $d->additional_fields[0]['class']   = 'select2';
        $d->additional_fields[0]['options'] = array('None') + get_instance()->cms_page_model->get_all_nested_flat();

        $d->additional_fields[1]                = array();
        $d->additional_fields[1]['type']        = 'text';
        $d->additional_fields[1]['key']         = 'redirect_url';
        $d->additional_fields[1]['label']       = 'Redirect To URL';
        $d->additional_fields[1]['placeholder'] = 'Manually set the URL to redirect to, this will override any option set above.';
        $d->additional_fields[1]['tip']         = 'URLs which do not begin with http(s):// will automatically be prefixed with ' . site_url();

        $d->additional_fields[2]            = array();
        $d->additional_fields[2]['type']    = 'dropdown';
        $d->additional_fields[2]['key']     = 'redirect_code';
        $d->additional_fields[2]['label']   = 'Redirect Type';
        $d->additional_fields[2]['class']   = 'select2';
        $d->additional_fields[2]['options'] = array(

            '302' => '302 Moved Temporarily',
            '301' => '301 Moved Permanently'
      );

        // --------------------------------------------------------------------------

        return $d;
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
