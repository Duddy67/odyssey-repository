<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
require_once JPATH_COMPONENT.'/helpers/travel.php';


/**
 * @package     Joomla.Site
 * @subpackage  com_odyssey
 */
class OdysseyControllerAddons extends JControllerForm
{
  public function setAddons()
  {
    TravelHelper::checkBookingProcess();

    //Get the addon form.
    $post = JFactory::getApplication()->input->post->getArray();
    echo '<pre>';
    var_dump($post);
    echo '</pre>';

    //Build id path arrays from which all the valid item ids can be retrieved.
    //
    //Addon id path:
    // array[step_id] -> array[addon_id, ...]
    //                   array[addon_id, ...]
    //      [step_id] -> array[addon_id, ...]
    //      ...
    //
    //Addon option id path:
    // array[step_id] -> array[addon_id] -> array[addon_option_id, ...]
    //                   array[addon_id] -> array[addon_option_id, ...]
    //      [step_id] -> array[addon_id] -> array[addon_option_id, ...]
    //      ...

    $selAddonIds = $selAddonOptionIds = array();
    //Get addon ids.
    foreach($post as $key => $value) {
      //Get addons which have a group and are possibly selectable. 
      if(preg_match('#^(multi|single|no)_([0-9]+)_([0-9]+)$#', $key, $matches)) {
	$selType = $matches[1];
	$grpNb = $matches[2];
	$stepId = $matches[3];

	//Note: Checkbox groups is return by the POST variable as arrays of checkbox values (ie: addon ids).
	if($selType == 'multi') {
	  $addonIds = $value;
	}
	else {
	  $addonId = $value;
	}

	//Store the selected addon id into the id path array.
	if(array_key_exists($stepId, $selAddonIds)) {
	  if($selType == 'multi') {
	    //Use a loop as we deal with an array.
	    foreach($addonIds as $addonId) {
	      $selAddonIds[$stepId][] = $addonId;
	    }
	  }
	  else {
	    $selAddonIds[$stepId][] = $addonId;
	  }
	}
	else { //Create path to addon id.
	  if($selType == 'multi') {
	    //Assign directly the array of addon ids.
	    $selAddonIds[$stepId] = $addonIds;
	  }
	  else {
	    $selAddonIds[$stepId] = array($addonId);
	  }
	}
      }

      //Get addons which have no group and are not selectable. 
      if(preg_match('#^none_([0-9]+)_([0-9]+)$#', $key, $matches)) {
	$stepId = $matches[1];
	$addonId = $matches[2];

	//Store the selected addon id into the id path array.
	if(array_key_exists($stepId, $selAddonIds)) {
	  $selAddonIds[$stepId][] = $addonId;
	}
	else {
	  $selAddonIds[$stepId] = array($addonId);
	}
      }
    }

    //Get addon option ids.
    foreach($post as $key => $value) {
      if(preg_match('#^option_(multi|single)_([0-9]+)_([0-9]+)$#', $key, $matches)) {
	$selType = $matches[1];
	$stepId = $matches[2];
	$addonId = $matches[3];

	//Note: Checkbox groups is return by the POST variable as arrays of checkbox
	//values (ie: addon option ids).
	if($selType == 'multi') {
	  $addonOptionIds = $value;
	}
	else {
	  $addonOptionId = $value;
	}

	//Check that the parent addon has been selected (in case of a group of multi or
	//single select).
	if(isset($selAddonIds[$stepId]) && in_array($addonId, $selAddonIds[$stepId])) {
	  //Store the selected addon option id into the id path array.
	  if(isset($selAddonOptionIds[$stepId][$addonId])) {
	    if($selType == 'multi') {
	      //Use a loop as we deal with an array.
	      foreach($addonOptionIds as $addonOptionId) {
		$selAddonOptionIds[$stepId][$addonId][] = $addonOptionId;
	      }
	    }
	    else {
	      $selAddonOptionIds[$stepId][$addonId][] = $addonOptionId;
	    }
	  }
	  else {
	    if($selType == 'multi') {
	      //Assign directly the array of addon ids.
	      $selAddonOptionIds[$stepId] = array($addonId => $addonOptionIds);
	    }
	    else {
	      $selAddonOptionIds[$stepId] = array($addonId => array($addonOptionId));
	    }
	  }
	}
      }
    }

    echo '<pre>';
    var_dump($selAddonIds);
    var_dump($selAddonOptionIds);
    echo '</pre>';
//return;

    //
    $model = $this->getModel('Travel');
    $addons = $model->getSelectedAddons($selAddonIds, $selAddonOptionIds);

    //echo '<pre>';
    //var_dump($addons);
    //echo '</pre>';
    $session = JFactory::getSession();
    $session->set('addons', $addons, 'odyssey'); 

    $this->setRedirect('index.php?option='.$this->option.'&task=passengers.checkUser');

    return true;
  }
}


