<?php
/**
 * @package Odysplay
 * @copyright Copyright (c)2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; // No direct access.
// Include the helper functions only once
JLoader::register('ModOdysplayHelper', __DIR__.'/helper.php');
JLoader::register('TravelHelper', JPATH_ROOT.'/components/com_odyssey/helpers/travel.php');
JLoader::register('PriceruleHelper', JPATH_ROOT.'/components/com_odyssey/helpers/pricerule.php');
JLoader::register('OdysseyHelperRoute', JPATH_ROOT.'/components/com_odyssey/helpers/route.php');
JLoader::register('UtilityHelper', JPATH_ROOT.'/administrator/components/com_odyssey/helpers/utility.php');


if($params->get('linked_travels')) {
  $jinput = JFactory::getApplication()->input;

  if($jinput->get('view', '', 'string') == 'travel') {
    $travelId = $jinput->get('id', 0, 'int');
    //Gets the travel ids from the current travel (ie: its travel_ids attribute).
    $travelIds = ModOdysplayHelper::getLinkedTravelIds($travelId);
  }
  else { 
    //The module in linked travels mode is used only with the travel view.
    return;
  }
}
else {
  //Gets the travel ids from the module field.
  $travelIds = $params->get('travel_ids');
}

//Don't display anything if no travel ids have been found.
if(empty($travelIds)) {
  return;
}

$travels = ModOdysplayHelper::getTravels($travelIds, $params);
//Get the currency in the default display mode (ie: code or symbol);
$currency = UtilityHelper::getCurrency();

require(JModuleHelper::getLayoutPath('mod_odysplay'));

