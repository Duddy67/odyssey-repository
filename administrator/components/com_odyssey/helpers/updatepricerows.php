<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.


class UpdatePriceRowsHelper
{
  //Whenever a departure is removed or added and/or the "max passengers" value is modified
  //in a departure step, the changes must be echoed in the price tables by adding and/or removing some price rows.
  //Important: Price rows are added only for travel prices. 
  //           For addons and transit cities price rows are just deleted. 
  public static function updateTravelPriceRows($dptStepId, $travelState, $addonDptStates, $transCityStates)
  {
    //Get the travel price rows from the given departure step id.
    //Note: Multiple travels can use the same departure step id.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('dpt_id, MAX(psgr_nb) AS psgr_nb')
	  ->from('#__odyssey_travel_price')
	  ->where('dpt_step_id='.(int)$dptStepId)
	  ->group('dpt_id');
    $db->setQuery($query);
    $results = $db->loadAssocList();

    if(empty($results)) {
      //return;
    }
//file_put_contents('debog_query.txt', print_r($query->__toString(), true));
//file_put_contents('debog_query.txt', print_r($results, true));
 
    //Run tests to detect which are the differences between the old and the new state.
    $travelOldState = $dptsToRemove = $dptsToAdd = $psgrsToRemove = $psgrsToAdd = array();

    foreach($results as $result) {
      //Takes the advantage of the loop to build a mapping array from 
      //the travel price rows previously returned.
      $travelOldState[$result['dpt_id']] = $result['psgr_nb'];

      //Check for the price rows to remove.
      $isIn = false;
      foreach($travelState as $dptId => $maxPsgrs) {
	if($dptId == $result['dpt_id']) {
	  //The departure still exists in the new setting.
	  $isIn = true;
	  break;
	}
      }

      //The departure is no longer in the new setting, so the corresponding 
      //price rows must be removed.
      if(!$isIn) {
	//Build a simple array of ids.
	$dptsToRemove[] = $result['dpt_id'];
      }
    }

//file_put_contents('debog_newsetting.txt', print_r($travelState, true));
//file_put_contents('debog_oldsetting.txt', print_r($travelOldState, true));
    foreach($travelState as $dptId1 => $maxPsgrs1) {
      $isIn = false;
      foreach($travelOldState as $dptId2 => $maxPsgrs2) {
	//The price row still exists in the new setting.
	if($dptId2 == $dptId1) {
	  $isIn = true;

	  //Check if the max passengers number has changed for this departure.
	  if($maxPsgrs2 < $maxPsgrs1) {
	    //Create a mapping array with the passenger numbers data. 
	    $nbToAdd = $maxPsgrs1 - $maxPsgrs2;
	    $psgrsToAdd[$dptId2] = array('current_nb' => $maxPsgrs2, 'nb_to_add' => $nbToAdd);
	  }

	  if($maxPsgrs2 > $maxPsgrs1) {
	    $nbToRemove = $maxPsgrs2 - $maxPsgrs1;
	    $psgrsToRemove[$dptId2] = array('current_nb' => $maxPsgrs2, 'nb_to_remove' => $nbToRemove);
	  }
	}
      }

      //A new departure has been added, so the corresponding travel 
      //price rows must be added as well.
      if(!$isIn) {
	//Create a mapping array.
	$dptsToAdd[$dptId1] = $maxPsgrs1;
      }
    }

    //The possible inserting queries require the travel ids.
    if(!empty($dptsToAdd) || !empty($psgrsToAdd)) {
      //Get the travel ids using the given departure step id.
      $query->clear();
      $query->select('id')
	    ->from('#__odyssey_travel')
	    ->where('dpt_step_id='.(int)$dptStepId);
      $db->setQuery($query);
      $travelIds = $db->loadColumn();
    }

    //Get the step ids in which one or more addons are included.
    //In doing so we can also delete the price rows of the possible addons which 
    //are included into link type steps.
    $query->clear();
    $query->select('s1.id')
	  ->from('#__odyssey_step AS s')
	  ->join('LEFT', '#__odyssey_step AS s1 ON s1.group_alias=s.group_alias')
	  ->join('INNER', '#__odyssey_step_addon_map AS sa ON sa.step_id=s1.id')
	  ->where('s.id='.(int)$dptStepId)
	  ->group('s1.id');
    $db->setQuery($query);
    $stepIds = $db->loadColumn();

    //If no addon is linked to the steps we put the departure step id into the array to
    //prevent a MySQL error when deleting.  
    if(empty($stepIds)) {
      $stepIds[] = $dptStepId;
    }

    //Run the appropriate queries according to the new state of the departure step.

    if(!empty($dptsToRemove)) {
//file_put_contents('debog_dptstoremove.txt', print_r($dptsToRemove, true));
      $query->clear();
      //Remove the travel price rows corresponding to the removed departure(s).
      $query->delete('#__odyssey_travel_price')
	    //->where('travel_id IN('.implode(',', $travelIds).')')
	    ->where('dpt_step_id='.(int)$dptStepId)
	    ->where('dpt_id IN('.implode(',', $dptsToRemove).')');
      $db->setQuery($query);
      $db->execute();

      //Remove the addon and addon option price rows corresponding to the removed departure(s).
      $query->clear();
      $query->delete('#__odyssey_addon_price')
	    //->where('travel_id IN('.implode(',', $travelIds).')')
	    ->where('step_id IN('.implode(',', $stepIds).')')
	    ->where('dpt_id IN('.implode(',', $dptsToRemove).')');
      $db->setQuery($query);
      $db->execute();

      $query->clear();
      $query->delete('#__odyssey_addon_option_price')
	    //->where('travel_id IN('.implode(',', $travelIds).')')
	    ->where('step_id IN('.implode(',', $stepIds).')')
	    ->where('dpt_id IN('.implode(',', $dptsToRemove).')');
      $db->setQuery($query);
      $db->execute();

      //Remove the transit city price rows corresponding to the removed departure(s).
      $query->clear();
      $query->delete('#__odyssey_transit_city_price')
	    //->where('travel_id IN('.implode(',', $travelIds).')')
	    ->where('dpt_step_id='.(int)$dptStepId)
	    ->where('dpt_id IN('.implode(',', $dptsToRemove).')');
      $db->setQuery($query);
      $db->execute();
    }

    if(!empty($dptsToAdd)) {
//file_put_contents('debog_dptstoadd.txt', print_r($dptsToAdd, true));
      //Build the VALUES clause of the INSERT MySQL query.
      $values = array();
      $columns = array('travel_id','dpt_step_id','dpt_id','psgr_nb','price');

      foreach($travelIds as $travelId) {
	//Loop through the mapping array.
	foreach($dptsToAdd as $dptId => $maxPsgrs) {
	  //A new price row must be inserted for each passenger.
	  for($i = 0; $i < $maxPsgrs; $i++) {
	    $psgrNb = $i + 1; //Compute the passenger number.
	    $row = '';
	    //Create a new price row.
	    $row .= $travelId.','.$dptStepId.','.$dptId.','.$psgrNb.',0.00';
	    //Insert a new row in the "values" clause.
	    $values[] = $row;
	  }
	}
      }

      //Run the MySQL insert query.
      $query->clear();
      //Add the travel price rows corresponding to the added departure(s).
      $query->insert('#__odyssey_travel_price')
	    ->columns($columns)
	    ->values($values);
      $db->setQuery($query);
      $db->execute();

      //Note: We don't add any price rows for addon or transit city items. 
    }

    if(!empty($psgrsToRemove)) {
//file_put_contents('debog_psgrstoremove.txt', print_r($psgrsToRemove, true));
      //Build the WHERE clause.
      $where1 = $where2 = '';
      //Loop through the mapping array.
      foreach($psgrsToRemove as $dptId => $data) {
	$in = '';
	//Compute the passenger numbers that must be removed.
	for($i = 0; $i < $data['nb_to_remove']; $i++) {
	  $psgrNbToRemove = $data['current_nb'] - $i;
	  $in .= $psgrNbToRemove.',';
	}
	//Remove comma from the end of the string.
	$in = substr($in, 0, -1);
	//Build a WHERE clause for each departure in which the max passengers 
	//value has been shorten. 

	//Used for travel and transit city price rows.
	$where1 .= '(dpt_step_id='.$dptStepId.' AND dpt_id='.$dptId.' AND psgr_nb IN('.$in.')) OR ';
	//Used for addon and addon option price rows.
        $where2 .= '(step_id IN('.implode(',', $stepIds).') AND dpt_id='.$dptId.' AND psgr_nb IN('.$in.')) OR ';
      }

      //Remove OR condition (and spaces) from the end of the string.
      $where1 = substr($where1, 0, -4);
      $where2 = substr($where2, 0, -4);

      $query->clear();
      //Remove the travel price rows corresponding to the removed passenger(s).
      $query->delete('#__odyssey_travel_price')
	    ->where($where1);
      $db->setQuery($query);
      $db->execute();

      $query->clear();
      //Remove the addon and addon option price rows corresponding to the removed passenger(s).
      $query->delete('#__odyssey_addon_price')
	    ->where($where2);
      $db->setQuery($query);
      $db->execute();

      $query->clear();
      $query->delete('#__odyssey_addon_option_price')
	    ->where($where2);
      $db->setQuery($query);
      $db->execute();

      $query->clear();
      //Remove the transit city price rows corresponding to the removed passenger(s).
      $query->delete('#__odyssey_transit_city_price')
	    ->where($where1);
      $db->setQuery($query);
      $db->execute();
    }

    if(!empty($psgrsToAdd)) {
//file_put_contents('debog_psgrstoadd.txt', print_r($psgrsToAdd, true));
      //Build the VALUES clause of the INSERT MySQL query.
      $values = array();
      $columns = array('travel_id','dpt_step_id','dpt_id','psgr_nb','price');

      foreach($travelIds as $travelId) {
	//Loop through the mapping array.
	foreach($psgrsToAdd as $dptId => $data) {
	  //Compute the passenger numbers that must be added.
	  for($i = 0; $i < $data['nb_to_add']; $i++) {
	    $psgrNb = $data['current_nb'] + $i + 1;
	    $row = '';
	    //Build a row for each departure in which the max passengers 
	    //value has been enlarged. 
	    $row .= $travelId.','.$dptStepId.','.$dptId.','.$psgrNb.',0.00';
	    //Insert a new row in the "values" clause.
	    $values[] = $row;
	  }
	}
      }

      $query->clear();
      //Add the travel price rows corresponding to the added passenger(s).
      $query->insert('#__odyssey_travel_price')
	    ->columns($columns)
	    ->values($values);
      $db->setQuery($query);
      $db->execute();

      //Note: We don't add any price rows for addon or transit city items. 
    }

    //Dynamical items can be linked individualy to the travel departures thanks to
    //checkboxes. 
    UpdatePriceRowsHelper::updateAddonPriceRows($dptStepId, $addonDptStates);
    UpdatePriceRowsHelper::updateTransitCityPriceRows($dptStepId, $transCityStates);

    return;
  }


