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

use Nails\Factory;

$iSliderId = !empty($sliderId) ? (int) $sliderId: null;

if (!empty($iSliderId)) {

    $oSliderModel = Factory::model('Slider', 'nailsapp/module-cms');
    $oSlider      = $oSliderModel->getById($iSliderId);

    ?>
    <div class="cms-widget cms-widget-slider">
        <?php dump($oSlider) ?>
    </div>
    <?php

}
