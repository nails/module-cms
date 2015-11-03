<?php

/**
 * This is the "Slider" CMS widget view
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

$iSliderId = !empty($sliderId) ? (int) $sliderId: null;

if (!empty($iSliderId)) {

    $oSliderModel = \Nails\Factory::model('Slider', 'nailsapp/module-cms');
    $oSlider      = $oSliderModel->get_by_id($iSliderId);

    ?>
    <div class="cms-widget cms-widget-slider">
        <?php dump($oSlider) ?>
    </div>
    <?php

}
