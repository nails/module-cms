<?php

/**
 * This class is the "Image" CMS editor view
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

echo '<div class="fieldset">';

    $field            = array();
    $field['key']     = 'image_id';
    $field['label']   = 'Image';
    $field['default'] = isset(${$field['key']}) ? ${$field['key']} : '';
    $field['bucket']  = 'cms-widget-image';

    echo form_field_mm_image($field);

    // --------------------------------------------------------------------------

    $field            = array();
    $field['key']     = 'scaling';
    $field['label']   = 'Scaling';
    $field['class']   = 'select2';
    $field['default'] = isset(${$field['key']}) ? ${$field['key']} : '';

    $options = array(
        'NONE'  => 'None, show fullsize',
        'CROP'  => 'Crop to size',
        'SCALE' => 'Fit within boundary'
   );

    echo form_field_dropdown($field, $options);

    // --------------------------------------------------------------------------

    $field                = array();
    $field['key']         = 'width';
    $field['label']       = 'Width';
    $field['default']     = isset(${$field['key']}) ? ${$field['key']} : '';
    $field['placeholder'] = 'The maximum width of the image, in pixels.';

    echo form_field($field);

    // --------------------------------------------------------------------------

    $field                = array();
    $field['key']         = 'height';
    $field['label']       = 'Height';
    $field['default']     = isset(${$field['key']}) ? ${$field['key']} : '';
    $field['placeholder'] = 'The maximum height of the image, in pixels.';

    echo form_field($field);

    // --------------------------------------------------------------------------

    $field            = array();
    $field['key']     = 'linking';
    $field['label']   = 'Linking';
    $field['class']   = 'select2';
    $field['default'] = isset(${$field['key']}) ? ${$field['key']} : '';

    $options = array(
        'NONE'     => 'Do not link',
        'FULLSIZE' => 'Link to fullsize',
        'CUSTOM'   => 'Custom URL'
   );

    echo form_field_dropdown($field, $options);

    // --------------------------------------------------------------------------

    $field                = array();
    $field['key']         = 'url';
    $field['label']       = 'URL';
    $field['default']     = isset(${$field['key']}) ? ${$field['key']} : '';
    $field['placeholder'] = 'http://www.example.com';

    echo form_field($field);

    // --------------------------------------------------------------------------

    $field            = array();
    $field['key']     = 'target';
    $field['label']   = 'Target';
    $field['class']   = 'select2';
    $field['default'] = isset(${$field['key']}) ? ${$field['key']} : '';

    $options = array(
        ''        => 'None',
        '_blank'  => 'New window/tab',
        '_parent' => 'Parent window/tab'
   );

    echo form_field_dropdown($field, $options);

    // --------------------------------------------------------------------------

    $field                = array();
    $field['key']         = 'img_attr';
    $field['label']       = 'Attributes';
    $field['default']     = isset(${$field['key']}) ? ${$field['key']} : '';
    $field['placeholder'] = 'Any additional attributes to include in the image tag.';

    echo form_field($field);

    // --------------------------------------------------------------------------

    $field                = array();
    $field['key']         = 'link_attr';
    $field['label']       = 'Link Attributes';
    $field['default']     = isset(${$field['key']}) ? ${$field['key']} : '';
    $field['placeholder'] = 'Any additional attributes to include in the link tag.';

    echo form_field($field);

echo '</div>';
