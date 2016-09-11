<?php

//Initialize the Joomla framework
define('_JEXEC', 1);
//First we get the number of letters we want to substract from the path.
$length = strlen('/administrator/components/com_odyssey/js');
//Turn the length number into a negative value.
$length = $length - ($length * 2);
//
define('JPATH_BASE', substr(dirname(__DIR__), 0, $length));

//Get the required files
require_once (JPATH_BASE.'/includes/defines.php');
require_once (JPATH_BASE.'/includes/framework.php');
//We need to use Joomla's database class 
require_once (JPATH_BASE.'/libraries/joomla/factory.php');
require_once (JPATH_BASE.'/administrator/components/com_odyssey/helpers/odyssey.php');
require_once (JPATH_BASE.'/administrator/components/com_odyssey/helpers/step.php');
//Create the application
$mainframe = JFactory::getApplication('site');
$mainframe->initialise();


function setDepartureCheckboxes($items, $dptCheckboxes) {
  foreach($items as $key1 => $item) {
    //First, store the available departures of the step as an internal array.
    $items[$key1]['departures'] = $dptCheckboxes;
    //Determine which of those departures are selected for this item.
    foreach($items[$key1]['departures'] as $key2 => $departure) {
      //Check against the dpt id array previously saved.
      if(in_array($departure['dpt_id'], $item['dpt_ids'])) {
	$items[$key1]['departures'][$key2]['selected'] = 1;
      }
      else {
	$items[$key1]['departures'][$key2]['selected'] = '';
      }
    }
  }

  return $items;
}

//Get the required variables.
$stepId = JFactory::getApplication()->input->get->get('step_id', 0, 'uint');
$stepType = JFactory::getApplication()->input->get->get('step_type', '', 'str');
$dptStepId = JFactory::getApplication()->input->get->get('dpt_step_id', 0, 'uint');
$dateType = JFactory::getApplication()->input->get->get('date_type', '', 'str');
$checkStatus = JFactory::getApplication()->input->get->get('check_status', 0, 'uint');

//Get data of each item type.
$data = $departures = $cities = $transitCities = array();

//Called from the checkStepData js function.
if($checkStatus) {
  $data['is_in_travel'] = OdysseyHelper::isInTravel($dptStepId);

  echo json_encode($data);
  return;
}

$db = JFactory::getDbo();
$query = $db->getQuery(true);

if($stepType == 'departure') {
  $query->select('*')
	->from('#__odyssey_departure_step_map')
	->where('step_id='.(int)$stepId)
	->order('date_time');
  $db->setQuery($query);
  $results = $db->loadAssocList();

  //Build departure data according to the date type.
  foreach($results as $result) {
    if($dateType == 'standard' || $dateType == 'period') {
      //Remove seconds from date time.
      $regex = '#([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2})#';
      if($dateType == 'period') {
	//Remove time value as it is not used with period date type. 
	$regex = '#([0-9]{4}-[0-9]{2}-[0-9]{2})#';
      }

      preg_match($regex, $result['date_time'], $matches);
        $dateTime = $matches[1];
      preg_match('#([0-9]{4}-[0-9]{2}-[0-9]{2})#', $result['date_time_2'], $matches);
        $dateTime2 = $matches[1];

      $departures[] = array('date_time' => $dateTime, 'date_time_2' => $dateTime2, 'city_id' => $result['city_id'],
			    'dpt_id' => $result['dpt_id'], 'max_passengers' => $result['max_passengers'],
			    'allotment' => $result['allotment'], 'altm_subtract' => $result['altm_subtract'],
			    'dpt_step_alias' => $result['dpt_step_alias']);
    }
    elseif($dateType == 'every_year') {
      //Extract month date and time values from the datetime value.
      preg_match('#0000-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2})#', $result['date_time'], $matches);
      $departures[] = array('month' => $matches[1], 'date' => $matches[2], 'time' => $matches[3].':'.$matches[4],
			    'city_id' => $result['city_id'], 'dpt_id' => $result['dpt_id'],
			    'max_passengers' => $result['max_passengers'],
			    'dpt_step_alias' => $result['dpt_step_alias']);
    }
    else { //every_month
    }
  }

  //Check for possible transit cities.
  $query->clear();
  $query->select('city_id, dpt_id, time_offset')
	->from('#__odyssey_step_transit_city_map')
	->where('dpt_step_id='.(int)$stepId)
	->order('city_id');
  $db->setQuery($query);
  $results = $db->loadAssocList();

  //Transit cities linked to steps are stored on one level (one row for each selected departure).
  //We have to turn it into a 2 levels array (with an internal departure array).

  $transitCityIds = array();
  if(!empty($results)) {
    foreach($results as $key => $result) {
      //Store the common data (id, name...) from the first row of each transit city.
      if(!in_array($result['city_id'], $transitCityIds)) {
	$transitCities[] = array('city_id' => $result['city_id'],
				 'hr_mn' => $result['time_offset'],
				 //Store the first departure id (to which this transit city is linked) in an internal array.
				 'dpt_ids' => array($result['dpt_id']));
	$transitCityIds[] = $result['city_id'];
      }
      else {
	//Store the next departure ids in the internal array of the current array.
	$transitCities[count($transitCities) - 1]['dpt_ids'][] = $result['dpt_id'];
      }
    }
  }
}
else { //link
  $query->select('city_id, ordering AS city_ordering')
	->from('#__odyssey_step_city_map')
	->where('step_id='.(int)$stepId)
	->order('ordering');
  $db->setQuery($query);
  $cities = $db->loadAssocList();
}

