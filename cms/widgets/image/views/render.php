<?php

/**
 * This class is the "Image" CMS widget view
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

//  Set defaults
$imageId  = ! empty($image_id)  ? $image_id  : '';
$scaling  = ! empty($scaling)   ? $scaling   : '';
$width    = ! empty($width)     ? $width     : '';
$height   = ! empty($height)    ? $height    : '';
$linking  = ! empty($linking)   ? $linking   : '';
$url      = ! empty($url)       ? $url       : '';
$target   = ! empty($target)    ? $target    : '';
$linkAttr = ! empty($link_attr) ? $link_attr : '';
$imgAttr  = ! empty($img_attr)  ? $img_attr  : '';

if ($imageId) {

    //  Determine image URL
    if ($scaling == 'CROP' && $width && $height) {

        $imgUrl = cdn_thumb($imageId, $width, $height);

    } elseif ($scaling == 'SCALE' && $width && $height) {

        $imgUrl = cdn_scale($imageId, $width, $height);

    } else {

        $imgUrl = cdn_serve($imageId);
    }

    // --------------------------------------------------------------------------

    //  Determine linking
    if ($linking == 'CUSTOM' && $url) {

        $linkUrl    = $url;
        $linkTarget = $target ? 'target="' . $target . '"' : '' ;

    } elseif ($linking == 'FULLSIZE') {

        $linkUrl    = cdn_serve($imageId);
        $linkTarget = $target ? 'target="' . $target . '"' : '' ;

    } else {

        $linkUrl    = '';
        $linkTarget = '';
    }

    // --------------------------------------------------------------------------

    // Render
    $out = '';
    $out .= $linkUrl ? '<a href="' . $linkUrl . '" ' . $linkAttr . $linkTarget . '>' : '';
    $out .= '<img src="' . $imgUrl . '" ' . $imgAttr . '/>';
    $out .= $linkUrl ? '</a>' : '';

    echo $out;

}
