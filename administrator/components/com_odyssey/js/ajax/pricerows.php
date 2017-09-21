<?php
//Initialize the Joomla framework
define('_JEXEC', 1);
//First we get the number of letters we want to substract from the path.
$length = strlen('/administrator/components/com_odyssey/js');
//Turn the length number into a negative value.
$length = $length - ($length * 2);
//
define('JPATH_BASE', substr(dirname(__DIR__), 0, $length));
require_once (JPATH_BASE.'/administrator/components/com_odyssey/helpers/utility.php');

//Get the required files
require_once (JPATH_BASE.'/includes/defines.php');
require_once (JPATH_BASE.'/includes/framework.php');
//We need to use Joomla's database class 
require_once (JPATH_BASE.'/'.UtilityHelper::getFactoryFilePath());
//Create the application
$mainframe = JFactory::getApplication('site');
$mainframe->initialise();

//Get or set the required variables.
$travelId = JFactory::getApplication()->input->get->get('travel_id', 0, 'uint');
$dptStepId = JFactory::getApplication()->input->get->get('dpt_step_id', 0, 'uint');
$langTag = JFactory::getApplication()->input->get->get('lang_tag', '', 'str');
$data = array();
$dateType = 'standard';

$lang = JFactory::getLanguage();
//Check the lang tag parameter has been properly retrieved.
if(empty($langTag)) {
  //If not, we'll use english by default.
  $langTag = $lang->getTag();
}
$lang->load('com_odyssey', JPATH_ROOT.'/administrator/components/com_odyssey/', $lang->getTag(), true);


function setDateFormat(&$items, $dateType)
{
  //Set date format according to the date type.
  $dateFormat = 'COM_ODYSSEY_DATE_FORMAT_'.strtoupper($dateType);

  foreach($items as $key => $value) {
    $items[$key]['date_time'] = JHtml::_('date', $value['date_time'], JText::_($dateFormat));
    //
    if($dateType == 'period') {
      $items[$key]['date_time_2'] = JHtml::_('date', $value['date_time_2'], JText::_($dateFormat));
    }
  }
}


$db = JFactory::getDbo();
$query = $db->getQuery(true);
//Get a travel price row for each departure of the step sequence multiplied with the number of passengers.
//Note: If the travel item is new we just get a price row for each departure of the step
//sequence with an empty price field.
$query->select('d.dpt_id, d.date_time, d.date_time_2, d.max_passengers, c.name AS city, c.lang_var, p.psgr_nb,p.price')
      ->from('#__odyssey_departure_step_map AS d')
      ->join('LEFT', '#__odyssey_travel_price AS p ON p.dpt_id=d.dpt_id AND p.travel_id='.(int)$travelId.' AND p.dpt_step_id='.(int)$dptStepId)
      ->join('LEFT', '#__odyssey_city AS c ON c.id=d.city_id')
      ->where('d.step_id='.(int)$dptStepId)
      ->order('d.date_time, p.psgr_nb');
$db->setQuery($query);
$results = $db->loadAssocList();

//Check for the departure date type.
if($results[0]['date_time_2'] != '0000-00-00 00:00:00') {
  $dateType = 'period';
}

$travelPriceRows = UtilityHelper::combinePriceRows($results);

//file_put_contents('debog_file.txt', print_r($travelPriceRows, true));
//Get an addon price row for each departure selected for this addon multiplied with the number of passengers.
//The rows are displayed by step as the same addon can be included in different step of the sequence.
//Note: If the travel item is new or if the addon has just been added from the step item, we just get a price row 
//for each departure selected for this addon with an empty price field.
$query->clear();
$query->select('sa.step_id, s2.name AS step_name, ds.dpt_id, ds.date_time, ds.date_time_2, c.name AS city, ds.max_passengers,'.
               'sa.addon_id, sa.ordering AS addon_ordering, a.name AS addon_name, a.global AS addon_global, ap.psgr_nb, ap.price')
      ->from('#__odyssey_step_addon_map AS sa')
      ->join('LEFT', '#__odyssey_step AS s1 ON s1.id='.(int)$dptStepId)
      ->join('LEFT', '#__odyssey_step AS s2 ON s2.group_alias=s1.group_alias')
      //->join('LEFT', '#__odyssey_step AS s2 ON s2.dpt_step_id=s1.id OR s2.id='.(int)$dptStepId) //Alternate version
      ->join('LEFT', '#__odyssey_departure_step_map AS ds ON ds.step_id=s1.id')
      ->join('LEFT', '#__odyssey_addon_price AS ap ON ap.travel_id='.$travelId.' AND ap.step_id=sa.step_id'.
	             ' AND ap.addon_id=sa.addon_id AND ap.dpt_id=sa.dpt_id')
      //Get the addon and departure city names.
      ->join('LEFT', '#__odyssey_addon AS a ON a.id=sa.addon_id')
      ->join('LEFT', '#__odyssey_city AS c ON c.id=ds.city_id')
      ->where('sa.dpt_id=ds.dpt_id')
      ->where('sa.step_id=s2.id')
      //Don't get addons from unpublished link steps.
      ->where('s2.published=1')
      //Don't get unpublished addons as well.
      ->where('a.published=1')
      //Order the result rows according to the way we want to display them. 
      ->order('sa.step_id, sa.ordering, sa.addon_id, ds.date_time, ap.psgr_nb');
$db->setQuery($query);
$results = $db->loadAssocList();

$addonPriceRows = UtilityHelper::combinePriceRows($results);

