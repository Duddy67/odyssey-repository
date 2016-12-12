<?php
/**
 * @package Odyssey
 * @copyright Copyright (c)2016 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; // No direct access.
// Include the helper functions only once
JLoader::register('ModOdysplayHelper', __DIR__.'/helper.php');
JLoader::register('TravelHelper', JPATH_ROOT.'/components/com_odyssey/helpers/travel.php');
JLoader::register('OdysseyHelperRoute', JPATH_ROOT.'/components/com_odyssey/helpers/route.php');
JLoader::register('UtilityHelper', JPATH_ROOT.'/administrator/components/com_odyssey/helpers/utility.php');
//require_once(JPATH_ROOT.'/components/com_odyssey/helpers/travel.php');

$travelIds = $params->get('travel_ids');
$travelIds = preg_replace('#\s#', '', $travelIds);
$travelIds = explode(';', $travelIds);

$module = JModuleHelper::getModule('odysplay');

$travels = ModOdysplayHelper::getTravels($travelIds, $params);
//Get the currency in the default display mode (ie: code or symbol);
$currency = UtilityHelper::getCurrency();

$jinput = JFactory::getApplication()->input;
$option = $jinput->get('option', '', 'string');
$view = $jinput->get('view', '', 'string');

//Load the language file of the component to get the country and region variables.
$lang = JFactory::getLanguage();
$lang->load('com_odyssey', JPATH_ROOT.'/components/com_odyssey', $lang->getTag());

require(JModuleHelper::getLayoutPath('mod_odysplay'));

