<?php

use Nails\Cms\Helper\Form;

if (!function_exists('form_field_cms_widgets')) {
    function form_field_cms_widgets($field, $tip = ''): string
    {
        return Form::form_field_cms_widgets($field, $tip);
    }
}
