<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
require_once JPATH_ROOT.'/administrator/components/com_odyssey/helpers/utility.php';


class OverlappingHelper
{
  public static function checkTravelOverlapping($travel)
  {
    //The departure day is included into the travel duration.
    $nbDays = $travel['nb_days'] - 1;
    //Get the end date of the travel.
    $endDate = UtilityHelper::getLimitDate($nbDays, $travel['date_picker']);

    //No overlapping.
    if($endDate <= $travel['date_time_2']) {
      return $travel;
    }

    //Compute the number of days for each period.
    $travel['nb_days_period_1'] = UtilityHelper::getRemainingDays($travel['date_time_2'], $travel['date_picker']);
    //The departure day is included into the travel duration.
    $travel['nb_days_period_1'] = $travel['nb_days_period_1'] + 1;
    $travel['nb_days_period_2'] = $travel['nb_days'] - $travel['nb_days_period_1'];

    //Get the second overlapping period.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('step_id, dpt_id, date_time, date_time_2')
          ->from('#__odyssey_departure_step_map')
	  ->where('date_time > '.$db->quote($travel['date_time_2']))
	  ->where('step_id='.(int)$travel['dpt_step_id'])
	  ->order('date_time ASC')
	  ->setLimit('1');
    $db->setQuery($query);
    $period2 = $db->loadAssoc();

    //There is no second overlapping period or this period starts after the end of the travel.
    //Note: Add the seconds parameter to endDate or the comparison won't work properly.
    if($period2 === null || $period2['date_time'] > $endDate.':00') {
      return $travel;
    }

    //In case there is a gap between the end of the period 1 and the start of the period 2.
    for($i = 1; $i < $travel['nb_days']; $i++) {
      if($period2['date_time'] > UtilityHelper::getLimitDate($i, $travel['date_time_2'], true, 'Y-m-d H:i:s')) {
	//Readjust the days for each period.
	$travel['nb_days_period_1']++;
	$travel['nb_days_period_2']--;
      }
      else {
	break;
      }
    }

    $travel['period_2_dpt_id'] = $period2['dpt_id'];
    $travel['overlapping'] = 1;

    return $travel;
  }


  public static function updateTravelPrice($travel)
  {
    //Ensure travel is overlapping.
    if(!$travel['overlapping']) {
      return $travel;
    }

    //Compute travel and transit city prices for period 1.
    $transitPricePeriod1 = $transitPricePeriod2 = 0;

    $travelPricePeriod1 = $travel['nb_days_period_1'] * ($travel['travel_price'] / $travel['nb_days']);

    if($travel['transit_price']) {
      $transitPricePeriod1 = $travel['nb_days_period_1'] * ($travel['transit_price'] / $travel['nb_days']);
    }

    //Get both travel and transit city prices during the period 2.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('t.price AS travel_price, IFNULL(tc.price, 0) AS transit_price')
          ->from('#__odyssey_travel_price AS t')
	  ->join('LEFT', '#__odyssey_transit_city_price AS tc ON  tc.travel_id=t.travel_id AND tc.dpt_step_id=t.dpt_step_id'.
	                 ' AND tc.dpt_id=t.dpt_id AND tc.psgr_nb=t.psgr_nb AND tc.city_id='.(int)$travel['city_id'])
	  ->where('t.travel_id='.(int)$travel['travel_id'].' AND t.dpt_step_id='.(int)$travel['dpt_step_id']) 
	  ->where('t.dpt_id='.(int)$travel['period_2_dpt_id'].' AND t.psgr_nb='.(int)$travel['nb_psgr']);
    $db->setQuery($query);
    $prices = $db->loadAssoc();

    //Compute travel and transit city prices for period 2.
    $travelPricePeriod2 = $travel['nb_days_period_2'] * ($prices['travel_price'] / $travel['nb_days']);

    if($prices['transit_price']) {
      $transitPricePeriod2 = $travel['nb_days_period_2'] * ($prices['transit_price'] / $travel['nb_days']);
    }

    //Adds up prices from the 2 periods to get final prices.
    $travel['travel_price'] = $travelPricePeriod1 + $travelPricePeriod2;
    $travel['transit_price'] = $transitPricePeriod1 + $transitPricePeriod2;

    return $travel;
  }


