<?php
/**
 * @package Odyssey Plugin
 * @copyright Copyright (c)2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access
defined('_JEXEC') or die('Restricted access');
// Import the JPlugin class
jimport('joomla.plugin.plugin');
require_once JPATH_ROOT.'/administrator/components/com_odyssey/helpers/odyssey.php';
require_once JPATH_ROOT.'/administrator/components/com_odyssey/helpers/step.php';
require_once JPATH_ROOT.'/administrator/components/com_odyssey/helpers/utility.php';
require_once JPATH_ROOT.'/administrator/components/com_odyssey/helpers/updatepricerows.php';
require_once JPATH_ROOT.'/components/com_odyssey/helpers/order.php';
require_once JPATH_ROOT.'/components/com_odyssey/helpers/travel.php';


class plgContentOdyssey extends JPlugin
{

  public function onContentBeforeSave($context, $data, $isNew)
  {
    if($context == 'com_odyssey.travel') { //TRAVEL
      if(!$isNew) {
	// Create a new query object.
	$db = JFactory::getDbo();
	$query = $db->getQuery(true);
	//Set the group alias of the linked step.
	$query->select('dpt_step_id')
	      ->from('#__odyssey_travel')
	      ->where('id='.(int)$data->id);
	$db->setQuery($query);
	$oldDptStepId = $db->loadResult();
	
	//If the step sequence has changes for this travel, all the price rows previously
	//linked to this travel must be deleted.
	if($data->dpt_step_id != $oldDptStepId) {
	  $query->clear();
	  $query->delete('#__odyssey_travel_price')
		->where('travel_id='.(int)$data->id);
	  $db->setQuery($query);
	  $db->execute();

	  $query->clear();
	  $query->delete('#__odyssey_addon_price')
		->where('travel_id='.(int)$data->id);
	  $db->setQuery($query);
	  $db->execute();

	  $query->clear();
	  $query->delete('#__odyssey_addon_option_price')
		->where('travel_id='.(int)$data->id);
	  $db->setQuery($query);
	  $db->execute();

	  $query->clear();
	  $query->delete('#__odyssey_transit_city_price')
		->where('travel_id='.(int)$data->id);
	  $db->setQuery($query);
	  $db->execute();
	}
      }

      return true;
    }
    elseif($context == 'com_odyssey.step') { //STEP

      if($data->step_type == 'link') {
	// Create a new query object.
	$db = JFactory::getDbo();
	$query = $db->getQuery(true);

	//Set the catid and group alias of the linked step.
	$query->select('catid, group_alias')
	      ->from('#__odyssey_step')
	      ->where('id='.(int)$data->dpt_step_id);
	$db->setQuery($query);
	$result = $db->loadObject();

	$data->catid = $result->catid;
	$data->group_alias = $result->group_alias;

	return true;
      }
    }
    elseif($context == 'com_odyssey.addon') { //ADDON

      /*if($data->addon_type == 'addon_option') {
	$data->group_nb = 'none';
	$data->option_type = '';
      }*/

      return true;
    }
    elseif($context == 'com_odyssey.pricerule') { //PRICE RULE

      return true;
    }
    else { //Hand over to Joomla.
      return true;
    }
  }


  public function onContentBeforeDelete($context, $data)
  {
    return true;
  }


  //Since the id of a new item is not known before being saved, the code which
  //links item ids to other item ids should be placed here.

  public function onContentAfterSave($context, $data, $isNew)
  {
    //Filter the sent event.

    if($context == 'com_odyssey.travel' || $context == 'com_odyssey.form') { //TRAVEL 
      //Check for travel order.
      $this->setOrderByTag($context, $data, $isNew);

      $post = JFactory::getApplication()->input->post->getArray();
      $travelPrices = $addonPrices = $addonOptionPrices = $transCityPrices = $filters = $country = $region = $city = array();
      //Create a mapping between filter and column names.
      $filterMapping = array('country' => 'country_code', 'region' => 'region_code', 'city' => 'city_id');

      foreach($post as $key => $value) {
	//Clean up the price value.
	$value = trim($value);
	if(empty($value)) {
	  $value = '0.00';
	}

	//Get the travel prices by passenger number for each departure: price_psgr_[psgr_nb]_[dpt_id]
	if(preg_match('#^price_psgr_([0-9]+)_([0-9]+)$#', $key, $matches)) {
	  $psgrNb = $matches[1];
	  $dptId = $matches[2];

	  $row = new JObject;
	  $row->dpt_step_id = $data->dpt_step_id;
	  $row->dpt_id = $dptId;
	  $row->psgr_nb = $psgrNb;
	  $row->price = $value;
	  $travelPrices[] = $row;
	}

	//Get the addon prices by passenger number for each departure: price_psgr_[psgr_nb]_[step_id]_[addon_id]_[dpt_id]
	if(preg_match('#^price_psgr_([0-9]+)_([0-9]+)_([0-9]+)_([0-9]+)$#', $key, $matches)) {
	  $psgrNb = $matches[1];
	  $stepId = $matches[2];
	  $addonId = $matches[3];
	  $dptId = $matches[4];

	  $row = new JObject;
	  $row->step_id = $stepId;
	  $row->addon_id = $addonId;
	  $row->dpt_id = $dptId;
	  $row->psgr_nb = $psgrNb;
	  $row->price = $value;

	  $addonPrices[] = $row;
	}

	//Get the addon option prices by passenger number for each departure: price_psgr_[psgr_nb]_[step_id]_[addon_id]_[addon_option_id]_[dpt_id]
	if(preg_match('#^price_psgr_([0-9]+)_([0-9]+)_([0-9]+)_([0-9]+)_([0-9]+)$#', $key, $matches)) {
	  $psgrNb = $matches[1];
	  $stepId = $matches[2];
	  $addonId = $matches[3];
	  $addonOptionId = $matches[4];
	  $dptId = $matches[5];

	  $row = new JObject;
	  $row->step_id = $stepId;
	  $row->addon_id = $addonId;
	  $row->addon_option_id = $addonOptionId;
	  $row->dpt_id = $dptId;
	  $row->psgr_nb = $psgrNb;
	  $row->price = $value;

	  $addonOptionPrices[] = $row;
	}

	//Get the transit city prices by passenger number for each departure: //price_psgr_[psgr_nb]_[city_id]_[dpt_id]
	if(preg_match('#^price_psgr_([0-9]+)_([0-9]+)_([0-9]+)$#', $key, $matches)) {
	  $psgrNb = $matches[1];
	  $cityId = $matches[2];
	  $dptId = $matches[3];

	  $row = new JObject;
	  $row->dpt_step_id = $data->dpt_step_id;
	  $row->city_id = $cityId;
	  $row->dpt_id = $dptId;
	  $row->psgr_nb = $psgrNb;
	  $row->price = $value;

	  $transCityPrices[] = $row;
	}

	//Get the countries, regions or cities linked to the travel to be used as search filters. 
	//Note: Don't set the value row now as the different filter values must be get together. 
	if(preg_match('#^(country|region|city)_(code|id)_([0-9]+)$#', $key, $matches) && !empty($value)) {
	  //Use dynamical variable to set the corresponding array.
	  ${$matches[1]}[] = $value;
	}
      }

      $columns = array('travel_id','dpt_step_id','dpt_id','psgr_nb','price');
      OdysseyHelper::updateMappingTable('#__odyssey_travel_price', $columns, $travelPrices, array($data->id));

      $columns = array('travel_id','step_id','addon_id','dpt_id','psgr_nb','price');
      OdysseyHelper::updateMappingTable('#__odyssey_addon_price', $columns, $addonPrices, array($data->id));

      $columns = array('travel_id','step_id','addon_id','addon_option_id','dpt_id','psgr_nb','price');
      OdysseyHelper::updateMappingTable('#__odyssey_addon_option_price', $columns, $addonOptionPrices, array($data->id));

      $columns = array('travel_id','dpt_step_id','city_id','dpt_id','psgr_nb','price');
      OdysseyHelper::updateMappingTable('#__odyssey_transit_city_price', $columns, $transCityPrices, array($data->id));

      //Move on to the filter values.
      //First we need to know which filter has the highest number of items.
      $maxFilters = 0;
      //Note: Use dynamical variables to get the corresponding array.
      foreach($filterMapping as $filter => $column) {
	if($maxFilters < count(${$filter})) {
	  $maxFilters = count(${$filter});
	}
      }

      //Get the filter values together an set the row. 
      for($i = 0; $i < $maxFilters; $i++) {
	$row = new JObject;

	foreach($filterMapping as $filter => $column) {
	  //Set to null in case no item is defined for this filter.
	  $row->$column = null;
	  if(isset(${$filter}[$i])) {
	    $row->$column = ${$filter}[$i];
	  }
	}

	$filters[] = $row;
      }

      $columns = array('travel_id','country_code','region_code','city_id');
      OdysseyHelper::updateMappingTable('#__odyssey_search_filter', $columns, $filters, array($data->id));

      return true;
    }
    elseif($context == 'com_odyssey.step') { //STEP

      $post = JFactory::getApplication()->input->post->getArray();
      $dateType = $post['jform']['date_type'];

      //Initialize the needed arrays.
      $departures = $timeGaps = $cities = $addons = $addonIdNbMap = $addonDptIdNbMap = $addonDepartures = $currentDptIds = array();
      $transitCities = $transCityIdNbMap = $transCityDptIdNbMap = $transCityDepartures = $dptMaxPsgrMap = array();
      foreach($post as $key => $value) {
	//Handle data according to the step type.
	if($data->step_type == 'departure') {
	  //Handle the standard, period or every_year date types.
	  if(preg_match('#^date_time_([0-9]+)$#', $key, $matches) || preg_match('#^ev_year_month_([0-9]+)$#', $key, $matches)) {
	    $idNb = $matches[1];

	    //Set the date_time value according to the date type.
	    if(preg_match('#^date_time_([0-9]+)$#', $key, $matches)) {
	      //Set the value as it is.
	      $dateTime = $post['date_time_'.$idNb];
	    }
	    else { //reccuring date.
	      //Build the date_time value from the 3 separate values set by the user. 
	      $dateTime = '0000-'.$post['ev_year_month_'.$idNb].'-'.$post['ev_year_date_'.$idNb].' '.$post['ev_year_time_'.$idNb].':00';
	    }

	    $row = new JObject;
	    $row->city_id = $post['city_id_'.$idNb];
	    $row->dpt_id = $post['dpt_id_'.$idNb];
	    $row->date_time = $dateTime;
	    //Set the second date time as default (in case date type has changed).
	    $row->date_time_2 = '0000-00-00 00:00:00';
	    //If we're dealing with a period date type we set it to the value defined by the user.
	    if($data->date_type == 'period') {
	      $row->date_time_2 = $post['date_time_2_'.$idNb];
	    }

	    $row->max_passengers = $post['max_passengers_'.$idNb];
	    $row->allotment = $post['allotment_'.$idNb];
	    $row->altm_subtract = $post['altm_subtract_'.$idNb];
	    $departures[] = $row;

	    //The departure ids/max passengers mapping is needed for the price row update.
	    //Note: Departures newly added have not yet an id. The ids will be created and
	    //retrieved from the updateDepartures function.
	    if($post['dpt_id_'.$idNb] != 0) {
	      $dptMaxPsgrMap[$post['dpt_id_'.$idNb]] = $post['max_passengers_'.$idNb];
	      //Store the current departure ids to check against the checkbox departure ids
	      //of the addons and transit cities items.
	      $currentDptIds[] = $post['dpt_id_'.$idNb];
	    }
	  }

	  //Handle the every_month date type.
	  if(preg_match('#^ev_month_day_([0-9]+)$#', $key, $matches)) {
	    //TODO
	  }

	  if(preg_match('#^transitcity_id_([0-9]+)$#', $key, $matches) && !empty($value)) {
	    $idNb = $matches[1];
	    //Store the mapping between the transit city id and the id number.
	    $transCityIdNbMap[$value] = $idNb;
	  }

	  //Check for transit city departures.
	  if(preg_match('#^transitcity_dpt_([0-9]+)_([0-9]+)$#', $key, $matches)) {
	    $idNb = $matches[2];
	    //Store the mapping between the id number and the departure id.
	    $transCityDptIdNbMap[] = array('id_nb' => $idNb, 'dpt_id' => $value);
	  }
	}
	else { //link
	  //Handle the time gap value.
	  //Check if the checkbox of the row has been selected.
	  if(preg_match('#^dpt_id_([0-9]+)$#', $key, $matches)) {
	    //Note: Unlike other items, time gap item doesn't use an id number to 
	    //identified data but a departure id.
	    $dptId = $matches[1];

	    //Pad the beginning of the day number with zero filling 
	    //according to the length of the number.
	    $length = 3 - strlen($post['days_'.$dptId]); //Note: Number max length is 3.
	    $zeroFilling = '';
	    for($i = 0; $i < $length; $i++) {
	      $zeroFilling .= '0';
	    }

	    //Build the time_gap value from the 2 separate values set by the user. 
	    $timeGap = $zeroFilling.$post['days_'.$dptId].':'.$post['hr_mn_'.$dptId];

	    $row = new JObject;
	    $row->dpt_id = $dptId;
	    $row->time_gap = $timeGap;

	    $groupPrev = 0;
	    if(isset($post['group_prev_'.$dptId])) {
	      $groupPrev = 1;
	    }
	    $row->group_prev = $groupPrev;
	    $timeGaps[] = $row;

	    //Store the current departure ids to check against the checkbox departure ids
	    //of the addons and transit cities items.
	    $currentDptIds[] = $dptId;
	  }

	  //Handle the cities.
	  if(preg_match('#^city_id_([0-9]+)$#', $key, $matches)) {
	    $idNb = $matches[1];

	    $row = new JObject;
	    $row->city_id = $post['city_id_'.$idNb];
	    $row->ordering = $post['city_ordering_'.$idNb];
	    $cities[] = $row;
	  }
	}

	//Check for addons.
	//Note: Addons cannot be set here as we also need the addon departures id.
	//So for now we just store their id and id number.
	if(preg_match('#^addon_id_([0-9]+)$#', $key, $matches) && !empty($value)) {
	  $idNb = $matches[1];
	  //Store the mapping between the addon id and the id number.
	  $addonIdNbMap[$value] = $idNb;
	}

	//Check for addon departures.
	if(preg_match('#^addon_dpt_([0-9]+)_([0-9]+)$#', $key, $matches)) {
	  $idNb = $matches[2];
	  //Store the mapping between the id number and the departure id.
	  $addonDptIdNbMap[] = array('id_nb' => $idNb, 'dpt_id' => $value);
	}
      } //End foreach.

      //Set the addon objects according to the previously stored data.
      //Note: Addon data has 2 levels: The common data (id, name...) and the selected
      //departure ids for this addon. But for practical reason we store it as one level
      //data: The common data plus the first selected departure id and as many rows as 
      //extra departure ids.
      foreach($addonIdNbMap as $addonId => $idNb) {
	foreach($addonDptIdNbMap as $value) {
	  //Note: Ensure the checkbox departure id matches the current departure ids as this
	  //departure might has been just removed whereas the corresponding checkbox is still checked.
	  if($value['id_nb'] == $idNb && in_array($value['dpt_id'], $currentDptIds)) {
	    $row = new JObject;
	    $row->addon_id = $addonId;
	    $row->dpt_id = $value['dpt_id'];
	    $row->ordering = $post['addon_ordering_'.$idNb];
	    $addons[] = $row;
	  }
	}
      }

      $addonDptStates = array();
      foreach($addons as $addon) {
	if(!array_key_exists($addon->addon_id, $addonDptStates)) {
	  $addonDptStates[$addon->addon_id] = array($addon->dpt_id);
	}
	else {
	  $addonDptStates[$addon->addon_id][] = $addon->dpt_id;
	}
      }

      $db = JFactory::getDbo();
      $query = $db->getQuery(true);

      if($data->step_type == 'departure') {

	foreach($transCityIdNbMap as $cityId => $idNb) {
	  foreach($transCityDptIdNbMap as $value) {
	    //Note: Ensure the checkbox departure id matches the current departure ids as this
	    //departure might has been just removed whereas the corresponding checkbox is still checked.
	    if($value['id_nb'] == $idNb && in_array($value['dpt_id'], $currentDptIds)) {
	      $row = new JObject;
	      $row->city_id = $cityId;
	      $row->dpt_id = $value['dpt_id'];
	      $row->time_offset = $post['transitcity_hr_mn_'.$idNb];
	      $transitCities[] = $row;
	    }
	  }
	}

	$transCityStates = array();
	foreach($transitCities as $transitCity) {
	  if(!array_key_exists($transitCity->city_id, $transCityStates)) {
	    $transCityStates[$transitCity->city_id] = array($transitCity->dpt_id);
	  }
	  else {
	    $transCityStates[$transitCity->city_id][] = $transitCity->dpt_id;
	  }
	}

	$departures = StepHelper::checkAllotment($data->id, $departures);

	$newDptIdsMap = StepHelper::updateDepartures($data->id, $departures, $data->date_type, $isNew);

	if(!$isNew) {
	  //Get the linked steps.
	  $query->select('id')
		->from('#__odyssey_step')
		->where('dpt_step_id='.(int)$data->id);
	  $db->setQuery($query);
	  $ids = $db->loadColumn();

	  //Update the catid and group alias values of the linked steps.
	  if(!empty($ids)) {
	    $fields = array('group_alias='.$db->quote($data->group_alias),
			    'catid='.(int)$data->catid);

	    $query->clear();
	    $query->update('#__odyssey_step')
		  ->set($fields)
		  ->where('id IN('.implode(',', $ids).')');
	    $db->setQuery($query);
	    $db->execute();
	  }

	  //Add possible new departures to the mapping array.
          foreach($newDptIdsMap as $dptId => $maxPsgrs) {
	    $dptMaxPsgrMap[$dptId] = $maxPsgrs;
	  }

	  //If departure step is part of a travel we must update the travel prices.
          if(OdysseyHelper::isInTravel($data->id)) {
	    UpdatePriceRowsHelper::updateTravelPriceRows($data->id, $dptMaxPsgrMap, $addonDptStates, $transCityStates);
	  }
	}
      }
      else { //link
	$columns = array('step_id','dpt_id','time_gap','group_prev');
	OdysseyHelper::updateMappingTable('#__odyssey_timegap_step_map', $columns, $timeGaps, array($data->id));

	$columns = array('step_id','city_id','ordering');
	OdysseyHelper::updateMappingTable('#__odyssey_step_city_map', $columns, $cities, array($data->id));

	//
	if(!$isNew) {
          if(OdysseyHelper::isInTravel($data->dpt_step_id)) {
	    UpdatePriceRowsHelper::updateAddonPriceRows($data->dpt_step_id, $addonDptStates, $data->id);
	  }
	}
      }

      //Note: The dynamical item updates must be called after the price row updates as the previous state of the
      //table is needed to detect the removed items.

      $columns = array('step_id','addon_id','dpt_id','ordering');
      OdysseyHelper::updateMappingTable('#__odyssey_step_addon_map', $columns, $addons, array($data->id));

      $columns = array('dpt_step_id','city_id','dpt_id','time_offset');
      OdysseyHelper::updateMappingTable('#__odyssey_step_transit_city_map', $columns, $transitCities, array($data->id));

      return true;
    }
    elseif($context == 'com_odyssey.addon') { //ADDON
      $post = JFactory::getApplication()->input->post->getArray();
      $addonOptions = $addonIds = array();

      foreach($post as $key => $value) {
	//Check for addon options.
	if(preg_match('#^option_name_([0-9]+)$#', $key, $matches) && !empty($value)) {
	  $idNb = $matches[1];

	  //Set the published value (defined by a checkbox).
	  $published = 0;
	  if(isset($post['published_'.$idNb])) {
	    $published = 1;
	  }

	  //Store the addon option attributes.
	  $addonOption = array('id' => $post['option_id_'.$idNb], 
	                 'name' => $post['option_name_'.$idNb],  
	                 'published' => $published,  
	                 'ordering' => $post['option_ordering_'.$idNb]);

	  $addonOptions[] = $addonOption;
	}
      }

      OdysseyHelper::setAddonOptions($addonOptions, $data->id);

      if($data->addon_type == 'hosting') {
	//Get and store hosting attribute values.
	$jform = JFactory::getApplication()->input->post->get('jform', array(), 'array');
	$columns = array('addon_id', 'nb_persons');
	$row = new JObject;
	$row->nb_persons = $jform['nb_persons'];
	$hosting = array($row);
	OdysseyHelper::updateMappingTable('#__odyssey_addon_hosting', $columns, $hosting, array($data->id));
      }

      return true;
    }
    elseif($context == 'com_odyssey.pricerule') { //PRICE RULE

      $post = JFactory::getApplication()->input->post->getArray();
      $targets = $recipients = $conditions = $travelPriceRules = $travels = $priceRules = array();

      foreach($post as $key => $value) {
	if($data->prule_type == 'catalog') {
	  if($data->target == 'travel') {
	    //Note: travel pricerules cannot be set here as we also need the travel
	    //pricerule rows.
	    if(preg_match('#^target_id_([0-9]+)$#', $key, $matches)) {
	      $idNb = $matches[1];
	      //Create a mapping array. 
	      $travels[$idNb] = array('travel_id' => $value, 'dpt_step_id' => $post['dpt_step_id_'.$idNb]);
	    }

	    //Get the travel pricerule rows by passenger number for each departure: value_psgr_[psgr_nb]_[dpt_id]_[id_nb]
	    if(preg_match('#^value_psgr_([0-9]+)_([0-9]+)_([0-9]+)$#', $key, $matches)) {
	      $psgrNb = $matches[1];
	      $dptId = $matches[2];
	      $idNb = $matches[3];
	      //
	      $priceRules[] = array('psgr_nb' => $psgrNb, 'dpt_id' => $dptId, 'id_nb' => $idNb, 'value' => $value);
	    }
	  }
	  else { //travel_cat, addon, addon_option
	    if(preg_match('#^target_id_([0-9]+)$#', $key, $matches)) {
	      $idNb = $matches[1];

	      $row = new JObject;
	      $row->item_id = $value;
	      //Note: When target is set as addon or addon_option, 3 extra fields are defined.
	      $row->travel_ids = (isset($post['travel_ids_'.$idNb])) ? $post['travel_ids_'.$idNb] : '';
	      $row->dpt_nbs = (isset($post['dpt_nbs_'.$idNb])) ? $post['dpt_nbs_'.$idNb] : '';
	      $row->step_ids = (isset($post['step_ids_'.$idNb])) ? $post['step_ids_'.$idNb] : '';
	      //Common field for all target types.
	      $row->psgr_nbs = $post['psgr_nbs_'.$idNb];
	      $targets[] = $row;
	    }
	  }
	}
	else { //cart
	  if(preg_match('#^condition_id_([0-9]+)$#', $key, $matches)) {
	    $idNb = $matches[1];

	    $row = new JObject;
	    $row->item_id = $value;
	    $row->operator = $post['operator_'.$idNb];
	    //Set the proper field according to the condition.
	    $row->item_amount = (isset($post['condition_item_amount_'.$idNb])) ?  $post['condition_item_amount_'.$idNb] : 0;
	    $row->item_qty = (isset($post['condition_item_qty_'.$idNb])) ?  $post['condition_item_qty_'.$idNb] : 0;
	    $conditions[] = $row;
	  }

	  //Note: For now only cart amount is used as target so there is no need to
	  //handle it as it is set directly in the odyssey_pricerule table.
	}

	//Retrieve all the new set recipients from the POST array.
	if(preg_match('#^recipient_id_([0-9]+)$#', $key, $matches)) {
	  $row = new JObject;
	  $row->item_id = $value;
	  $recipients[] = $row;
	}
      }

      //Handle the travel price rule rows.
      foreach($travels as $idNb => $travel) {
	foreach($priceRules as $priceRule) {
	  if($priceRule['id_nb'] == $idNb) {
	    $row = new JObject;
	    $row->travel_id = $travel['travel_id'];
	    $row->dpt_step_id = $travel['dpt_step_id'];
	    $row->dpt_id = $priceRule['dpt_id'];
	    $row->psgr_nb = $priceRule['psgr_nb'];
	    $row->value = $priceRule['value'];
	    $travelPriceRules[] = $row;
	  }
	}
      }

      $columns = array('prule_id','travel_id','dpt_step_id','dpt_id','psgr_nb','value');
      OdysseyHelper::updateMappingTable('#__odyssey_travel_pricerule', $columns, $travelPriceRules, array($data->id));

      $columns = array('prule_id','item_id');
      OdysseyHelper::updateMappingTable('#__odyssey_prule_recipient', $columns, $recipients, array($data->id));

      $columns = array('prule_id','item_id','travel_ids','dpt_nbs','step_ids','psgr_nbs');
      OdysseyHelper::updateMappingTable('#__odyssey_prule_target', $columns, $targets, array($data->id));

      $columns = array('prule_id','item_id','operator','item_amount','item_qty');
      OdysseyHelper::updateMappingTable('#__odyssey_prule_condition', $columns, $conditions, array($data->id));

      return true;
    }
    elseif($context == 'com_odyssey.customer') { //CUSTOMER
      $jform = JFactory::getApplication()->input->post->get('jform', array(), 'array');
      //Add some variables into the odysseyprofile array.
      $jform['odysseyprofile']['firstname'] = $jform['firstname'];
      $jform['odysseyprofile']['customer_title'] = $jform['customer_title'];
      $jform['odysseyprofile']['id'] = $jform['id'];

      //Use the onUserAfterSave function of the profile plugin to update data.
      $dispatcher = JEventDispatcher::getInstance();
      JPluginHelper::importPlugin('user');
      $dispatcher->trigger('onUserAfterSave', array($jform, false, true, ''));

      return true;
    }
    elseif($context == 'com_odyssey.order') { //ORDER
      $post = JFactory::getApplication()->input->post->getArray();
      //Get passengers contained in the form of the order.
      $passengers = TravelHelper::checkInPassengers($post, $data->customer_id);

      //In case the administrator has decreased the number of passengers we remove rows
      //from the passenger array.
      $currentNbPsgr = count($passengers);
      while($data->nb_psgr < $currentNbPsgr) {
	$id = $currentNbPsgr - 1;
	unset($passengers[$id]);
	$currentNbPsgr--;
      }

      //Update passenger data for this order.
      OrderHelper::setPassengers($passengers, $data->id);

      return true;
    }
    else { //Hand over to Joomla.
      return true;
    }
  }


  public function onContentAfterDelete($context, $data)
  {
    //Filter the sent event.

    if($context == 'com_odyssey.travel') {
      // Create a new query object.
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);

      //Delete all the rows linked to the item id. 
      $query->delete('#__odyssey_travel_tag_map')
	    ->where('travel_id='.(int)$data->id);
      $db->setQuery($query);
      $db->execute();

      //Remove the deleted travel id from the mapping price tables.
      $query->clear();
      $query->delete('#__odyssey_travel_price')
	    ->where('travel_id='.(int)$data->id);
      $db->setQuery($query);
      $db->execute();

      $query->clear();
      $query->delete('#__odyssey_transit_city_price')
	    ->where('travel_id='.(int)$data->id);
      $db->setQuery($query);
      $db->execute();

      $query->clear();
      $query->delete('#__odyssey_addon_price')
	    ->where('travel_id='.(int)$data->id);
      $db->setQuery($query);
      $db->execute();

      $query->clear();
      $query->delete('#__odyssey_addon_option_price')
	    ->where('travel_id='.(int)$data->id);
      $db->setQuery($query);
      $db->execute();

      return true;
    }
    elseif($context == 'com_tags.tag') {

      return true;
    }
    elseif($context == 'com_odyssey.step') {
      // Create a new query object.
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);

      if($data->step_type == 'departure') {
	//When a departure step is deleted there are a lot of things to delete as well.
	//Let's start first with the departure step mapping table.
	$query->delete('#__odyssey_departure_step_map')
	      ->where('step_id='.(int)$data->id);
	$db->setQuery($query);
	$db->execute();

	//In case some transit cities are linked to this departure step.
	$query->clear();
	$query->delete('#__odyssey_step_transit_city_map')
	      ->where('dpt_step_id IN('.implode(',', $linkStepIds).')');
	$db->setQuery($query);
	$db->execute();

	//Now we have to check if there is any link type step associated to the departure step.
	$query->clear();
	$query->select('s2.id')
	      ->from('#__odyssey_step AS s1')
	      ->join('LEFT', '#__odyssey_step AS s2 ON s2.dpt_step_id=s1.id')
	      ->where('s1.id='.(int)$data->id)
	      ->where('s2.id IS NOT NULL');
	$db->setQuery($query);
	$linkStepIds = $db->loadColumn();
	
	//The possible link type steps must be deleted as well.
	if(!empty($linkStepIds)) {
	  //Let's start first by removing the deleted step ids from the mapping tables.
	  $query->clear();
	  $query->delete('#__odyssey_timegap_step_map')
		->where('step_id IN('.implode(',', $linkStepIds).')');
	  $db->setQuery($query);
	  $db->execute();

	  $query->clear();
	  $query->delete('#__odyssey_step_addon_map')
		->where('step_id IN('.implode(',', $linkStepIds).')');
	  $db->setQuery($query);
	  $db->execute();

	  $query->clear();
	  $query->delete('#__odyssey_step_city_map')
		->where('step_id IN('.implode(',', $linkStepIds).')');
	  $db->setQuery($query);
	  $db->execute();

	  //At last delete the link steps.
	  $query->clear();
	  $query->delete('#__odyssey_step')
		->where('id IN('.implode(',', $linkStepIds).')');
	  $db->setQuery($query);
	  $db->execute();
	}
      }
      else { //link
	//Remove the deleted step id from the mapping tables.
	$query->delete('#__odyssey_timegap_step_map')
	      ->where('step_id='.(int)$data->id);
	$db->setQuery($query);
	$db->execute();

	$query->clear();
	$query->delete('#__odyssey_step_addon_map')
	      ->where('step_id='.(int)$data->id);
	$db->setQuery($query);
	$db->execute();

	$query->clear();
	$query->delete('#__odyssey_step_city_map')
	      ->where('step_id='.(int)$data->id);
	$db->setQuery($query);
	$db->execute();
      }

      return true;
    }
    elseif($context == 'com_odyssey.addon') {
      if($data->addon_type == 'hosting') {
	//Remove the deleted addon id from the mapping tables.
	$db = JFactory::getDbo();
	$query = $db->getQuery(true);
	$query->delete('#__odyssey_addon_hosting')
	      ->where('addon_id='.(int)$data->id);
	$db->setQuery($query);
	$db->execute();

	//Remove the options linked to the deleted addon.
	$query->clear();
	$query->delete('#__odyssey_addon_option')
	      ->where('addon_id='.(int)$data->id);
	$db->setQuery($query);
	$db->execute();
      }

      return true;
    }
    elseif($context == 'com_odyssey.pricerule') {
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);

      //Remove the deleted price rule id from the mapping tables.
      $query->delete('#__odyssey_prule_recipient')
	    ->where('prule_id='.(int)$data->id);
      $db->setQuery($query);
      $db->execute();

      $query->clear();
      $query->delete('#__odyssey_prule_target')
	    ->where('prule_id='.(int)$data->id);
      $db->setQuery($query);
      $db->execute();

      $query->clear();
      $query->delete('#__odyssey_prule_condition')
	    ->where('prule_id='.(int)$data->id);
      $db->setQuery($query);
      $db->execute();

      $query->clear();
      $query->delete('#__odyssey_travel_pricerule')
	    ->where('prule_id='.(int)$data->id);
      $db->setQuery($query);
      $db->execute();

      return true;
    }
    elseif($context == 'com_odyssey.order') {
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);

      //Remove the deleted order id from the mapping tables.
      $query->delete('#__odyssey_order_addon')
	    ->where('order_id='.(int)$data->id);
      $db->setQuery($query);
      $db->execute();

      $query->clear();
      $query->delete('#__odyssey_order_addon_option')
	    ->where('order_id='.(int)$data->id);
      $db->setQuery($query);
      $db->execute();

      $query->clear();
      $query->delete('#__odyssey_order_passenger')
	    ->where('order_id='.(int)$data->id);
      $db->setQuery($query);
      $db->execute();

      $query->clear();
      $query->delete('#__odyssey_order_transaction')
	    ->where('order_id='.(int)$data->id);
      $db->setQuery($query);
      $db->execute();

      $query->clear();
      $query->delete('#__odyssey_order_travel')
	    ->where('order_id='.(int)$data->id);
      $db->setQuery($query);
      $db->execute();

      return true;
    }
    else { //Hand over to Joomla.
      return true;
    }
  }


  public function onContentChangeState($context, $pks, $value)
  {
    //Filter the sent event.

    if($context == 'com_odyssey.travel') {
      return true;
    }
    else { //Hand over to Joomla.
      return true;
    }
  }


  /**
   * Create (or update) a row whenever an travel is tagged.
   * The travel/tag mapping allows to order the travel against a given tag. 
   *
   * @param   string   $context  The context of the content passed to the plugin (added in 1.6)
   * @param   object   $data     A JTableContent object
   * @param   boolean  $isNew    If the content is just about to be created
   *
   * @return  void
   *
   */
  private function setOrderByTag($context, $data, $isNew)
  {
    //Get the jform data.
    $jform = JFactory::getApplication()->input->post->get('jform', array(), 'array');

    // Create a new query object.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Check we have tags before treating data.
    if(isset($jform['tags'])) {
      //Retrieve all the rows matching the item id.
      $query->select('travel_id, tag_id, IFNULL(ordering, "NULL") AS ordering')
	    ->from('#__odyssey_travel_tag_map')
	    ->where('travel_id='.(int)$data->id);
      $db->setQuery($query);
      $tags = $db->loadObjectList();

      $values = array();
      foreach($jform['tags'] as $tagId) {
	$newTag = true; 
	//In order to preserve the ordering of the old tags we check if 
	//they match those newly selected.
	foreach($tags as $tag) {
	  if($tag->tag_id == $tagId) {
	    $values[] = $tag->travel_id.','.$tag->tag_id.','.$tag->ordering;
	    $newTag = false; 
	    break;
	  }
	}

	if($newTag) {
	  $values[] = $data->id.','.$tagId.',NULL';
	}
      }

      //Delete all the rows matching the item id.
      $query->clear();
      $query->delete('#__odyssey_travel_tag_map')
	    ->where('travel_id='.(int)$data->id);
      $db->setQuery($query);
      $db->execute();

      $columns = array('travel_id', 'tag_id', 'ordering');
      //Insert a new row for each tag linked to the item.
      $query->clear();
      $query->insert('#__odyssey_travel_tag_map')
	    ->columns($columns)
	    ->values($values);
      $db->setQuery($query);
      $db->execute();
    }
    else { //No tags selected or tags removed.
      //Delete all the rows matching the item id.
      $query->delete('#__odyssey_travel_tag_map')
	    ->where('travel_id='.(int)$data->id);
      $db->setQuery($query);
      $db->execute();
    }

    return;
  }
}

