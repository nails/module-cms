<?php

/**
 * This is the "Sidebar Left" CMS template view
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Template
 * @author      Nails Dev Team
 * @link
 */

echo $this->load->view('structure/header', getControllerData());

// --------------------------------------------------------------------------

echo '<h1>Sidebar content</h1>';
echo $sidebar;

echo '<h1>Mainbody content</h1>';
echo $mainbody;

// --------------------------------------------------------------------------

echo $this->load->view('structure/footer', getControllerData());
