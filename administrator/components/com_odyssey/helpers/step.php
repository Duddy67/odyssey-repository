<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.
require_once JPATH_ADMINISTRATOR.'/components/com_odyssey/helpers/odyssey.php';


class StepHelper
{
  public static function updateDepartures($stepId, $departures, $dateType, $isNew)
  {
    $removed = $newDptIdsMap = array();
    // Create a new query object.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    if($isNew) {
      //Departure ids are easy to create for new step items. 
      foreach($departures as $key => $departure) {
	$departures[$key]->dpt_id = $key + 1;
      }
//file_put_contents('debog_file.txt', print_r($departures, true));
    }
    else { //Departure ids might have to be computed again.

      //Get the departure ids of the departure dates previously set.
      $query->select('dpt_id')
	    ->from('#__odyssey_departure_step_map')
	    ->where('step_id='.(int)$stepId)
	    //Put the highest id at the begining of the array.
	    ->order('dpt_id DESC');
      $db->setQuery($query);
      $oldDptIds = $db->loadColumn();

      //Get the highest id of the departure dates.
      $highestId = $oldDptIds[0];

      //Check for removed departure dates.
      foreach($oldDptIds as $oldDptId) {
	$inside = false;
	//Check if the departure date is still part of the new selection.
	foreach($departures as $departure) {
	  if(in_array($departure->dpt_id, $oldDptIds)) {
	    $inside = true;
	    break;
	  }
	}

	if(!$inside) {
	  $removed[] = $oldDptId;
	}
      }

      //Set a brand new id for the departure recently added.
      foreach($departures as $key => $departure) {
	//Check for new departure dates.
	//Note: The id values of the new departures are zero.
	if(empty($departure->dpt_id)) {
	  $departures[$key]->dpt_id = ++$highestId;
	  //Set as well the mapping array needed for the price row update.
	  $newDptIdsMap[$departures[$key]->dpt_id] = $departures[$key]->max_passengers;
	}
      }
    }

    //Set fields.
    $columns = array('step_id','city_id','dpt_id','date_time','date_time_2','max_passengers','allotment','altm_subtract');
    /*elseif($dateType == 'every_month') { //Note: In a futur version. 
      $columns = array('step_id','city_id','dpt_id','day','day_nb','months','max_passengers','allotment','altm_subtract');
    }*/

    //Update departure table.
    OdysseyHelper::updateMappingTable('#__odyssey_departure_step_map', $columns, $departures, array($stepId));

    //Time gaps refering to the removed departure dates (in linked steps) must be deleted.
    if(!empty($removed)) {
      $query->clear();
      $query->select('id')
	    ->from('#__odyssey_step')
	    ->where('step_type != "departure"')
	    ->where('dpt_step_id='.(int)$stepId);
      $db->setQuery($query);
      $linkedStepIds = $db->loadColumn();

      if(!empty($linkedStepIds)) {
	$query->clear();
	$query->delete('#__odyssey_timegap_step_map')
	      ->where('step_id IN('.implode(',', $linkedStepIds).')')
	      ->where('dpt_id IN('.implode(',', $removed).')');
	$db->setQuery($query);
	$db->execute();
      }
    }

