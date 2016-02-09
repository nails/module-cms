<?php

/**
 * This class is the "Blockquote" CMS widget view
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

$sQuote    = !empty($quote) ? $quote : '';
$sCiteUrl  = !empty($cite_url) ? $cite_url : '';
$sCiteText = !empty($cite_text) ? $cite_text : '';

if (!empty($sQuote)) {

    ?>
    <div class="cms-widget cms-widget-blockquote">
        <blockquote>
            <?php

            echo $sQuote;

            if (!empty($sCiteText)) {

                echo '<footer>';

                if (!empty($sCiteUrl)) {

                    echo $sCiteText;

                } else {

                    echo anchor($sCiteUrl, $sCiteText);
                }

                echo '</footer>';

            }

            ?>
        </blockquote>
    </div>
    <?php

}
