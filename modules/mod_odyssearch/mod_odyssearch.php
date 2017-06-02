<?php
/**
 * @package Odyssearch
 * @copyright Copyright (c)2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; // No direct access.


$jinput = JFactory::getApplication()->input;
$option = $jinput->get('option', '', 'string');
$view = $jinput->get('view', '', 'string');

//Don't load the module in the search view of the Odyssey component. 
if($option == 'com_odyssey' && $view == 'search') {
  return;
}

$itemId = $params->get('set_itemid');

$form = false;
//Create a new JForm object
$form = new JForm('filterForm');
//Load form .xml file.
$form->loadFile(JPATH_ROOT.'/components/com_odyssey/models/forms/filter_search.xml');

//Get the filter setting from the component global configuration.
$searchFilters = JComponentHelper::getParams('com_odyssey')->get('search_filters');
//Price and duration filters are always shown.
$showedFilters = array('price', 'duration'); 

//Set the filters to show according to the filter setting.
if($searchFilters != 'region' && $searchFilters != 'city' && $searchFilters != 'region_city') {
  $showedFilters[] = 'country';
}

if($searchFilters != 'country' && $searchFilters != 'city' && $searchFilters != 'country_city') {
  $showedFilters[] = 'region';
}

if($searchFilters != 'country' && $searchFilters != 'region' && $searchFilters != 'country_region') {
  $showedFilters[] = 'city';
}

//Load the language file of the component to get the country and region variables.
$lang = JFactory::getLanguage();
$lang->load('com_odyssey', JPATH_ROOT.'/components/com_odyssey', $lang->getTag());

require(JModuleHelper::getLayoutPath('mod_odyssearch'));

