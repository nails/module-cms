<?php

/**
 * This class is the "Rich Text" CMS widget view
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Widget
 * @author      Nails Dev Team
 * @link
 */

if (isset($body)) {

	echo '<div class="cms-widget cms-widget-richtext">';
		echo $body;
	echo '</div>';
}