    //
    return $newDptIdsMap;
  }


  public static function getStepSequence($dptStepId, $departureNb)
  {
    // Create a new query object.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Retrieve the departure ids of the travel in the chronological order.
    $query->select('dpt_id, date_time')
	  ->from('#__odyssey_departure_step_map')
	  ->where('step_id='.(int)$dptStepId)
	  ->order('date_time');
    $db->setQuery($query);
    $departures = $db->loadObjectList();

    //Check that the given departure number matches.
    if(!isset($departures[$departureNb - 1])) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_WARNING_INVALID_DEPARTURE_STEP_ID'), 'warning');
      return array();
    }

    //Get the departure id corresponding to the given departure number (ie: 1, 2, 3 etc).
    $dptId = $departures[$departureNb - 1]->dpt_id;

    //Get some departure step data.
    //Note: name and description attributes are used on frontend.
    $query->clear();
    $query->select('published, name, description')
	  ->from('#__odyssey_step')
	  ->where('id='.(int)$dptStepId);
    $db->setQuery($query);
    $dptStep = $db->loadObject();

    //Get all the steps linked to the given departure step.
    $query->clear();
    $query->select('step_id, time_gap, group_prev, name, description')
	  ->from('#__odyssey_step')
	  ->join('INNER', '#__odyssey_timegap_step_map ON step_id=id AND dpt_id='.(int)$dptId)
	  ->where('dpt_step_id='.(int)$dptStepId.' AND published=1')
	  ->group('id')
	  ->order('time_gap');
    $db->setQuery($query);
    $stepSequence = $db->loadAssocList();

    //Add the departure step at the beginning of the sequence.
    array_unshift($stepSequence, array('step_id' => $dptStepId, 'dpt_id' => $dptId, 'time_gap' => '000:00:00',
				       'group_prev' => 0, 'published' => $dptStep->published, 
				       'name' => $dptStep->name, 'description' => $dptStep->description)); 

    return $stepSequence;
  }


  public static function getStepDuration($stepSequence, $zerofill = true)
  {
    $hrLimit = 24;
    $mnLimit = 60;
    $stepDuration = array();
    $lastStepIndex = count($stepSequence) - 1;

    foreach($stepSequence as $key => $step) {
      //The current step is grouped with the previous one.
      if((int)$step['group_prev']) {
	continue;
      }

      //It's the last step of the sequence.
      if($key == $lastStepIndex) {
	break;
      }

      //Get the time gap of the current step.
      $current = StepHelper::getDaysHoursMinutes($step['time_gap'], true);

      $i = 1;
      $index = 0;
      //Loop through the sequence and search for the next step wich is not grouped with
      //its predecessor. 
      while(isset($stepSequence[$key + $i])) {
	//Note: Take in account the case where the last step is grouped to its 
	//predecessor as we need its time gap data.
	if(!(int)$stepSequence[$key + $i]['group_prev'] || (($key + $i) == $lastStepIndex && (int)$stepSequence[$key + $i]['group_prev'])) {
	  //Save the index of the next step to work with.
	  $index = $key + $i;
	  break;
	}

	$i++;
      }

      if($index) {
	//Get the time gap of the next step.
	$next = StepHelper::getDaysHoursMinutes($stepSequence[$index]['time_gap'], true);
	//Day duration can be compute right away.
	$days = (int)$next['days'] - (int)$current['days'];

	//Check hours duration and readjust result if required.

	//We have one day too many in the result as there is less than 24 hours between
	//the 2 steps. 
	if((int)$next['hours'] < (int)$current['hours']) {
	  //Duration must be computed into hours.
	  $hours = ($hrLimit - (int)$current['hours']) + (int)$next['hours'];
	  //Remove a day from the result.
	  $days = $days - 1;
	}
	//Just add the remaining hours to the result.
	elseif((int)$next['hours'] > (int)$current['hours']) {
	  $hours = (int)$next['hours'] - (int)$current['hours'];
	}
	else { //equal
	  //Duration is exactly one day. Leave the result as it is.
	  $hours = 0;
	}

	//Check minutes duration and readjust result if required.

	//Same principle as above but adjusted for minutes.
	if((int)$next['minutes'] < (int)$current['minutes']) {
	  $minutes = ($mnLimit - (int)$current['minutes']) + (int)$next['minutes'];
	  $hours = $hours - 1;
	}
	elseif((int)$next['minutes'] > (int)$current['minutes']) {
	  $minutes = (int)$next['minutes'] - (int)$current['minutes'];
	}
	else { //equal
	  $minutes = 0;
	}

	if($zerofill) {
	  if($hours < 10) {
	    $hours = '0'.$hours;
	  }

	  if($minutes < 10) {
	    $minutes = '0'.$minutes;
	  }
	}

        //Store the final result for this step.
	$stepDuration[$step['step_id']] = array('days' => $days, 'hours' => $hours, 'minutes' => $minutes);
      }
    }

    return $stepDuration;
  }


  public static function getDaysHoursMinutes($timeGap, $cleanHrMn = false)
  {
    $result = array();
    //Extract days hours and minutes values from the time gap pattern.
    if(preg_match('#([0-9]{1,3}):([0-9]{2}):([0-9]{2})#', $timeGap, $matches)) {
      $days = $matches[1];
      $hours = $matches[2];
      $minutes = $matches[3];

      //Remove possible zero filling from the beginning of the number of days.
      if($days != '000') {
	preg_match('#^(0{0,2})([0-9]{1,3})#', $days, $matches); 
	$days = $matches[2];
      }
      else {
	$days = '0';
      }

      $result['days'] = $days;

      //Remove possible zero filling from the beginning of the numbers of hours and
      //minutes.
      if($cleanHrMn) {
	//if the first figure is zero it is removed.
	if(substr($hours, 0, -1) == '0') {
	  $hours = substr($hours, -1);
	}

	if(substr($minutes, 0, -1) == '0') {
	  $minutes = substr($minutes, -1);
	}
      }

      $result['hours'] = $hours;
      $result['minutes'] = $minutes;
    }

    return $result;
  }


  public static function getInTimeGapFormat($days, $hours, $minutes)
  {
    //Pad the beginning of the day number with zero filling 
    //according to the length of the number.
    $length = 3 - strlen((string)$days); //Note: Number max length is 3.
    $zeroFilling = '';
    for($i = 0; $i < $length; $i++) {
      $zeroFilling .= '0';
    }

    $days = $zeroFilling.$days;

    //Same thing for hours and minutes. Number max length is 2.

    if(strlen((string)$hours) == 1) {
      $hours = '0'.$hours;
    }

    if(strlen((string)$minutes) == 1) {
      $minutes = '0'.$minutes;
    }

    //Return days hours and minutes into a time gap format.
    return $days.':'.$hours.':'.$minutes;
  }


  public static function applyTimeOffset($timeOffset, $stepIds, $dptStepId, $dptNb)
  {
    //First run some tests to ensure we have correct data to work with.

    if(!is_array($stepIds)) {
      return false;
    }

    //Check we have exactly 2 step ids.
    if(count($stepIds) != 2) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_WARNING_INVALID_STEP_SELECTION'), 'warning');
      return false;
    }

    //Get the current step sequence.
    $stepSequence = StepHelper::getStepSequence($dptStepId, $dptNb);

    //Check the departure step is not a part of the given step ids.
    if($stepSequence[0]['step_id'] == $stepIds[0] || $stepSequence[0]['step_id'] == $stepIds[1]) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_WARNING_CANNOT_USE_DEPARTURE_STEP'), 'warning');
      return false;
    }

    //Check the time offset data is correct.
    if(!preg_match('#^([\+|-])([0-9]{1,2}):([0-9]{2}):([0-9]{2})$#', $timeOffset, $matches)) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_WARNING_INVALID_TIME_OFFSET'), 'warning');
      return false;
    }

    //Extract the time offset from the given data.
    $operator = $matches[1];

    //Build a time gap from the given days, hours and minutes.
    $timeOffset = '0'.$matches[2].':'.$matches[3].':'.$matches[4];
    $timeOffset = StepHelper::getDaysHoursMinutes($timeOffset, true);

    $hrLimit = 24;
    $mnLimit = 60;

    //Check that hours and minutes are properly set.
    if($timeOffset['hours'] >= $hrLimit || $timeOffset['minutes'] >= $mnLimit) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_WARNING_INVALID_HOURS_MINUTES'), 'warning');
      return false;
    }

    //Extract the sequence section to treat from the step sequence.
    $section = array();
    $isPart = false;
    foreach($stepSequence as $step) {
      if($isPart) {
	$section[] = $step;
	//This is the last step of the sequence section.
	if(in_array($step['step_id'], $stepIds)) {
	  //The sequence section is complete.
	  break;
	}
      }

      //This is the first step of the sequence section.
      if(!$isPart && in_array($step['step_id'], $stepIds)) {
	$section[] = $step;
	//From now on we store the following steps in the array.
	$isPart = true;
      }
    }

    $update = array();
    foreach($section as $step) {
      $timeGap = StepHelper::getDaysHoursMinutes($step['time_gap'], true);

      if($operator == '+') { //The easy one. :)
	//First add up the time offset and time gap values.
	$days = $timeOffset['days'] + $timeGap['days'];
	$hours = $timeOffset['hours'] + $timeGap['hours'];
	$minutes = $timeOffset['minutes'] + $timeGap['minutes'];

	//In case the result reaches or exceeds the limit.
	if($minutes >= $mnLimit) {
	  //Retrieve the remaining minutes from the subtraction.
	  $minutes = $minutes - $mnLimit;
	  //Add an extra hour.
	  $hours = $hours + 1;
	}

	//Same thing for hours.
	if($hours >= $hrLimit) {
	  $hours = $hours - $hrLimit;
	  $days = $days + 1;
	}

	//Just in case.
	if($days > 999) {
	  JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_WARNING_TIME_OFFSET_TOO_HIGHT'), 'warning');
	  return false;
	}
      }
      else { //minus. The tricky one. :)

	//First let's rule out the cases where the result will be zero or less than zero.
	if($timeOffset['days'] > $timeGap['days'] || ($timeOffset['days'] == $timeGap['days'] &&
	   $timeOffset['hours'] == $timeGap['hours'] && $timeOffset['minutes'] == $timeGap['minutes'])) {
	  JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_WARNING_TIME_OFFSET_TOO_HIGHT'), 'warning');
	  return false;
	}

	//We have to go back to the previous 60 minute cycle and thus remove 1 hour.
	if($timeOffset['minutes'] > $timeGap['minutes']) {
	  //Hours cannot be decreased (as day value is zero).
	  if($timeGap['days'] == 0 && $timeGap['hours'] == 0) {
	    JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_WARNING_TIME_OFFSET_TOO_HIGHT'), 'warning');
	    return false;
	  }

	  //Retrieve the remaining minutes from the subtraction.
	  $minutes = ($timeGap['minutes'] + $mnLimit) - $timeOffset['minutes'];

	  //We are at the beginning of a 24 hour cycle.
	  if($timeGap['hours'] == 0) {
	    //Return to the previous 24 hour cycle.
	    $timeGap['hours'] = 23;
	    //Decrease day value.
	    $timeGap['days'] = $timeGap['days'] - 1;
	  }
	  else { //Just decrease hour value.
	    $timeGap['hours'] = $timeGap['hours'] - 1;
	  }
	}
	else { //We're in the same 60 minute cycle.
	  //Just subtract time offset value from the current minute value.
	  $minutes = $timeGap['minutes'] - $timeOffset['minutes'];
	}

	//We have to go back to the previous 24 hour cycle and thus remove 1 day.
	if($timeOffset['hours'] > $timeGap['hours']) {
	  $hours = ($timeGap['hours'] + $hrLimit) - $timeOffset['hours'];
	  $timeGap['days'] = $timeGap['days'] - 1;

	  //Check again against the time offset.
	  if($timeOffset['days'] > $timeGap['days']) {
	    JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_WARNING_TIME_OFFSET_TOO_HIGHT'), 'warning');
	    return false;
	  }
	}
	else { //We're in the same 24 hour cycle.
	  //Just subtract time offset value from the current hour value.
	  $hours = $timeGap['hours'] - $timeOffset['hours'];
	}

	//Finaly subtract time offset value from the current day value.
	$days = $timeGap['days'] - $timeOffset['days'];
      }

      //Store result.
      $newTimeGap = StepHelper::getInTimeGapFormat($days, $hours, $minutes);
      $update[] = array('step_id' => $step['step_id'], 'time_gap' => $newTimeGap);
    }

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Build the WHEN condition.
    $when = '';
    $ids = array();
    foreach($update as $step) {
      $when .= 'WHEN step_id = '.$step['step_id'].' THEN '.$db->Quote($step['time_gap']).' ';
      $ids[] = $step['step_id'];
    }

    //Update the steps of the section with their new time gap.
    $query->update('#__odyssey_timegap_step_map')
	  ->set('time_gap = CASE '.$when.' END ')
	  ->where('step_id IN('.implode(',', $ids).')')
	  ->where('dpt_id='.(int)$stepSequence[0]['dpt_id']);