  public static function updateAddonPriceRows($dptStepId, $addonDptStates, $stepId = 0)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Note: This function is also called from the content plugin to update  
    //      link type steps. A step id is given as third argument.

    //If called from the updateTravelPriceRows function, we only deal with the departure step id.
    if(!$stepId) {
      $stepId = $dptStepId;
    }

    //Get the ids of the addons dynamicaly removed then delete the corresponding price rows.
    $removedItemIds = UpdatePriceRowsHelper::getRemovedItemIds($stepId, $addonDptStates, 'addon');
    if(!empty($removedItemIds)) {
      $query->delete('#__odyssey_addon_price')
	    ->where('step_id='.(int)$stepId.' AND addon_id IN('.implode(',', $removedItemIds).')');
      $db->setQuery($query);
      $db->execute();

      $query->clear();
      $query->delete('#__odyssey_addon_option_price')
	    ->where('step_id='.(int)$stepId.' AND addon_id IN('.implode(',', $removedItemIds).')');
      $db->setQuery($query);
      $db->execute();
    }

    //Get the departure ids set in the departure step.
    $query->clear();
    $query->select('dpt_id')
	  ->from('#__odyssey_departure_step_map')
	  ->where('step_id='.(int)$dptStepId);
    $db->setQuery($query);
    $travelDptIds = $db->loadColumn();

