<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

require_once JPATH_ROOT.'/administrator/components/com_odyssey/helpers/utility.php';
require_once JPATH_ROOT.'/administrator/components/com_odyssey/helpers/odyssey.php';


class OrderHelper
{
  public static function storeOrder($travel, $addons, $settings)
  {
    $user = JFactory::getUser();
    $orderDetails = '<table class="order-details table" style="width:100%;"><tr><th>'.JText::_('COM_ODYSSEY_HEADING_NAME').'</th><th>'.
                     JText::_('COM_ODYSSEY_HEADING_TYPE').'</th><th>'.JText::_('COM_ODYSSEY_HEADING_PRICE').'</th></tr>';
    $currency = $settings['currency'];

    //Build the order details html table.
    foreach($addons as $addon) {
      //Check for price rules and display names.
      if(isset($addon['pricerules'])) {
	foreach($addon['pricerules'] as $priceRule) {
	  $orderDetails .= '<tr class="addon-pricerules"><td>'.$priceRule['name'].'</td><td>'.
	                    JText::_('COM_ODYSSEY_HEADING_ADDON_PRICERULE').'</td>';
	  $orderDetails .= '<td>'.UtilityHelper::formatPriceRule($priceRule['operation'], $priceRule['value']).'</td></tr>';
	}
      }

      $orderDetails .= '<tr><td>'.$addon['name'].'</td><td>'.JText::_('COM_ODYSSEY_HEADING_ADDON').'</td>';

      if($addon['price'] > 0) {
	$orderDetails .= '<td>';

	//Check for price rules.
	if(isset($addon['pricerules'])) {
	  $orderDetails .= '<span style="text-decoration: line-through;">'.
			     UtilityHelper::formatNumber($addon['normal_price'], $settings['digits_precision']).' '.$currency.'</span><br />';
	}

	$orderDetails .= UtilityHelper::formatNumber($addon['price'], $settings['digits_precision']).' '.$currency.'</td></tr>';
      }
      else {
	$orderDetails .= '<td>'.JText::_('COM_ODYSSEY_HEADING_INCLUDED').'</td></tr>';
      }

      foreach($addon['options'] as $option) {
	//Check for price rules and display names.
	if(isset($option['pricerules'])) {
	  foreach($option['pricerules'] as $priceRule) {
	    $orderDetails .= '<tr class="addon-option-pricerules"><td>'.$priceRule['name'].'</td><td>'.
	                      JText::_('COM_ODYSSEY_HEADING_ADDON_OPTION_PRICERULE').'</td>';
	    $orderDetails .= '<td>'.UtilityHelper::formatPriceRule($priceRule['operation'], $priceRule['value']).'</td></tr>';
	  }
	}

	$orderDetails .= '<tr><td>'.$option['name'].'</td><td>'.JText::_('COM_ODYSSEY_HEADING_ADDON_OPTION').'</td>';

	if($option['price'] > 0) {
	  $orderDetails .= '<td>';

	  //Check for price rules.
	  if(isset($option['pricerules'])) {
	    $orderDetails .= '<span style="text-decoration: line-through;">'.
			      UtilityHelper::formatNumber($option['normal_price'], $settings['digits_precision']).' '.$currency.'</span><br />';
	  }

	  $orderDetails .= UtilityHelper::formatNumber($option['price'], $settings['digits_precision']).' '.$currency.'</td></tr>';
	}
	else {
	  $orderDetails .= '<td>'.JText::_('COM_ODYSSEY_HEADING_INCLUDED').'</td></tr>';
	}
      }
    }

    //Check for travel price rules.
    if(isset($travel['pricerules'])) {
      foreach($travel['pricerules'] as $priceRule) {
	//Set the proper text according to the price rule behavior.
	$jtext = JText::_('COM_ODYSSEY_HEADING_TRAVEL_PRICERULE');
	if($priceRule['behavior'] == 'CPN_XOR' || $priceRule['behavior'] == 'CPN_AND') {
	  $jtext = JText::_('COM_ODYSSEY_HEADING_TRAVEL_COUPON');
	}

	$orderDetails .= '<tr class="travel-pricerules"><td>'.$priceRule['name'].'</td><td>'.$jtext.'</td>';
	$orderDetails .= '<td>'.UtilityHelper::formatPriceRule($priceRule['operation'], $priceRule['value']).'</td></tr>';
      }
    }

    $orderDetails .= '<tr><td>'.$travel['name'].'</td><td>'.JText::_('COM_ODYSSEY_HEADING_TRAVEL').'</td><td>';

    if(isset($travel['pricerules'])) {
      $orderDetails .= '<span style="text-decoration: line-through;">'.
	               UtilityHelper::formatNumber($travel['normal_price'], $settings['digits_precision']).' '.$currency.'</span><br />';
    }

    $orderDetails .= UtilityHelper::formatNumber($travel['travel_price'], $settings['digits_precision']).' '.$currency.'</td></tr>';

    if($travel['transit_price'] > 0) {
      $orderDetails .= '<tr><td>'.$travel['dpt_city_name'].'</td><td>'.JText::_('COM_ODYSSEY_HEADING_TRANSIT_CITY').'</td><td>'.
			UtilityHelper::formatNumber($travel['transit_price'], $settings['digits_precision']).' '.$currency.'</td></tr>';
    }

    $orderDetails .= '</table>';

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $nowDate = $db->quote(JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true));