//echo $query->__toString();
    $db->setQuery($query);
    $db->execute();

    return;
  }


  public static function checkPeriods()
  {
    $post = JFactory::getApplication()->input->post->getArray();
    $periods = array();
    //First create the period array with the "to" and "from" date times for each array index.
    foreach($post as $key => $value) {
      if(preg_match('#^date_time_([0-9]+)$#', $key, $matches)) {
	$idNb = $matches[1];
	$periods[] = array('from' => $post['date_time_'.$idNb], 'to' => $post['date_time_2_'.$idNb], 'id_nb' => $idNb);
      }
    }

    //Then sort the period array by descending "from" date times.
    //In order to do so we use a simple bubble sort algorithm.
    $nbIds = count($periods);
    for($i = 0; $i < $nbIds; $i++) {
      for($j = 0; $j < $nbIds - 1; $j++) {
	if($periods[$j]['from'] > $periods[$j + 1]['from']) {
	  $temp = $periods[$j + 1];
	  $periods[$j + 1] = $periods[$j];
	  $periods[$j] = $temp;
	}
      }
    }

    //Perform different checkings. An array of result is returned which allows to provided
    //informations about the error.

    //Check time periods between "from" and "to" date times.
    foreach($periods as $period) {
      if($period['from'] >= $period['to']) {
	return array('result' => false, 'error' => 'invalid_time_period', 'lang_var' => 'COM_ODYSSEY_ERROR_INVALID_TIME_PERIOD');
      }
    }

    //Check for overlapping periods between a "to" date time and the next "from" date time.
    for($i = 0; $i < $nbIds - 1; $i++) {
      if($periods[$i]['to'] >= $periods[$i + 1]['from']) {
	return array('result' => false, 'error' => 'overlapping', 'lang_var' => 'COM_ODYSSEY_ERROR_OVERLAPPING_PERIODS');
      }
    }

    return array('result' => true);
  }


  public static function checkAllotment($stepId, $departures)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Get the current departure data. 
    $query->select('dpt_id, allotment, altm_locked')
	  ->from('#__odyssey_departure_step_map')
	  ->where('step_id='.(int)$stepId);
    $db->setQuery($query);
    $allotments = $db->loadObjectList();

    $isLocked = false;
    foreach($departures as $key => $departure) {
      foreach($allotments as $allotment) {
	//Allotment value has been modified while an admin is editing departures.
	if($allotment->dpt_id == $departure->dpt_id && $allotment->altm_locked == 1) {
	  //The new allotment value is not taken in account and set to the current value
	  //instead.
	  $departures[$key]->allotment = $allotment->allotment;
	  $isLocked = true;
	}
      }
    }

    if($isLocked) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_NOTICE_ALLOTMENT_LOCKED'), 'Notice');
      //Reset all the altm_locked flags linked to this step.
      $query->clear();
      $query->update('#__odyssey_departure_step_map')
	    ->set('altm_locked=0')
	    ->where('step_id='.(int)$stepId);
      $db->setQuery($query);
      $db->execute();
    }

    return $departures;
  }
}