if(!empty($addonPriceRows)) {
  //Now we have to reorder the steps as they must be ordered by time gap no by id.

  //Get only the ids of the type link steps.
  $stepIds = array();
  foreach($addonPriceRows as $addonPriceRow) {
    if($addonPriceRow['step_id'] != $dptStepId && !in_array($addonPriceRow['step_id'], $stepIds)) {
      $stepIds[] = $addonPriceRow['step_id'];
    }
  }

  if(!empty($stepIds)) {
    //Get the type link steps in a chronological order.
    $query->clear();
    //Note: For each step get only the one with the highest time gap value from the departure.
    $query->select('step_id, MAX(time_gap)') 
	  ->from('#__odyssey_timegap_step_map')
	  ->where('step_id IN('.implode(',', $stepIds).')')
	  ->group('step_id')
	  ->order('time_gap');
    $db->setQuery($query);
    $steps = $db->loadAssocList();

    //Add the departure step data at the beginning of the order/array.
    array_unshift($steps, array('step_id' => $dptStepId, 'time_gap' => '000:00:00')); 

    //Reorder the addon rows according to the order of the steps. 
    $tmp = array();
    foreach($steps as $step) {
      foreach($addonPriceRows as $addonPriceRow) {
	if($addonPriceRow['step_id'] == $step['step_id']) {
	  $tmp[] = $addonPriceRow;
	}
      }
    }

    $addonPriceRows = $tmp;
  }

  //Get all the possible addon options linked to the addons previously retrieved.
  //For each option linked to an addon an option price row is created for each departure
  //multiplied with the number of passengers.
  $query->clear();
  $query->select('sa.step_id, ds.dpt_id, ds.date_time, ds.date_time_2, ds.max_passengers, ao.addon_id,'.
		 'ao.id AS addon_option_id,ao.ordering AS addon_option_ordering, ao.name AS addon_option_name, op.psgr_nb, op.price')
	->from('#__odyssey_step_addon_map AS sa')
	->join('INNER', '#__odyssey_addon_option AS ao ON ao.addon_id=sa.addon_id')
	->join('LEFT', '#__odyssey_step AS s1 ON s1.id='.(int)$dptStepId)
	->join('LEFT', '#__odyssey_step AS s2 ON s2.group_alias=s1.group_alias')
	//->join('LEFT', '#__odyssey_step AS s2 ON s2.dpt_step_id=s1.id OR s2.id='.(int)$dptStepId) //Alternate version
	->join('LEFT', '#__odyssey_departure_step_map AS ds ON ds.step_id=s1.id')
	//Get the possible prices from the addon option price table.
	->join('LEFT', '#__odyssey_addon_option_price AS op ON op.travel_id='.$travelId.' AND op.step_id=sa.step_id '.
		       'AND op.dpt_id=ds.dpt_id AND op.addon_option_id=ao.id')
	->where('sa.dpt_id=ds.dpt_id')
	->where('sa.step_id=s2.id')
	//Don't get addon options from unpublished link steps.
	->where('s2.published=1')
	//Don't get unpublished addon options as well.
	->where('ao.published=1')
	->order('sa.step_id, ao.ordering, ao.addon_id, ao.id, ds.date_time, op.psgr_nb');
  //file_put_contents('debog_file.txt', print_r($query->__toString(), true));
  $db->setQuery($query);
  $results = $db->loadAssocList();

  $options = UtilityHelper::combinePriceRows($results);

//file_put_contents('debog_file.txt', print_r($options, true));
  if(!empty($options)) {
    //Insert the possible options in the addon array.
    $tmp = array();
    foreach($addonPriceRows as $addonPriceRow) {
      $tmp[] = $addonPriceRow;

      //Check if we have one or more options linked to this addon.
      foreach($options as $option) {
	if($option['step_id'] == $addonPriceRow['step_id'] &&
	   $option['addon_id'] == $addonPriceRow['addon_id'] &&
	   $option['dpt_id'] == $addonPriceRow['dpt_id']) {
	  //Store the option(s) in the row(s) folling the row of the "parent" addon.
	  $tmp[] = $option;
	}
      }
    }

    $addonPriceRows = $tmp;
  }
}

$query->clear();
$query->select('ds.dpt_id, ds.date_time, ds.date_time_2, c.name AS city, ds.max_passengers,'.
               'tc.city_id, tcn.name AS transitcity_name, tc.time_offset, ap.psgr_nb, ap.price')
      ->from('#__odyssey_step_transit_city_map AS tc')
      ->join('LEFT', '#__odyssey_step AS s1 ON s1.id='.(int)$dptStepId)
      ->join('LEFT', '#__odyssey_step AS s2 ON s2.group_alias=s1.group_alias')
      ->join('LEFT', '#__odyssey_departure_step_map AS ds ON ds.step_id=s1.id')
      ->join('LEFT', '#__odyssey_transit_city_price AS ap ON ap.travel_id='.$travelId.' AND ap.dpt_step_id=tc.dpt_step_id'.
	             ' AND ap.city_id=tc.city_id AND ap.dpt_id=tc.dpt_id')
      //Get both the transit and departure city names.
      ->join('LEFT', '#__odyssey_city AS c ON c.id=ds.city_id')
      ->join('LEFT', '#__odyssey_city AS tcn ON tcn.id=tc.city_id')
      ->where('tc.dpt_id=ds.dpt_id')
      ->where('tc.dpt_step_id=s2.id')
      //Order the result rows according to the way we want to display them. 
      ->order('tc.dpt_step_id, tc.city_id, ds.date_time, ap.psgr_nb');
$db->setQuery($query);
$results = $db->loadAssocList();

$transitCityPriceRows = UtilityHelper::combinePriceRows($results);

setDateFormat($travelPriceRows, $dateType);
setDateFormat($addonPriceRows, $dateType);
setDateFormat($transitCityPriceRows, $dateType);

$data['travel'] = $travelPriceRows;
$data['addons'] = $addonPriceRows;
$data['transitcities'] = $transitCityPriceRows;

echo json_encode($data);