  public static function updateAddonPrices($travel, $addons, $stepIds)
  {
    //Ensure travel is overlapping.
    if(!$travel['overlapping'] || empty($addons)) {
      return $addons;
    }

    //Get the addons from the departure of the period 2.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('sa.step_id, sa.addon_id, IFNULL(ap.price, 0) AS price')
	  ->from('#__odyssey_step_addon_map AS sa')
	  ->join('INNER', '#__odyssey_addon AS a ON a.id=sa.addon_id')
	  ->join('LEFT', '#__odyssey_addon_hosting AS h ON h.addon_id=a.id')
	  ->join('LEFT', '#__odyssey_addon_price AS ap ON ap.travel_id='.(int)$travel['travel_id'].' AND ap.step_id=sa.step_id'.
	                 ' AND ap.addon_id=sa.addon_id AND ap.dpt_id=sa.dpt_id AND ap.psgr_nb='.(int)$travel['nb_psgr'])
	  ->where('sa.step_id IN('.implode(',', $stepIds).') AND sa.dpt_id='.(int)$travel['period_2_dpt_id'].' AND a.published=1')
	  //For hosting type addons we check that the number of passengers/persons is matching.
	  //Zero means no limit and IS NULL is to get the other addon types. 
	  ->where('(h.nb_persons='.(int)$travel['nb_psgr'].' OR h.nb_persons=0 OR h.nb_persons IS NULL)')
	  ->group('sa.step_id, sa.addon_id');
    $db->setQuery($query);
    $period2Addons = $db->loadAssocList();

    foreach($addons as $key => $addon) {
      //Search for the corresponding addon in the departure set in period 2.
      foreach($period2Addons as $period2Addon) {
	//The addon matches and at least one of the addon has a price greater than zero. 
	if($period2Addon['step_id'] == $addon['step_id'] && $period2Addon['addon_id'] == $addon['addon_id'] 
	    && ($period2Addon['price'] > 0 || $addon['price'] > 0)) {
	  $addonPricePeriod1 = $addonPricePeriod2 = 0;
	  //Compute the addon price for both period 1 and 2.
	  if($addon['price']) {
	    $addonPricePeriod1 = $travel['nb_days_period_1'] * ($addon['price'] / $travel['nb_days']);
	  }

	  if($period2Addon['price']) {
	    $addonPricePeriod2 = $travel['nb_days_period_2'] * ($period2Addon['price'] / $travel['nb_days']);
	  }

	  //Adds up prices from the 2 periods to get final prices.
	  $addons[$key]['price'] = $addonPricePeriod1 + $addonPricePeriod2;

	  break;
	}
      }
    }

    return $addons;
  }


  public static function updateAddonOptionPrices($travel, $addonOptions, $stepIds, $addonIds)
  {
    //Ensure travel is overlapping.
    if(!$travel['overlapping'] || empty($addonOptions)) {
      return $addonOptions;
    }

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('sa.step_id, ao.addon_id, ao.id AS addon_option_id, IFNULL(ap.price, 0) AS price')
	  ->from('#__odyssey_addon_option AS ao')
	  ->join('INNER', '#__odyssey_step_addon_map AS sa ON sa.addon_id=ao.addon_id')
	  ->join('LEFT', '#__odyssey_addon_option_price AS ap ON ap.travel_id='.(int)$travel['travel_id'].' AND ap.step_id=sa.step_id'.
			 ' AND ap.addon_id=ao.addon_id AND ap.addon_option_id=ao.id AND ap.dpt_id=sa.dpt_id'.
			 ' AND ap.psgr_nb='.(int)$travel['nb_psgr'])
	  ->where('ao.addon_id IN('.implode(',', $addonIds).') AND sa.step_id IN('.implode(',', $stepIds).')')
	  ->where('sa.dpt_id='.(int)$travel['period_2_dpt_id'].' AND ao.published=1')
	  ->group('sa.step_id, ao.addon_id, ao.id');
//file_put_contents('debog_options.txt', print_r($query->__toString(), true));
    $db->setQuery($query);
    $period2AddonOptions = $db->loadAssocList();

    foreach($addonOptions as $key => $addonOption) {
      //Search for the corresponding addon option in the departure set in period 2.
      foreach($period2AddonOptions as $period2AddonOption) {
	//The addon option matches and at least one of the addon has a price greater than zero. 
	if($period2AddonOption['step_id'] == $addonOption['step_id'] && $period2AddonOption['addon_id'] == $addonOption['addon_id'] 
	  && $period2AddonOption['addon_option_id'] == $addonOption['addon_option_id'] 
	  && ($period2AddonOption['price'] > 0 || $addonOption['price'] > 0)) {
	  $addonOptionPricePeriod1 = $addonOptionPricePeriod2 = 0;
	  //Compute the addon option price for both period 1 and 2.
	  if($addonOption['price']) {
	    $addonOptionPricePeriod1 = $travel['nb_days_period_1'] * ($addonOption['price'] / $travel['nb_days']);
	  }

	  if($period2AddonOption['price']) {
	    $addonOptionPricePeriod2 = $travel['nb_days_period_2'] * ($period2AddonOption['price'] / $travel['nb_days']);
	  }

	  //Adds up prices from the 2 periods to get final prices.
	  $addonOptions[$key]['price'] = $addonOptionPricePeriod1 + $addonOptionPricePeriod2;

	  break;
	}
      }
    }

    return $addonOptions;
  }


