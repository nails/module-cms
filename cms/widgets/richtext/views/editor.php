<?php

/**
 * This class is the "Rich Text" CMS editor view
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

?>
<textarea name="body" class="wysiwyg">
    <?=!empty($body) ? $body : ''?>
</textarea>