    //Set the proper departure date according to the date type.
    $dptDate = $travel['date_time'];
    if($travel['date_type'] == 'period') {
      $dptDate = $travel['date_picker'];
    }

    $columns = array('order_nb','customer_id','payment_status','order_status',
	             'outstanding_balance','deposit_rate','final_amount','departure_date','nb_psgr','order_details',
		     'currency_code','rounding_rule','digits_precision','created');

    $values = $db->quote('undefined').','.(int)$user->get('id').','.$db->quote('pending').','.$db->quote('pending').','.
	      (float)$travel['final_amount'].','.(int)$settings['deposit_rate'].','.(float)$travel['final_amount'].','.
	      $db->quote($dptDate).','.(int)$travel['nb_psgr'].','.$db->quote($orderDetails).','.$db->quote($settings['currency_code']).','.
	      $db->quote($settings['rounding_rule']).','.(int)$settings['digits_precision'].','.$nowDate;
    $query->insert('#__odyssey_order')
	  ->columns($columns)
	  ->values($values);
    try {
      $db->setQuery($query);
      $db->execute();
    }
    catch(RuntimeException $e) {
      JFactory::getApplication()->enqueueMessage(JText::_($e->getMessage()), 'error');
      return 0;
    }

    //Return the id of the order newly created.
    return (int)$db->insertid();
  }


  public static function storeTravel($travel, $orderId)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Set the proper departure date according to the date type.
    $dptDate = $travel['date_time'];
    if($travel['date_type'] == 'period') {
      $dptDate = $travel['date_picker'];
    }

    $columns = array('order_id','travel_id','dpt_step_id','dpt_id','name',
	             'departure_date','date_type','nb_psgr','travel_price',
		     'tax_rate','dpt_city_name','time_offset','transit_price');

    $values = (int)$orderId.','.(int)$travel['travel_id'].','.(int)$travel['dpt_step_id'].','.
              (int)$travel['dpt_id'].','.$db->quote($travel['name']).','.$db->quote($dptDate).','.
	      $db->quote($travel['date_type']).','.(int)$travel['nb_psgr'].','.(float)$travel['travel_price'].','.
	      (float)$travel['tax_rate'].','.$db->quote($travel['dpt_city_name']).','.
	      $db->quote($travel['time_offset']).','.(float)$travel['transit_price'];

    $query->insert('#__odyssey_order_travel')
	  ->columns($columns)
	  ->values($values);
    try {
      $db->setQuery($query);
      $db->execute();
    }
    catch(RuntimeException $e) {
      JFactory::getApplication()->enqueueMessage(JText::_($e->getMessage()), 'error');
      return false;
    }

    return true;
  }


  public static function storeAddons($addons, $orderId)
  {
    if(empty($addons)) {
      return true;
    }

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    $values1 = $values2 = array();
    foreach($addons as $addon) {
      $values1[] = (int)$orderId.','.(int)$addon['step_id'].','.(int)$addon['addon_id'].','.
		  $db->quote($addon['name']).','.(float)$addon['price'];

      foreach($addon['options'] as $addonOption) {
	$values2[] = (int)$orderId.','.(int)$addon['step_id'].','.(int)$addon['addon_id'].','.
		    (int)$addonOption['addon_option_id'].','.$db->quote($addonOption['name']).','.(float)$addonOption['price'];
      }
    }

    if(!empty($values1)) {
      $columns = array('order_id','step_id','addon_id','name','price');
      $query->insert('#__odyssey_order_addon')
	    ->columns($columns)
	    ->values($values1);
      try {
	$db->setQuery($query);
	$db->execute();
      }
      catch(RuntimeException $e) {
	JFactory::getApplication()->enqueueMessage(JText::_($e->getMessage()), 'error');
	return false;
      }
    }


    if(!empty($values2)) {
      $columns = array('order_id','step_id','addon_id','addon_option_id','name','price');
      $query->clear();
      $query->insert('#__odyssey_order_addon_option')
	    ->columns($columns)
	    ->values($values2);
      try {
	$db->setQuery($query);
	$db->execute();
      }
      catch(RuntimeException $e) {
	JFactory::getApplication()->enqueueMessage(JText::_($e->getMessage()), 'error');
	return false;
      }
    }

    return true;
  }


  public static function storePriceRules($item, $orderId, $itemType)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $values = array();

    //Check for travel price rules to store.
    if($itemType == 'travel' && isset($item['pricerules'])) {
      foreach($item['pricerules'] as $priceRule) {
	$values[] = $orderId.','.$priceRule['id'].','.$db->quote($priceRule['name']).','.$db->quote($priceRule['prule_type']).','.
	            $db->quote($priceRule['behavior']).','.$db->quote($priceRule['operation']).','.$db->quote($priceRule['target']).','.
		    $priceRule['show_rule'].','.$priceRule['value'].','.$priceRule['ordering'];
      }
    }

    if($itemType == 'addon') {
      $addonIds = $addonOptionIds = array();
      foreach($item as $addon) {
	//Check for price rule and possible duplicate price rule.
	if(isset($addon['pricerules']) && !in_array($addon['addon_id'], $addonIds)) {
	  //Note: The same price rule can be applied on multiple addons so there 
	  //is no need to insert the same price rule twice or more.
	  $addonIds[] = $addon['addon_id'];

	  foreach($addon['pricerules'] as $priceRule) {
	    $values[] = $orderId.','.$priceRule['prule_id'].','.$db->quote($priceRule['name']).','.$db->quote($priceRule['prule_type']).','.
			$db->quote($priceRule['behavior']).','.$db->quote($priceRule['operation']).','.$db->quote($priceRule['target']).','.
			$priceRule['show_rule'].','.$priceRule['value'].','.$priceRule['ordering'];
	  }
	}

	//Check for addon option price rules.
	if(!empty($addon['options'])) {
	  foreach($addon['options'] as $option) {
	    //Check for price rule and possible duplicate price rule.
	    if(isset($option['pricerules']) && !in_array($option['addon_option_id'], $addonOptionIds)) {
	      $addonOptionIds[] = $option['addon_option_id'];

	      foreach($option['pricerules'] as $priceRule) {
		$values[] = $orderId.','.$priceRule['prule_id'].','.$db->quote($priceRule['name']).','.$db->quote($priceRule['prule_type']).','.
			    $db->quote($priceRule['behavior']).','.$db->quote($priceRule['operation']).','.$db->quote($priceRule['target']).','.
			    $priceRule['show_rule'].','.$priceRule['value'].','.$priceRule['ordering'];
	      }
	    }
	  }
	}
      }
    }

    if(!empty($values)) {
      $columns = array('order_id', 'prule_id', 'name', 'prule_type', 'behavior', 'operation', 'target', 'show_rule', 'value', 'ordering');
      $query->insert('#__odyssey_order_pricerule')
	    ->columns($columns)
	    ->values($values);
      try {
	$db->setQuery($query);
	$db->execute();
      }
      catch(RuntimeException $e) {
	JFactory::getApplication()->enqueueMessage(JText::_($e->getMessage()), 'error');
	return false;
      }
    }

    return true;
  }


  public static function createTransaction($travel, $utility, $settings)
  {
    //Set the amount value which has been paid.
    $amount = $travel['final_amount'];
    if(isset($travel['deposit_amount'])) {
      $amount = $travel['deposit_amount'];
    }

    if($travel['booking_option'] == 'remaining') {
      $amount = $travel['outstanding_balance'];
    }

    //Set the result of the transaction.
    $result = 'success';
    $detail = $utility['payment_details'];
    if(!$utility['payment_result']) {
      $result = 'error';
    }

    //Create the transaction.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    $nowDate = $db->quote(JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true));
    $columns = array('order_id','payment_mode','amount_type','amount','result','detail','transaction_data','created');
    $values = (int)$travel['order_id'].','.$db->quote($utility['payment_mode']).','.$db->quote($travel['booking_option']).','.  
              (float)$amount.','.$db->quote($result).','.$db->quote($detail).','.$db->quote($utility['transaction_data']).','.$nowDate;

    $query->insert('#__odyssey_order_transaction')
	  ->columns($columns)
	  ->values($values);
    try {
      $db->setQuery($query);
      $db->execute();
    }
    catch(RuntimeException $e) {
      JFactory::getApplication()->enqueueMessage(JText::_($e->getMessage()), 'error');
      return false;
    }

    return true;
  }


  public static function setPassengers($passengers, $orderId)
  {
    //Get the passenger ini file in which some settings are defined.
    $psgrIni = parse_ini_file(OdysseyHelper::getOverridedFile(JPATH_ROOT.'/administrator/components/com_odyssey/models/forms/passenger.ini'));
    $attributes = $psgrIni['attributes'];
    $types = $psgrIni['types'];
    $address = $psgrIni['address'];

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    $values = $psgrIds = $newPsgrIds = $whens = $whensAddr = array();
    foreach($passengers as $passenger) {

      if(empty($passenger['data']['id'])) { //Insert data.
	$value = '';
	foreach($attributes as $attribute) {
	  if($types[$attribute] == 'string') {
	    $value .= $db->quote($passenger['data'][$attribute]).',';
	  }
	  else {
	    $value .= (int)$passenger['data'][$attribute].',';
	  }
	}

	//Remove comma from the end of the string.
	$value = substr($value, 0, -1);

	//There is no option here but to run the MySQL query through the foreach loop as
	//we need the last id value to map the passengers with the order and to possibly 
	//link the passenger's address to the order.

	$query->clear();
	$query->insert('#__odyssey_passenger')
	      ->columns($attributes)
	      ->values($value);
	      echo $query;
	$db->setQuery($query);
	$db->execute();

	//Get the id of the passenger newly created.
	$psgrId = $db->insertid();

	//Store the new passenger id.
	$newPsgrIds[] = $psgrId;

	//Passengers are asked to give an address.
	//Note: We don't store the customer address as it is already set during registration.
	if((int)$psgrIni['is_address'] && $passenger['data']['customer'] != 1) {
	  $values = '';
	  foreach($address as $attribute) {
	    if($types[$attribute] == 'string') {
	      $values .= $db->quote($passenger['address'][$attribute]).',';
	    }
	    else {
	      $values .= (int)$passenger['address'][$attribute].',';
	    }
	  }

	  //Add the item_id and item_type attributes.
	  $columns = $address;
	  $columns[] = 'item_id';
	  $columns[] = 'item_type';
	  //Add the item_id and item_type values.
	  $values .= (int)$psgrId.','.$db->quote('passenger');

	  //Insert the passenger's address.
	  $query->clear();
	  $query->insert('#__odyssey_address')
		->columns($columns)
		->values($values);
	  $db->setQuery($query);
	  $db->execute();
	}
      }
      else { //Update data.
	//Store passenger ids.
	$psgrIds[] = (int)$passenger['data']['id'];
	//
	foreach($passenger['data'] as $key => $value) {
	  if($types[$key] == 'string') {
	    $value = $db->quote($value);
	  }
	  else {
	    $value = (int)$value;
	  }

	  if($key != 'id') { //Don't update the id attribute.
	    if(isset($whens[$key])) {
	      $whens[$key] .= 'WHEN id = '.(int)$passenger['data']['id'].' THEN '.$value.' '; 
	    }
	    else {
	      $whens[$key] = 'WHEN id = '.(int)$passenger['data']['id'].' THEN '.$value.' '; 
	    }
	  }
	}

	if((int)$psgrIni['is_address'] && $passenger['data']['customer'] != 1) {
	  foreach($passenger['address'] as $key => $value) {
	    if($types[$key] == 'string') {
	      $value = $db->quote($value);
	    }
	    else {
	      $value = (int)$value;
	    }

	    if(isset($whensAddr[$key])) {
	      $whensAddr[$key] .= 'WHEN item_id = '.(int)$passenger['address']['item_id'].' AND item_type = "passenger" THEN '.$value.' '; 
	    }
	    else {
	      $whensAddr[$key] = 'WHEN item_id = '.(int)$passenger['address']['item_id'].' AND item_type = "passenger" THEN '.$value.' '; 
	    }
	  }
	}
      }
    }

    if(!empty($whens)) {
      $cases = '';
      foreach($whens as $key => $when) {
	$cases .= $key.' = CASE '.$when.' END,';
      }

      //Remove comma from the end of the string.
      $cases = substr($cases, 0, -1);

      $query->clear();
      $query->update('#__odyssey_passenger')
	    ->set($cases)
	    ->where('id IN('.implode(',', $psgrIds).')');
      $db->setQuery($query);
      $db->execute();

      //Update the passenger addresses as well.
      if((int)$psgrIni['is_address'] && !empty($whensAddr)) {
	$cases = '';
	foreach($whensAddr as $key => $when) {
	  $cases .= $key.' = CASE '.$when.' END,';
	}

	//Remove comma from the end of the string.
	$cases = substr($cases, 0, -1);

	$query->clear();
	$query->update('#__odyssey_address')
	      ->set($cases)
	      ->where('item_id IN('.implode(',', $psgrIds).') AND item_type="passenger"');
	$db->setQuery($query);
	$db->execute();
      }
    }

    //Remove the old mapping.
    $query->clear();
    $query->delete('#__odyssey_order_passenger')
	  ->where('order_id='.(int)$orderId);
    $db->setQuery($query);
    $db->execute();

    //Merge both new (inserted) and old (updated) passenger ids.
    $psgrIds = array_merge($newPsgrIds, $psgrIds);

    $columns = array('order_id', 'psgr_id');
    $values = array();
    foreach($psgrIds as $psgrId) {
      $values[] = (int)$orderId.','.(int)$psgrId;
    }

    //Update the mapping table.
    $query->clear();
    $query->insert('#__odyssey_order_passenger')
	  ->columns($columns)
	  ->values($values);
    $db->setQuery($query);
    $db->execute();

  }
}

