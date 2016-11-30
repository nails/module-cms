<?php

/**
 * This file is the template for the contents of: views/editor.php
 * Used by the console command when creating widgets.
 */

return <<<'EOD'
<?php

/**
 * This is the "{{SLUG}}" CMS editor view
 */

?>
<textarea name="some_variable"><?=!empty($some_variable) ? $some_variable : ''?></textarea>

EOD;
