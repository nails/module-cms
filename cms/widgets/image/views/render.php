<?php

    //  Set defaults
    $image_id  = ! empty($image_id)  ? $image_id  : '';
    $scaling   = ! empty($scaling)   ? $scaling   : '';
    $width     = ! empty($width)     ? $width     : '';
    $height    = ! empty($height)    ? $height    : '';
    $linking   = ! empty($linking)   ? $linking   : '';
    $url       = ! empty($url)       ? $url       : '';
    $target    = ! empty($target)    ? $target    : '';
    $link_attr = ! empty($link_attr) ? $link_attr : '';
    $img_attr  = ! empty($img_attr)  ? $img_attr  : '';

    if ($image_id) {

        //  Determine image URL
        if ($scaling == 'CROP' && $width && $height) {

            $img_url = cdn_thumb($image_id, $width, $height);

        } elseif ($scaling == 'SCALE' && $width && $height) {

            $img_url = cdn_scale($image_id, $width, $height);

        } else {

            $img_url = cdn_serve($image_id);
        }

        // --------------------------------------------------------------------------

        //  Determine linking
        if ($linking == 'CUSTOM' && $url) {

            $link_url       = $url;
            $link_target    = $target ? 'target="' . $target . '"' : '' ;

        } elseif ($linking == 'FULLSIZE') {

            $link_url       = cdn_serve($image_id);
            $link_target    = $target ? 'target="' . $target . '"' : '' ;

        } else {

            $link_url       = '';
            $link_target    = '';
        }

        // --------------------------------------------------------------------------

        // Render
        $out = '';
        $out .= $link_url ? '<a href="' . $link_url . '" ' . $link_attr . '>' : '';
        $out .= '<img src="' . $img_url . '" ' . $img_attr . '/>';
        $out .= $link_url ? '</a>' : '';

        echo $out;

    }
