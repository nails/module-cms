<?php

/**
 * This file is the template for the contents of: view.php
 * Used by the console command when creating templates.
 */

return <<<'EOD'
<?php

/**
 * This is the "{{SLUG}}" CMS template view
 */

use Nails\Factory;

$oView = Factory::service('View');
$oView->load('structure/header', getControllerData());
echo $sBody;
$oView->load('structure/footer', getControllerData());

EOD;
