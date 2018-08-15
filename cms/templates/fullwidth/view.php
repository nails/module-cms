<?php

/**
 * This is the "Full width" CMS template view
 *
 * @package     Nails
 * @subpackage  module-cms
 * @category    Template
 * @author      Nails Dev Team
 * @link
 */

$oView = \Nails\Factory::service('View');
echo $oView->load('structure/header', getControllerData(), true);
echo $mainbody;
echo $oView->load('structure/footer', getControllerData(), true);
