<?php

/**
 * This class is the "Slider" CMS editor view
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

use Nails\Cms\Constants;
use Nails\Factory;

$oSliderModel = Factory::model('Slider', Constants::MODULE_SLUG);
$aSlidersFlat = $oSliderModel->getAllFlat();

if (empty($aSlidersFlat)) {

    ?>
    <div class="alert alert-warning">
        <strong>No Sliders Available: </strong>Create some sliders in the "Sliders" section of admin.
    </div>
    <?php

} else {

    ?>
    <div class="fieldset">
        <?php

        $aField            = array();
        $aField['key']     = 'sliderId';
        $aField['label']   = 'Slider';
        $aField['class']   = 'select2';
        $aField['default'] = isset(${$aField['key']}) ? ${$aField['key']} : '';

        echo form_field_dropdown($aField, $aSlidersFlat);

        ?>
    </div>
    <?php

}
