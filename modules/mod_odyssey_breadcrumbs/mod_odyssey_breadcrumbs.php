<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_breadcrumbs
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include the breadcrumbs functions only once
JLoader::register('ModOdysseyBreadCrumbsHelper', __DIR__ . '/helper.php');

//Override: 
$params->set('travel_name', false);
$jinput = JFactory::getApplication()->input;
$option = $jinput->get('option', '', 'string');
$view = $jinput->get('view', '', 'string');

//Don't show the breadcrumbs during the payment process.
if($option == 'com_odyssey' && ($view == 'addons' || $view == 'passengers' || $view == 'booking' || $view == 'payment')) {
  return;
}
//Check if we are on the travel view. If we are, the travel name must be added to the pathway.
elseif($option == 'com_odyssey' && $view == 'travel') {
  $params->set('travel_id', $jinput->get('id', 0, 'uint'));
  $params->set('travel_name', true);
} //End of override.

// Get the breadcrumbs
$list  = ModOdysseyBreadCrumbsHelper::getList($params);
$count = count($list);

// Set the default separator
$separator = ModOdysseyBreadCrumbsHelper::setSeparator($params->get('separator'));
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8');

require JModuleHelper::getLayoutPath('mod_odyssey_breadcrumbs', $params->get('layout', 'default'));

