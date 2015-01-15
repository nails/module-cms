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

echo '<textarea name="body" class="wysiwyg">';
    echo isset($body) ? $body : '';
echo '</textarea>';
