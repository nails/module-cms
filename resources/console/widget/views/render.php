<?php

/**
 * This file is the template for the contents of: views/render.php
 * Used by the console command when creating widgets.
 */

return <<<'EOD'
<?php

/**
 * This is the "{{SLUG}}" CMS widget view

 */

if (!empty($body)) {
    ?>
    <div class="cms-widget cms-widget-{{SLUG_LC}}">
        <?=$some_variable?>
    </div>
    <?php
}

EOD;