//Check for possible addons.
$query->clear();
$query->select('sa.addon_id, sa.dpt_id, sa.ordering, a.name, a.published')
      ->from('#__odyssey_step_addon_map AS sa')
      ->join('LEFT', '#__odyssey_addon AS a ON a.id=sa.addon_id')
      ->where('sa.step_id='.(int)$stepId)
      ->order('sa.ordering');
$db->setQuery($query);
$results = $db->loadAssocList();

//Addons linked to steps are stored on one level (one row for each selected departure).
//We have to turn it into a 2 levels array (with an internal departure array).

$addons = $addonIds = array();
if(!empty($results)) {
  foreach($results as $key => $result) {
    //Store the common data (id, name...) from the first row of each addon.
    if(!in_array($result['addon_id'], $addonIds)) {
      $addons[] = array('addon_id' => $result['addon_id'],
			'addon_ordering' => $result['ordering'],
			'addon_name' => $result['name'],
			'addon_status' => $result['published'],
			//Store the first departure id (to which this addon is linked) in an internal array.
			'dpt_ids' => array($result['dpt_id']));
      $addonIds[] = $result['addon_id'];
    }
    else {
      //Store the next departure ids in the internal array of the current array.
      $addons[count($addons) - 1]['dpt_ids'][] = $result['dpt_id'];
    }
  }
}

if(!empty($transitCities) || !empty($addons)) {
  //Set variables according to the step type.
  $prefix = 'tg';
  if($stepType == 'departure') {
    //The step id is the departure step id.
    $dptStepId = $stepId;
    $prefix = 'd';
  }

  //Get all the departures set in the departure step. The result is used as a pattern with
  //the addon departure ids (ie: departure checkboxes).
  $query->clear();
  $query->select('d.dpt_id, IFNULL('.$prefix.'.dpt_id, "") AS active')
	->from('#__odyssey_departure_step_map AS d');
  //Get the active (ie: stored) time gap rows for this step.
  if($stepType == 'link') {
    $query->join('LEFT', '#__odyssey_timegap_step_map AS tg ON tg.dpt_id=d.dpt_id AND tg.step_id='.(int)$stepId);
  }

  $query->where('d.step_id='.(int)$dptStepId)
	->order('d.date_time');
  $db->setQuery($query);
  $dptCheckboxes = $db->loadAssocList();
  //file_put_contents('debog_file.txt', print_r($query->__toString(), true));

  $transitCities = setDepartureCheckboxes($transitCities, $dptCheckboxes);
  $addons = setDepartureCheckboxes($addons, $dptCheckboxes);
}

//Get results as a list of associative arrays and put them into the data array.
$data['departure'] = $departures;
$data['addon'] = $addons;
$data['transitcity'] = $transitCities;
$data['city'] = $cities;

echo json_encode($data);


