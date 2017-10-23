<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
 
// import Joomla table library
jimport('joomla.database.table');
require_once JPATH_COMPONENT.'/helpers/step.php';
require_once JPATH_COMPONENT.'/helpers/odyssey.php';


/**
 * Step table class
 */
class OdysseyTableStep extends JTable
{
  /**
   * Constructor
   *
   * @param object Database connector object
   */
  function __construct(&$db) 
  {
    parent::__construct('#__odyssey_step', 'id', $db);
  }


  /**
   * Overrides JTable::store to set modified data and user id.
   *
   * @param   boolean  $updateNulls  True to update fields even if they are null.
   *
   * @return  boolean  True on success.
   *
   * @since   11.1
   */
  public function store($updateNulls = false)
  {
    $table = JTable::getInstance('Step', 'OdysseyTable', array('dbo', $this->getDbo()));

    if($this->id != 0) {
      //Check the step type hasn't been modified (in case js failed and the step type drop
      //down list is usable again).
      if(!$table->load(array('id' => $this->id, 'step_type' => $this->step_type))) {
	$message = JText::_('COM_ODYSSEY_ERROR_MODIFIED_ITEM_TYPE');
	//Redirect to the step list instead of the step edit form.
	JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_odyssey&view=steps', false), $message, 'error');
	return false;
      }
    }

    //A departure step is about to be trashed, archived or unpublished.
    //Check if this departure step is part of a travel.
    if($this->step_type == 'departure' && $this->published != 1 && OdysseyHelper::isInTravel($this->id)) {
      $this->setError(JText::_('COM_ODYSSEY_WARNING_DEPARTURE_STEP_USED_IN_TRAVEL'));
      return false;
    }

    $post = JFactory::getApplication()->input->post->getArray();
    $addonIds = $transitCityIds = array();

    if($this->step_type == 'departure') {
      //Set the group alias of the departure step.
    
      //Create a sanitized group alias, (see stringURLSafe function for details).
      $this->group_alias = JFilterOutput::stringURLSafe($this->group_alias);
      //In case no group alias has been defined, create a sanitized group alias from the name field.
      if(empty($this->group_alias)) {
	$this->group_alias = JFilterOutput::stringURLSafe($this->name);
      }

      // Verify that the group alias is unique
      if($table->load(array('group_alias' => $this->group_alias, 'step_type' => $this->step_type))
	 && ($table->id != $this->id || $this->id == 0)) {
	$this->setError(JText::_('COM_ODYSSEY_ERROR_DEPARTURE_UNIQUE_GROUP_ALIAS'));
	return false;
      }

      // Verify that the travel code (if any) is unique
      $this->travel_code = preg_replace('/\s+/', '', $this->travel_code);
      if(!empty($this->travel_code) && $table->load(array('travel_code' => $this->travel_code)) 
	 && ($table->id != $this->id || $this->id == 0)) {
	$this->setError(JText::_('COM_ODYSSEY_DATABASE_ERROR_TRAVEL_UNIQUE_CODE'));
	return false;
      }

      $dptExists = false;
      //Check again the date time values (in case Javascript has failed).
      foreach($post as $key => $value) {
	//Handle the standard or every_year date types.
	if(preg_match('#^date_time_([0-9]+)$#', $key, $matches) || preg_match('#^ev_year_month_([0-9]+)$#', $key, $matches)) {
	  $idNb = $matches[1];
          //Confirm that at least one departure has been set.
	  $dptExists = true;

	  //Check the date_time value according to the date type.
	  if(preg_match('#^date_time_([0-9]+)$#', $key)) {
	    if($this->date_type == 'period') {
	      //Check only date (time value is not taken in account for period date type).
	      if(!preg_match('#^[0-9]{4}-[0-9]{2}-[0-9]{2}$#', $post['date_time_'.$idNb])) {
		$this->setError(JText::_('COM_ODYSSEY_ERROR_INVALID_DATETIME_VALUE'));
		return false;
	      }

	      if(!preg_match('#^[0-9]{4}-[0-9]{2}-[0-9]{2}$#', $post['date_time_2_'.$idNb])) {
		$this->setError(JText::_('COM_ODYSSEY_ERROR_INVALID_DATETIME_VALUE'));
		return false;
	      }

	      $checkPeriods = StepHelper::checkPeriods();
	      if(!$checkPeriods['result']) {
		$this->setError(JText::_($checkPeriods['lang_var']));
		return false;
	      }
	    }
	    else { //Standard date type.
	      if(!preg_match('#^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}$#', $post['date_time_'.$idNb])) {
		$this->setError(JText::_('COM_ODYSSEY_ERROR_INVALID_DATETIME_VALUE'));
		return false;
	      }
	    }
	  }
	  else { //reccuring date.
	    if(!preg_match('#^[0-9]{2}:[0-9]{2}$#', $post['ev_year_time_'.$idNb])) {
	      $this->setError(JText::_('COM_ODYSSEY_ERROR_INVALID_TIME_VALUE'));
	      return false;
	    }
	  }

	  if(!ctype_digit($post['max_passengers_'.$idNb]) || $post['max_passengers_'.$idNb] == 0) {
	    $this->setError(JText::_('COM_ODYSSEY_ERROR_INVALID_MAX_PASSENGERS_VALUE'));
	    return false;
	  }

	  if(!ctype_digit($post['allotment_'.$idNb])) {
	    $this->setError(JText::_('COM_ODYSSEY_ERROR_INVALID_ALLOTMENT_VALUE'));
	    return false;
	  }
	} //Check for possible transit cities.
	elseif(preg_match('#^transitcity_id_([0-9]+)$#', $key, $matches)) {
	  $idNb = $matches[1];
          //Check first that a city has been selected.
	  if(empty($value)) {
	    $this->setError(JText::_('COM_ODYSSEY_ERROR_NO_CITY_SELECTED'));
	    return false;
	  }

	  //Check for duplicate.
	  if(in_array($value, $transitCityIds)) {
	    $this->setError(JText::_('COM_ODYSSEY_ERROR_TRANSIT_CITY_DUPLICATE_ENTRY'));
	    return false;
	  }
	  else {
	    $transitCityIds[] = $value;
	  }

	  //Then check that the time values are properly set.
	  if(!preg_match('#^([0-9]{2}):([0-9]{2})$#', $post['transitcity_hr_mn_'.$idNb], $matches)) {
	    $this->setError(JText::_('COM_ODYSSEY_ERROR_INVALID_TIME_VALUE'));
	    return false;
	  }
	  else { //Check for minute value only (hour value can go until 99).
	    $minutes = $matches[1];
	    if(substr($minutes, 0, 1) != '0' && $minutes > 59) {
	      $this->setError(JText::_('COM_ODYSSEY_ERROR_INVALID_MINUTE_VALUE'));
	      return false;
	    }
	  }
	}
      }

      if(!$dptExists) {
	$this->setError(JText::_('COM_ODYSSEY_ERROR_NO_DEPARTURE_DEFINED'));
	return false;
      }
    }
    else { //link

      if(empty($this->dpt_step_id)) {
	$this->setError(JText::_('COM_ODYSSEY_ERROR_DEPARTURE_STEP_MISSING'));
	return false;
      }

      $cityExists = false;
      //Check again the time gap values (in case Javascript has failed).
      foreach($post as $key => $value) {
	//Check if the checkbox of the row has been selected.
	if(preg_match('#^dpt_id_([0-9]+)$#', $key, $matches)) {
	  $dptId = $matches[1];

	  if(!preg_match('#^[0-9]{1,3}$#', $post['days_'.$dptId])) {
	    $this->setError(JText::_('COM_ODYSSEY_ERROR_INVALID_DAYS_VALUE'));
	    return false;
	  }

	  if(!preg_match('#^([0-9]{2}):([0-9]{2})$#', $post['hr_mn_'.$dptId], $matches)) {
	    $this->setError(JText::_('COM_ODYSSEY_ERROR_INVALID_TIME_VALUE'));
	    return false;
	  }

	  $timeGap = $post['days_'.$dptId].':'.$matches[1].':'.$matches[2];
	  $result = StepHelper::getDaysHoursMinutes($timeGap, true);
	  if($result['hours'] > 23 || $result['minutes'] > 59) {
	    $this->setError(JText::_('COM_ODYSSEY_ERROR_INVALID_TIME_VALUE'));
	    return false;
	  }

	  if($result['days'] == 0 && $result['hours'] == 0 && $result['minutes'] == 0) {
	    $this->setError(JText::_('COM_ODYSSEY_ERROR_INVALID_TIME_GAP'));
	    return false;
	  }
	}

	if(preg_match('#^city_id_([0-9]+)$#', $key) && !empty($value)) {
	  //Confirm that at least one city has been selected.
	  $cityExists = true;
	}
      }

      if(!$cityExists) {
	$this->setError(JText::_('COM_ODYSSEY_ERROR_NO_CITY_SELECTED'));
	return false;
      }
    }

    //In both cases (departure, link) check for possible duplicate entry of addon.
    foreach($post as $key => $value) {
      if(preg_match('#^addon_id_([0-9]+)$#', $key, $matches) && !empty($value)) {
	$idNb = $matches[1];
	if(in_array($value, $addonIds)) {
	  $this->setError(JText::_('COM_ODYSSEY_ERROR_ADDON_DUPLICATE_ENTRY'));
	  return false;
	}
	else {
	  $addonIds[] = $value;
	}
      }
    }

    return parent::store($updateNulls);
  }
}