  public static function updateSelectedAddonPrices($travel, $addons, $selAddonIds)
  {
    //Ensure travel is overlapping.
    if(!$travel['overlapping'] || empty($addons)) {
      return $addons;
    }

    //Get the addons from the departure of the period 2.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Get the selected addons (or addons by default).
    $query->select('sa.step_id, a.id AS addon_id, IFNULL(ap.price, 0) AS price')
	  ->from('#__odyssey_step_addon_map AS sa')
	  ->join('INNER', '#__odyssey_addon AS a ON a.id=sa.addon_id')
	  ->join('LEFT', '#__odyssey_addon_price AS ap ON ap.travel_id='.(int)$travel['travel_id'].' AND ap.step_id=sa.step_id'.
		 ' AND ap.addon_id=sa.addon_id AND ap.dpt_id='.(int)$travel['period_2_dpt_id'].' AND ap.psgr_nb='.(int)$travel['nb_psgr']);

    //Get the data of the addons selected in each step.
    $where = '';
    foreach($selAddonIds as $stepId => $addonIds) {
      $where .= '(sa.step_id='.(int)$stepId.' AND sa.addon_id IN('.implode(',', $addonIds).')) OR ';
    }
    //Remove OR plus spaces from the end of the string.
    $where = substr($where, 0, -4);

    $query->where($where)
	  ->group('sa.step_id, sa.addon_id');
    $db->setQuery($query);
    $period2Addons = $db->loadAssocList();

    foreach($addons as $key => $addon) {
      //Search for the corresponding addon in the departure set in period 2.
      foreach($period2Addons as $period2Addon) {
	//The addon matches and at least one of the addon has a price greater than zero. 
	if($period2Addon['step_id'] == $addon['step_id'] && $period2Addon['addon_id'] == $addon['addon_id'] 
	    && ($period2Addon['price'] > 0 || $addon['price'] > 0)) {
	  $addonPricePeriod1 = $addonPricePeriod2 = 0;
	  //Compute the addon price for both period 1 and 2.
	  if($addon['price']) {
	    $addonPricePeriod1 = $travel['nb_days_period_1'] * ($addon['price'] / $travel['nb_days']);
	  }

	  if($period2Addon['price']) {
	    $addonPricePeriod2 = $travel['nb_days_period_2'] * ($period2Addon['price'] / $travel['nb_days']);
	  }

	  //Adds up prices from the 2 periods to get final prices.
	  $addons[$key]['price'] = $addonPricePeriod1 + $addonPricePeriod2;

	  break;
	}
      }
    }

    return $addons;
  }


  public static function updateSelectedAddonOptionPrices($travel, $addonOptions, $selAddonOptionIds)
  {
    //Ensure travel is overlapping.
    if(!$travel['overlapping'] || empty($addonOptions)) {
      return $addonOptions;
    }

    //Get the addon options from the departure of the period 2.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('sa.step_id, sa.addon_id, ao.id AS addon_option_id, IFNULL(ap.price, 0) AS price')
	  ->from('#__odyssey_addon_option AS ao')
	  ->join('INNER', '#__odyssey_step_addon_map AS sa ON sa.addon_id=ao.addon_id')
	  ->join('LEFT', '#__odyssey_addon_option_price AS ap ON ap.travel_id='.(int)$travel['travel_id'].' AND ap.step_id=sa.step_id'.
			 ' AND ap.addon_id=sa.addon_id AND ap.dpt_id='.(int)$travel['period_2_dpt_id'].
			 ' AND ap.addon_option_id=ao.id AND ap.psgr_nb='.(int)$travel['nb_psgr']);

    //Get the data of the addon options selected in each addon (selected in each step).
    $where = '';
    foreach($selAddonOptionIds as $stepId => $addonIds) {
      foreach($addonIds as $addonId => $addonOptionIds) {
	if(!empty($addonOptionIds)) {
	  $where .= '(sa.step_id='.(int)$stepId.' AND ao.addon_id='.(int)$addonId.
		    ' AND ao.id IN('.implode(',', $addonOptionIds).')) OR ';
	}
      }
    }
    //Remove OR plus spaces from the end of the string.
    $where = substr($where, 0, -4);

    $query->where($where)
	  ->group('sa.step_id, sa.addon_id, ao.id');
    $db->setQuery($query);
    $period2AddonOptions = $db->loadAssocList();

    foreach($addonOptions as $key => $addonOption) {
      //Search for the corresponding addon option in the departure set in period 2.
      foreach($period2AddonOptions as $period2AddonOption) {
	//The addon option matches and at least one of the addon has a price greater than zero. 
	if($period2AddonOption['step_id'] == $addonOption['step_id'] && $period2AddonOption['addon_id'] == $addonOption['addon_id'] 
	  && $period2AddonOption['addon_option_id'] == $addonOption['addon_option_id'] 
	  && ($period2AddonOption['price'] > 0 || $addonOption['price'] > 0)) {
	  $addonOptionPricePeriod1 = $addonOptionPricePeriod2 = 0;
	  //Compute the addon option price for both period 1 and 2.
	  if($addonOption['price']) {
	    $addonOptionPricePeriod1 = $travel['nb_days_period_1'] * ($addonOption['price'] / $travel['nb_days']);
	  }

	  if($period2AddonOption['price']) {
	    $addonOptionPricePeriod2 = $travel['nb_days_period_2'] * ($period2AddonOption['price'] / $travel['nb_days']);
	  }

	  //Adds up prices from the 2 periods to get final prices.
	  $addonOptions[$key]['price'] = $addonOptionPricePeriod1 + $addonOptionPricePeriod2;

	  break;
	}
      }
    }

    return $addonOptions;
  }
}