    //Check for the addon price rows to remove.
    $where = '';
    foreach($addonDptStates as $addonId => $dptIds) {
      //Get the missing departure ids (ie: the unchecked departure checkboxes).
      $missingDptIds = array_diff($travelDptIds, $dptIds);
      //Build the MySQL query to delete the possible addon price rows linked to those
      //departure ids.
      if(!empty($missingDptIds)) {
	$where .= '(step_id='.$stepId.' AND addon_id='.$addonId.' AND dpt_id IN('.implode(',', $missingDptIds).')) OR ';
      }
    }

    if(!empty($where)) {
      //Remove OR condition (and spaces) from the end of the string.
      $where = substr($where, 0, -4);

      $query->clear();
      $query->delete('#__odyssey_addon_price')
	    ->where($where);
      $db->setQuery($query);
      $db->execute();
      //Delete possible addon option price rows as well.
      $query->clear();
      $query->delete('#__odyssey_addon_option_price')
	    ->where($where);
      $db->setQuery($query);
      $db->execute();
    }

    return;
  }


  public static function updateTransitCityPriceRows($dptStepId, $transCityStates)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Get the removed item ids then delete the corresponding price rows.
    $removedItemIds = UpdatePriceRowsHelper::getRemovedItemIds($stepId, $transCityStates, 'transit_city');
    if(!empty($removedItemIds)) {
      $query->delete('#__odyssey_transit_city_price')
	    ->where('dpt_step_id='.(int)$dptStepId.' AND city_id IN('.implode(',', $removedItemIds).')');
      $db->setQuery($query);
      $db->execute();
    }

    //Get the departure ids set in the departure step.
    $query->clear();
    $query->select('dpt_id')
	  ->from('#__odyssey_departure_step_map')
	  ->where('step_id='.(int)$dptStepId);
    $db->setQuery($query);
    $travelDptIds = $db->loadColumn();

    //Check for the transit city price rows to remove.
    $where = '';
    foreach($transCityStates as $cityId => $dptIds) {
      //Get the missing departure ids (ie: the unchecked departure checkboxes).
      $missingDptIds = array_diff($travelDptIds, $dptIds);
      //Build the MySQL query to delete the possible transit city price rows linked to those
      //departure ids.
      if(!empty($missingDptIds)) {
	$where .= '(dpt_step_id='.$dptStepId.' AND city_id='.$cityId.' AND dpt_id IN('.implode(',', $missingDptIds).')) OR ';
      }
    }

    if(!empty($where)) {
      //Remove OR condition (and spaces) from the end of the string.
      $where = substr($where, 0, -4);
      $query->clear();
      $query->delete('#__odyssey_transit_city_price')
	    ->where($where);
      $db->setQuery($query);
      $db->execute();
    }

    return;
  }


  public static function getRemovedItemIds($stepId, $itemStates, $itemType)
  {
    $currentItemIds = $removedItemIds = array();
    //Get the current item ids from the given mapping array.
    foreach($itemStates as $itemId => $value) {
      $currentItemIds[] = $itemId;
    }

    //Set column names according to the item type.
    $colName1 = 'addon_id';
    $colName2 = 'step_id';
    if($itemType == 'transit_city') {
      $colName1 = 'city_id';
      $colName2 = 'dpt_step_id';
    }

    //Get the item ids previously set.
    //Note: This function is called before the update of the item tables, so we still have
    //      access to old item setting. 
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select($colName1)
	  ->from('#__odyssey_step_'.$itemType.'_map')
	  ->where($colName2.'='.(int)$stepId)
	  ->group($colName1);
    $db->setQuery($query);
    $oldItemIds = $db->loadColumn();

    if(empty($oldItemIds)) {
      return $removedItemIds;
    }

    //Get the item ids which have been removed.
    $removedItemIds = array_diff($oldItemIds, $currentItemIds);

    return $removedItemIds;
  }
}

