<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die;
require_once JPATH_ROOT.'/administrator/components/com_odyssey/helpers/utility.php';


class PriceruleHelper
{
  public static function getCatalogPriceRules($travel, $travelId, $catId)
  {
    $user = JFactory::getUser();
    //Get user group ids to which the user belongs to.
    $groups = JAccess::getGroupsByUser($user->get('id'));
    //Get current date and time (equal to NOW() in SQL).
    $now = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);
    $catalogPrules = array();

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Get the price rules linked to the travel.
    //Note: Get a travel price rule row for each departure of the step sequence multiplied with the number of passengers.
    $query->select('pr.name, pr.operation, pr.behavior, pr.show_rule, pr.recipient, pr.ordering,'.
	           'tpr.prule_id, tpr.dpt_id, tpr.psgr_nb, tpr.value')
	  ->from('#__odyssey_pricerule AS pr')
	  ->join('INNER', '#__odyssey_travel_pricerule AS tpr ON tpr.prule_id=pr.id')
	  ->join('INNER', '#__odyssey_prule_recipient AS prr ON (pr.recipient="customer" AND prr.item_id='.(int)$user->get('id').')'.
	                  ' OR (pr.recipient="customer_group" AND prr.item_id IN ('.implode(',', $groups).'))')
	  ->where('pr.prule_type="catalog" AND pr.target="travel" AND tpr.travel_id='.(int)$travelId)
	  ->where('prr.prule_id=pr.id AND pr.published=1')
	  //Check against publication dates (start and stop).
	  ->where('('.$db->quote($now).' < pr.publish_down OR pr.publish_down = "0000-00-00 00:00:00")')
	  ->where('('.$db->quote($now).' > pr.publish_up OR pr.publish_up = "0000-00-00 00:00:00")')
	  ->order('pr.ordering, tpr.prule_id, tpr.dpt_id, tpr.psgr_nb');
    $db->setQuery($query);
    $travelPrules = $db->loadAssocList();

    if(!empty($travelPrules)) {
      //Rearrange price rule data. 
      $catalogPrules = PriceruleHelper::setTravelPruleRows($travelPrules);
    }

    $query->clear();
    //Get the price rules linked to the travel category.
    $query->select('pr.name, pr.operation, pr.value, pr.behavior, pr.show_rule, pr.recipient, pr.ordering, prt.prule_id, prt.psgr_nbs')
	  ->from('#__odyssey_pricerule AS pr')
	  ->join('INNER', '#__odyssey_prule_target AS prt ON prt.prule_id=pr.id')
	  ->join('INNER', '#__odyssey_prule_recipient AS prr ON (pr.recipient="customer" AND prr.item_id='.(int)$user->get('id').')'.
	                  ' OR (pr.recipient="customer_group" AND prr.item_id IN ('.implode(',', $groups).'))')
	  ->where('pr.prule_type="catalog" AND pr.target="travel_cat" AND prt.item_id='.(int)$catId.' AND prr.prule_id=pr.id AND pr.published=1')
	  //Check against publication dates (start and stop).
	  ->where('('.$db->quote($now).' < pr.publish_down OR pr.publish_down = "0000-00-00 00:00:00")')
	  ->where('('.$db->quote($now).' > pr.publish_up OR pr.publish_up = "0000-00-00 00:00:00")')
	  ->order('pr.ordering');
    $db->setQuery($query);
    $travelCatPrules = $db->loadAssocList();

    if(!empty($travelCatPrules)) {
      //Some travel price rules have previously been found.
      if(!empty($catalogPrules)) {
	//Rearrange price rule data. 
	$travelCatPrules = PriceruleHelper::setTravelCatPruleRows($travelCatPrules, $travel);
	//Merge travel and travel category price rules together.
	$catalogPrules = array_merge($catalogPrules, $travelCatPrules);
      }
      else {
	//Rearrange price rule data. 
	$catalogPrules = PriceruleHelper::setTravelCatPruleRows($travelCatPrules, $travel);
      }
    }

    //No price rules have been found.
    if(empty($catalogPrules)) {
      return $catalogPrules;
    }

    $catalogPrules = PriceruleHelper::checkExclusivePriceRules($catalogPrules);

    return $catalogPrules;
  }


  //Convert all rows of the same price rule into a single row containing nested arrays for
  //departure ids and value per passengers.
  public static function setTravelPruleRows($pruleRows, $priceStartingAt = false)
  {
    $pruleIds = $prules = array();
    $currentDptId = 0;

    foreach($pruleRows as $key => $pruleRow) {
      //We're dealing with a new price rule.
      if(!in_array($pruleRow['prule_id'], $pruleIds)) {
	//Store the id of the new price rule.
	$pruleIds[] = $pruleRow['prule_id'];
	//Create a dpt_ids attribute in which we store an array of dpt_id containing the
	//value for each passenger in an array. 
	$pruleRow['dpt_ids'] = array($pruleRow['dpt_id'] => array($pruleRow['psgr_nb'] => UtilityHelper::formatNumber($pruleRow['value'])));
	//Set the current dpt_id.
	$currentDptId = $pruleRow['dpt_id'];
	//Remove unwanted variables.
	unset($pruleRow['dpt_id']);
	unset($pruleRow['psgr_nb']);
	unset($pruleRow['value']);
	//Add the new price rule.
	$prules[] = $pruleRow;
      }
      else { //We're dealing with an existing price rule.
	$currentId = count($prules) - 1;
	//It's the same dpt_id.
	if($pruleRow['dpt_id'] == $currentDptId) {
	  //Just add the passenger value in the dpt_id array.
	  $prules[$currentId]['dpt_ids'][$pruleRow['dpt_id']][$pruleRow['psgr_nb']] = UtilityHelper::formatNumber($pruleRow['value']); 
	}
	else { //We're dealing with a new dpt_id.
	  //Add a new dpt_id as well as an array containing the first passenger value.
	  $prules[$currentId]['dpt_ids'][$pruleRow['dpt_id']] = array($pruleRow['psgr_nb'] => UtilityHelper::formatNumber($pruleRow['value']));
	  //Set the current dpt_id.
	  $currentDptId = $pruleRow['dpt_id'];
	}
      }

      //Add the normal price attribute to the array.
      if($priceStartingAt) {
	$currentId = count($prules) - 1;
	$prules[$currentId]['dpt_ids'][$currentDptId][] = UtilityHelper::formatNumber($pruleRow['normal_price']);
      }
    }

    return $prules;
  }


  //Add departure ids and value per passengers as nested arrays into each row.
  public static function setTravelCatPruleRows($pruleRows, $travel)
  {
    foreach($pruleRows as $key => $pruleRow) {
      //Turn the string value into an array.
      $psgrNbs = explode(',', $pruleRow['psgr_nbs']);
      //Add a dpt_ids attribute.
      $pruleRows[$key]['dpt_ids'] = array();

      //Set departure ids and value per passengers according to the travel data.
      foreach($travel as $data) {
	//Add a new dpt_id.
	$pruleRows[$key]['dpt_ids'][$data['dpt_id']] = array();
	//Set a price rule value for each passenger.
	foreach($data['price_per_psgr'] as $psgrNb => $price) {
	  //Apply the price rule value if the passenger number matches.
	  //Note: Zero means all passenger numbers.
	  if($pruleRow['psgr_nbs'] == 0 || in_array($psgrNb, $psgrNbs)) {
	    $pruleRows[$key]['dpt_ids'][$data['dpt_id']][$psgrNb] = $pruleRow['value'];
	  }
	  else { //Passenger number doesn't match.
	    $pruleRows[$key]['dpt_ids'][$data['dpt_id']][$psgrNb] = '0.00';
	  }
	  //Remove unwanted variables.
	  unset($pruleRows[$key]['value']);
	  unset($pruleRows[$key]['psgr_nbs']);
	}
      }
    }

    return $pruleRows;
  }


  //Get the price rules matching the travel selected by the customer.
  //Note: Price rules (if any) are already known but they have to be computed once again
  //in order to be stored into the user's session.
  public static function getMatchingTravelPriceRules($pruleIds, $travel)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Get both travel and travel_cat target types at once.
    $query->select('pr.id, pr.name, pr.prule_type, pr.operation, pr.behavior, pr.target,'.
	           'pr.value, pr.show_rule, pr.ordering, tpr.value AS tpr_value')
	  ->from('#__odyssey_pricerule AS pr')
	  ->join('LEFT', '#__odyssey_prule_target AS t ON t.prule_id=pr.id AND t.item_id='.(int)$travel['catid'])
	  ->join('LEFT', '#__odyssey_travel_pricerule AS tpr ON tpr.prule_id=pr.id AND tpr.travel_id='.(int)$travel['travel_id'].
	                 ' AND tpr.dpt_step_id='.(int)$travel['dpt_step_id'].' AND tpr.dpt_id='.(int)$travel['dpt_id'].
			 ' AND tpr.psgr_nb='.(int)$travel['nb_psgr'])
	  ->where('pr.id IN('.implode(',', $pruleIds).')')
	  ->order('pr.ordering');
    $db->setQuery($query);
    $travelPrules = $db->loadAssocList();

    //Get the normal price.
    $price = $travel['travel_price'];
    $priceRules = array();
    foreach($travelPrules as $travelPrule) {
      //Set the proper price rule value according to the target type of the price rule.
      $pruleValue = $travelPrule['value'];
      if($travelPrule['target'] == 'travel') {
	$pruleValue = UtilityHelper::formatNumber($travelPrule['tpr_value']);
      }

      //Set the needed attributes then store the price rule.
      $priceRule = array('id' => $travelPrule['id'], 
	                 'name' => $travelPrule['name'],
	                 'prule_type' => $travelPrule['prule_type'],
	                 'behavior' => $travelPrule['behavior'],
	                 'operation' => $travelPrule['operation'],
	                 'target' => $travelPrule['target'],
	                 'show_rule' => $travelPrule['show_rule'],
	                 'value' => $pruleValue,
	                 'ordering' => $travelPrule['ordering']);
      $priceRules[] = $priceRule;

      //Apply the price rule value. 
      $price = PriceruleHelper::computePriceRule($travelPrule['operation'], $pruleValue, $price);
    }

    //Add the price rule data to the travel.
    $travel['pricerules'] = $priceRules;
    $travel['normal_price'] = $travel['travel_price'];
    $travel['travel_price'] = UtilityHelper::formatNumber($price, 5);

    return $travel;
  }


  public static function getAddonCatalogPriceRules($travel)
  {
    $user = JFactory::getUser();
    //Get user group ids to which the user belongs to.
    $groups = JAccess::getGroupsByUser($user->get('id'));
    //Get current date and time (equal to NOW() in SQL).
    $now = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);
    $travelId = $travel['travel_id'];
    $dptId = $travel['dpt_id'];

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Retrieve the departure ids of the travel in the chronological order.
    $query->select('dpt_id, date_time')
	  ->from('#__odyssey_departure_step_map')
	  ->where('step_id='.(int)$travel['dpt_step_id'])
	  ->order('date_time');
    $db->setQuery($query);
    $departures = $db->loadObjectList();

    //Get the departure chronological number corresponding to the chosen departure id. 
    $dptNb = 0;
    foreach($departures as $key => $departure) {
      if($departure->dpt_id == $dptId) {
	$dptNb = $key + 1;
	break;
      }
    }

    if(!$dptNb) {
      return array();
    }

    //Compute the SQL REGEXP for each passenger number included in the travel.
    //TODO: To be used for a more advanced feature.
    $regexp = '';
    for($i = 0; $i < $travel['nb_psgr']; $i++) {
      $psgrNb = $i + 1;
      $regexp .= '(^'.$psgrNb.'$)|(^'.$psgrNb.',)|(,'.$psgrNb.',)|(,'.$psgrNb.'$)|';
    }

    //Remove comma from the end of the string.
    $regexp = substr($regexp, 0, -1);

    //Get the price rules linked to the addons of the travel.
    $query->clear();
    $query->select('ap.step_id, ap.addon_id, pr.name, pr.behavior, pr.show_rule, pr.ordering, prt.prule_id,'.
		   'ap.dpt_id, pr.operation, pr.prule_type, pr.target, pr.value, ap.psgr_nb')
	  ->from('#__odyssey_pricerule AS pr')
	  ->join('INNER', '#__odyssey_prule_target AS prt ON prt.prule_id=pr.id')
	  //Get only price rules matching the number of passenger set by the user (TODO: to modified).
	  ->join('INNER', '#__odyssey_addon_price AS ap ON ap.travel_id='.(int)$travelId.
	                  ' AND ap.addon_id=prt.item_id AND ap.dpt_id='.(int)$dptId.' AND ap.psgr_nb='.$psgrNb.' AND ap.price > 0')
	  ->join('INNER', '#__odyssey_prule_recipient AS prr ON (pr.recipient="customer" AND prr.item_id='.(int)$user->get('id').')'.
	                  ' OR (pr.recipient="customer_group" AND prr.item_id IN ('.implode(',', $groups).'))')
	  ->where('pr.prule_type="catalog" AND pr.target="addon"')
	  //Don't collect price rules related to coupon.
	  ->where('(pr.behavior="XOR" OR pr.behavior= "AND")')
	  //Get only price rules set for the given travel.
	  ->where('(prt.travel_ids=0 OR prt.travel_ids REGEXP "(^'.(int)$travelId.'$)|(^'.(int)$travelId.',)|(,'.(int)$travelId.',)|(,'.(int)$travelId.'$)")')
	  //Get only price rules set for the given departure number.
	  ->where('(prt.dpt_nbs=0 OR prt.dpt_nbs REGEXP "(^'.(int)$dptNb.'$)|(^'.(int)$dptNb.',)|(,'.(int)$dptNb.',)|(,'.(int)$dptNb.'$)")')
	  //Get only price rules set for each passenger number included in the travel.
	  //TODO: To be used for a more advanced feature.
	  //->where('(prt.psgr_nbs=0 OR prt.psgr_nbs REGEXP "'.$regexp.'")')
	  ->where('prr.prule_id=pr.id AND pr.published=1')
	  //Check against publication dates (start and stop).
	  ->where('('.$db->quote($now).' < pr.publish_down OR pr.publish_down = "0000-00-00 00:00:00")')
	  ->where('('.$db->quote($now).' > pr.publish_up OR pr.publish_up = "0000-00-00 00:00:00")')
	  ->order('ap.step_id, ap.addon_id, prt.prule_id, pr.ordering, ap.dpt_id, ap.psgr_nb');
    $db->setQuery($query);
    $catalogPrules = $db->loadAssocList();

    //Get the price rules linked to the addon options linked to the addons of the travel.
    $query->clear();
    $query->select('aop.step_id, aop.addon_id, aop.addon_option_id, pr.name, pr.behavior, pr.show_rule, pr.ordering, prt.prule_id,'.
		   'aop.dpt_id, pr.operation, pr.prule_type, pr.target, pr.value, aop.psgr_nb')
	  ->from('#__odyssey_pricerule AS pr')
	  ->join('INNER', '#__odyssey_prule_target AS prt ON prt.prule_id=pr.id')
	  //
	  ->join('INNER', '#__odyssey_addon_option_price AS aop ON aop.travel_id='.(int)$travelId.
	                  ' AND aop.addon_option_id=prt.item_id AND aop.dpt_id='.(int)$dptId.' AND aop.psgr_nb='.$psgrNb.' AND aop.price > 0')
	  ->join('INNER', '#__odyssey_prule_recipient AS prr ON (pr.recipient="customer" AND prr.item_id='.(int)$user->get('id').')'.
	                  ' OR (pr.recipient="customer_group" AND prr.item_id IN ('.implode(',', $groups).'))')
	  ->where('pr.prule_type="catalog" AND pr.target="addon_option"')
	  //Don't collect price rules related to coupon.
	  ->where('(pr.behavior="XOR" OR pr.behavior= "AND")')
	  //Get only price rules set for the given travel.
	  ->where('(prt.travel_ids=0 OR prt.travel_ids REGEXP "(^'.(int)$travelId.'$)|(^'.(int)$travelId.',)|(,'.(int)$travelId.',)|(,'.(int)$travelId.'$)")')
	  //Get only price rules set for the given departure number.
	  ->where('(prt.dpt_nbs=0 OR prt.dpt_nbs REGEXP "(^'.(int)$dptNb.'$)|(^'.(int)$dptNb.',)|(,'.(int)$dptNb.',)|(,'.(int)$dptNb.'$)")')
	  //Get only price rules set for each passenger number included in the travel.
	  //Note: To be used for a more advanced feature.
	  //->where('(prt.psgr_nbs=0 OR prt.psgr_nbs REGEXP "'.$regexp.'")')
	  ->where('prr.prule_id=pr.id AND pr.published=1')
	  //Check against publication dates (start and stop).
	  ->where('('.$db->quote($now).' < pr.publish_down OR pr.publish_down = "0000-00-00 00:00:00")')
	  ->where('('.$db->quote($now).' > pr.publish_up OR pr.publish_up = "0000-00-00 00:00:00")')
	  ->order('aop.step_id, aop.addon_id, prt.prule_id, pr.ordering, aop.dpt_id, aop.psgr_nb');
    $db->setQuery($query);
    $results = $db->loadAssocList();

    //Merge the addon and addon option price rules.
    $catalogPrules = array_merge($catalogPrules, $results);
    //Merge also the travel price rules to exclude addon price rules in case of exclusive
    //travel price rules.
    $session = JFactory::getSession();
    $travel = $session->get('travel', array(), 'odyssey'); 
    if(isset($travel['pricerules'])) {
      $catalogPrules = array_merge($catalogPrules, $travel['pricerules']);
    }

    $catalogPrules = PriceruleHelper::checkExclusivePriceRules($catalogPrules);

    //Build id path arrays from which all price rules can be easily retrieved.
    $addonPrules = $addonOptionPrules = array();
    foreach($catalogPrules as $catalogPrule) {
      //Separate regular addons and addon options (ignore travel price rules).
      if(isset($catalogPrule['addon_option_id'])) { //We're dealing with an addon option.
	//Path pattern: array[step_id] -> array[addon_id] -> array[addon_option_id] -> array(pricerule data)
	if(!array_key_exists($catalogPrule['step_id'], $addonOptionPrules)) {
	  $addonOptionPrules[$catalogPrule['step_id']] = array($catalogPrule['addon_id'] => array($catalogPrule['addon_option_id'] => array($catalogPrule)));
	}
	else {
	  if(!array_key_exists($catalogPrule['addon_id'], $addonOptionPrules[$catalogPrule['step_id']])) {
	    $addonOptionPrules[$catalogPrule['step_id']][$catalogPrule['addon_id']] = array($catalogPrule['addon_option_id'] => array($catalogPrule));
	  }
	  elseif(!array_key_exists($catalogPrule['addon_option_id'], $addonOptionPrules[$catalogPrule['step_id']][$catalogPrule['addon_id']])) {
	    $addonOptionPrules[$catalogPrule['step_id']][$catalogPrule['addon_id']][$catalogPrule['addon_option_id']] = array($catalogPrule);
	  }
	  else {
	    $addonOptionPrules[$catalogPrule['step_id']][$catalogPrule['addon_id']][$catalogPrule['addon_option_id']][] = $catalogPrule;
	  }
	}
      }
      elseif(isset($catalogPrule['addon_id'])) { //We're dealing with a regular addon.
	//Path pattern:  array[step_id] -> array[addon_id] -> array(pricerule data)
	if(!array_key_exists($catalogPrule['step_id'], $addonPrules)) {
	  $addonPrules[$catalogPrule['step_id']] = array($catalogPrule['addon_id'] => array($catalogPrule));
	}
	else {
	  if(!array_key_exists($catalogPrule['addon_id'], $addonPrules[$catalogPrule['step_id']])) {
	    $addonPrules[$catalogPrule['step_id']][$catalogPrule['addon_id']] = array($catalogPrule);
	  }
	  else {
	    $addonPrules[$catalogPrule['step_id']][$catalogPrule['addon_id']][] = $catalogPrule;
	  }
	}
      }
    }

    $priceRules = array();
    $priceRules['addons'] = $addonPrules;
    $priceRules['addon_options'] = $addonOptionPrules;

    return $priceRules;
  }


  public static function getMatchingAddonPriceRules($addons, $travel)
  {
    //Get the addon price rules linked to the travel. 
    $addonCatPrules = PriceruleHelper::getAddonCatalogPriceRules($travel);
    $addonPrules = $addonCatPrules['addons'];
    $addonOptionPrules = $addonCatPrules['addon_options'];

    //Insert the price rules which match the selected addons.
    foreach($addons as $key => $addon) {
      $normalPrice = $price = $addon['price'];
      //Check price rules for this addon.
      $prules = array();
      if(isset($addonPrules[$addon['step_id']][$addon['addon_id']])) {
	foreach($addonPrules[$addon['step_id']][$addon['addon_id']] as $addonPrule) {
	  //Get the new price. 
	  $price = PriceruleHelper::computePriceRule($addonPrule['operation'], $addonPrule['value'], $price);
	  $prules[] = $addonPrule;
	}
      }

      //Insert price rule data and modify price accordingly.
      if(!empty($prules)) {
	$addons[$key]['price'] = $price;
	$addons[$key]['pricerules'] = $prules;
	$addons[$key]['normal_price'] = $normalPrice;
      }

      //Now move to the addon options if any.
      if(!empty($addon['options'])) {
	//Insert the price rules which match the selected addon options.
	foreach($addon['options'] as $key2 => $option) {
	  $normalPrice = $price = $option['price'];
	  //Check price rules for this addon option.
	  $prules = array();
	  if(isset($addonOptionPrules[$addon['step_id']][$addon['addon_id']][$option['addon_option_id']])) {
	    foreach($addonOptionPrules[$addon['step_id']][$addon['addon_id']][$option['addon_option_id']] as $addonOptionPrule) {
	      //Get the new price. 
	      $price = PriceruleHelper::computePriceRule($addonOptionPrule['operation'], $addonOptionPrule['value'], $price);
	      $prules[] = $addonOptionPrule;
	    }
	  }

	  //Insert price rule data and modify price accordingly.
	  if(!empty($prules)) {
	    if(!empty($prules)) {
	      $addons[$key]['options'][$key2]['price'] = $price;
	      $addons[$key]['options'][$key2]['pricerules'] = $prules;
	      $addons[$key]['options'][$key2]['normal_price'] = $normalPrice;
	    }
	  }
	}
      }
    }

    return $addons;
  }


  public static function computePriceRule($operation, $pruleValue, $price)
  {
    $operation = PriceruleHelper::getOperationAttributes($operation);

    if($operation->type == 'percent') {
      $pruleValue = $price * ($pruleValue / 100);
    }

    if($operation->operator == '+') {
      return $price + $pruleValue;
    }
    else { //minus
      return $price - $pruleValue;
    }

    return $price;
  }


  protected static function getOperationAttributes($operation)
  {
    $op = new JObject;

    //Set the type attribute.
    if(preg_match('#%#', $operation)) {
      $op->type = 'percent';
    }
    else {
      $op->type = 'absolute';
    }

    //Extract the operator from the operation sign.
    if(preg_match('#([+|-])%#', $operation, $matches)) {
      $op->operator = $matches[1];
    }
    else {
      $op->operator = $operation;
    }

    return $op;
  }


  public static function checkCoupon($code)
  {
    //Check for a valid code.
    if(!preg_match('#^[a-zA-Z0-9-_]{5,}$#', $code)) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_ERROR_COUPON_CODE_NOT_VALID'), 'warning');
      return false;
    }

    $user = JFactory::getUser();

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Get the needed coupon data to validate (or not) the code.
    $query->select('c.id, c.name, c.prule_id, c.max_nb_uses, c.max_nb_coupons, c.login_mandatory, cc.nb_uses')
	  ->from('#__odyssey_coupon AS c')
	  ->join('LEFT', '#__odyssey_coupon_customer AS cc ON cc.customer_id='.(int)$user->get('id').' AND cc.code='.$db->quote($code))
	  ->where('c.code='.$db->quote($code).' AND c.published=1');
    // Setup the query
    $db->setQuery($query);
    $result = $db->loadAssoc();

    if(is_null($result)) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_WARNING_NO_MATCHING_CODE'), 'warning');
      return false;
    }

    //The customer must logged in before sending the coupon code.
    if($result['login_mandatory'] == 1 && $user->get('guest') == 1) {
      // Redirect to login page.
      JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_MESSAGE_LOGIN_MANDATORY'), 'message');
      JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_users&view=login', false));
      return;
    }

    //The stock of coupons is empty.
    if($result['max_nb_coupons'] == 0) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_NOTICE_NO_MORE_COUPON_AVAILABLE'), 'notice');
      return false;
    }

    //The number of uses per customer must be checked.
    if($result['max_nb_uses'] > 0) {
      //The number of uses has been reached (or exceeded) by the customer.
      if($result['nb_uses'] >= $result['max_nb_uses']) {
	JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_WARNING_COUPON_CANNOT_BE_USED'), 'warning');
	return false;
      }
    }

    //Grab the user session.
    $session = JFactory::getSession();
    //Create the coupon session array if it doesn't exist.
    if(!$session->has('coupons', 'odyssey')) {
      $session->set('coupons', array(), 'odyssey');
    }

    //Get the coupon session array.
    $coupons = $session->get('coupons', array(), 'odyssey');
    //If the price rule id is already in the array we leave the function to prevent to
    //decrease the stock of coupon (or increase the number of uses) once again.
    if(in_array($result['prule_id'], $coupons)) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_WARNING_COUPON_ALREADY_USED'), 'warning');
      return false;
    }

    //Store the price rule id.
    $coupons[] = $result['prule_id'];
    $session->set('coupons', $coupons, 'odyssey');

    if($user->get('guest') != 1 && $result['max_nb_uses'] > 0 && ($result['nb_uses'] < $result['max_nb_uses'] || empty($result['nb_uses']))) {
      if(empty($result['nb_uses'])) {
	$columns = array('customer_id', 'code', 'nb_uses');
	$values = (int)$user->get('id').','.$db->quote($code).',1'; 
	//Insert a new row for this customer/code.
	$query->clear();
	$query->insert('#__odyssey_coupon_customer')
	      ->columns($columns)
	      ->values($values);
	$db->setQuery($query);
	$db->execute();
      }
      else { //Increase the number of uses of the coupon for this customer.
	$query->clear();
	$query->update('#__odyssey_coupon_customer')
	      ->set('nb_uses = nb_uses + 1')
	      ->where('customer_id='.(int)$user->get('id').' AND code='.$db->quote($code));
	$db->setQuery($query);
	$db->execute();
      }
    }

    //The stock of coupons is not unlimited (-1) so we have to decrease its value.
    if($result['max_nb_coupons'] > 0) {
      $query->clear();
      $query->update('#__odyssey_coupon')
	    ->set('max_nb_coupons = max_nb_coupons - 1')
	    ->where('id='.(int)$result['id']);
      $db->setQuery($query);
      $db->execute();
    }

    return;
  }


  /**
   * Gets catalog price rules set for the base number of passengers and matching the given travels.
   * Applies price rules accordingly and returns the lowest price for each travel.
   *
   * @param array  An array of travel ids.
   * @param mixed  The category id(s) which are linked to the travels.
                   Type can be an integer or an array of integers.
   *
   *
   * @return array  An array containing the lowest price for each given travel.
   */
  public static function getPricesStartingAt($travelIds, $catIds)
  {
    $user = JFactory::getUser();
    //Get user group ids to which the user belongs to.
    $groups = JAccess::getGroupsByUser($user->get('id'));
    //Get current date and time (equal to NOW() in SQL).
    $now = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);
    //Get the base number of passengers.
    $baseNbPsgr = JComponentHelper::getParams('com_odyssey')->get('base_nb_psgr', 1);

    if(!is_array($catIds)) {
      //Put the integer into an array.
      $catId = (int)$catIds;
      $catIds = array($catId);
    }

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Get possible catalog price rules set for the base numner of passengers and the given travels.
    $query->select('tpr.travel_id, pr.name, pr.behavior, pr.show_rule, pr.ordering,tpr.prule_id,'.
	           'tpr.dpt_id, pr.operation, tpr.value, tp.psgr_nb, tp.price AS normal_price')
	  ->from('#__odyssey_pricerule AS pr')
	  ->join('INNER', '#__odyssey_travel_pricerule AS tpr ON tpr.prule_id=pr.id')
	  ->join('INNER', '#__odyssey_travel_price AS tp ON tp.travel_id=tpr.travel_id AND tp.dpt_id=tpr.dpt_id')
	  ->join('INNER', '#__odyssey_prule_recipient AS prr ON (pr.recipient="customer" AND prr.item_id='.(int)$user->get('id').')'.
	                  ' OR (pr.recipient="customer_group" AND prr.item_id IN ('.implode(',', $groups).'))')
	  ->where('pr.prule_type="catalog" AND pr.target="travel" AND tpr.travel_id IN('.implode(',', $travelIds).')')
	  //Don't collect price rules related to coupon.
	  ->where('(pr.behavior="XOR" OR pr.behavior= "AND")')
	  //Get only price rules set for the base number of passengers.
	  ->where('tpr.psgr_nb='.(int)$baseNbPsgr.' AND tp.psgr_nb='.(int)$baseNbPsgr.' AND tpr.value > 0')
	  ->where('prr.prule_id=pr.id AND pr.published=1')
	  //Check against publication dates (start and stop).
	  ->where('('.$db->quote($now).' < pr.publish_down OR pr.publish_down = "0000-00-00 00:00:00")')
	  ->where('('.$db->quote($now).' > pr.publish_up OR pr.publish_up = "0000-00-00 00:00:00")')
	  ->order('tpr.travel_id, pr.ordering, tpr.prule_id, tpr.dpt_id');
    $db->setQuery($query);
    $results = $db->loadAssocList();

    //Rearrange results for more convenience.  
    $travelPrules = array();
    foreach($results as $key => $result) {
      //First, set the array index as the travel id then store the corresponding price 
      //rule rows.  
      if(!array_key_exists($result['travel_id'], $travelPrules)) {
	$travelPrules[$result['travel_id']]['prules'] = array($result);
      }
      else {
	$travelPrules[$result['travel_id']]['prules'][] = $result;
      }

      //We reach the end of the array or the next element is regarding another travel.
      if(!isset($results[$key + 1]) || $results[$key + 1]['travel_id'] != $result['travel_id']) {
	//Rearrange and store price rule data. 
	$prules = PriceruleHelper::setTravelPruleRows($travelPrules[$result['travel_id']]['prules'], true);
	$travelPrules[$result['travel_id']]['prules'] = $prules;
      }
    }

    $query->clear();
    //Get possible catalog price rules set for the base number of passenger and linked to the travel category.
    $query->select('t.id AS travel_id, pr.name,pr.behavior, pr.show_rule, pr.ordering, prt.prule_id,'.
		   'tp.dpt_id, pr.operation, pr.value, tp.psgr_nb, tp.price AS normal_price')
	  ->from('#__odyssey_pricerule AS pr')
	  ->join('INNER', '#__odyssey_prule_target AS prt ON prt.prule_id=pr.id')
	  //Get the base number of passenger prices as we need them to compute price rule results.
	  ->join('INNER', '#__odyssey_travel AS t ON t.catid=prt.item_id')
	  ->join('INNER', '#__odyssey_travel_price AS tp ON tp.travel_id=t.id AND tp.psgr_nb='.(int)$baseNbPsgr)
	  ->join('INNER', '#__odyssey_prule_recipient AS prr ON (pr.recipient="customer" AND prr.item_id='.(int)$user->get('id').')'.
	                  ' OR (pr.recipient="customer_group" AND prr.item_id IN ('.implode(',', $groups).'))')
	  ->where('pr.prule_type="catalog" AND pr.target="travel_cat" AND prt.item_id IN('.implode(',', $catIds).')')
	  //Don't collect price rules related to coupon.
	  ->where('(pr.behavior="XOR" OR pr.behavior= "AND")')
	  //Get only price rules set for the base number of passengers.
	  ->where('(prt.psgr_nbs=0 OR prt.psgr_nbs REGEXP "(^'.$baseNbPsgr.'$)|(^'.$baseNbPsgr.',)|(,'.$baseNbPsgr.',)|(,'.$baseNbPsgr.'$)")')
	  ->where('prr.prule_id=pr.id AND pr.published=1')
	  //Check against publication dates (start and stop).
	  ->where('('.$db->quote($now).' < pr.publish_down OR pr.publish_down = "0000-00-00 00:00:00")')
	  ->where('('.$db->quote($now).' > pr.publish_up OR pr.publish_up = "0000-00-00 00:00:00")')
	  ->order('t.id, pr.ordering, prt.prule_id,tp.dpt_id');
//file_put_contents('debog_prule.txt', print_r($query->__toString(), true));
    $db->setQuery($query);
    $results = $db->loadAssocList();

    //Rearrange results for more convenience.  
    $travelCatPrules = array();
    foreach($results as $key => $result) {
      if(!array_key_exists($result['travel_id'], $travelCatPrules)) {
	$travelCatPrules[$result['travel_id']]['prules'] = array($result);
      }
      else {
	$travelCatPrules[$result['travel_id']]['prules'][] = $result;
      }

      if(!isset($results[$key + 1]) || $results[$key + 1]['travel_id'] != $result['travel_id']) {
	$prules = PriceruleHelper::setTravelPruleRows($travelCatPrules[$result['travel_id']]['prules'], true);
	$travelCatPrules[$result['travel_id']]['prules'] = $prules;
      }
    }

    //No price rule found for those travels.
    if(empty($travelPrules) && empty($travelCatPrules)) {
      return array();
    }

    //Price rules have to be merged.
    //Note: The final result is stored in the $travelPrules array.
    foreach($travelIds as $travelId) {
      //Travel and travel category price rules are linked to the same travel.
      if(isset($travelPrules[$travelId]) && isset($travelCatPrules[$travelId])) {
	//Merge travel and travel category price rules together.
	$catalogPrules = array_merge($travelPrules[$travelId]['prules'], $travelCatPrules[$travelId]['prules']);
	//The elements of the merged array must be reorder according to their "ordering" attribute.
	//In order to do so we use a simple bubble sort algorithm.
	$nbPrules = count($catalogPrules);
	for($i = 0; $i < $nbPrules; $i++) {
	  for($j = 0; $j < $nbPrules - 1; $j++) {
	    if($catalogPrules[$j]['ordering'] > $catalogPrules[$j + 1]['ordering']) {
	      $temp = $catalogPrules[$j + 1];
	      $catalogPrules[$j + 1] = $catalogPrules[$j];
	      $catalogPrules[$j] = $temp;
	    }
	  }
	}

	$travelPrules[$travelId]['prules'] = $catalogPrules;
      }
      elseif(!isset($travelPrules[$travelId]) && isset($travelCatPrules[$travelId])) {
	//Store the category price rules in the $travelPrules array.
	$travelPrules[$travelId]['prules'] = $travelCatPrules[$travelId]['prules'];
      }
    }

    //Compute and select the lower price for each travel.
    $lowestPrice = array();

    foreach($travelPrules as $travelId => $travelPrule) {
      $delete = false;
      foreach($travelPrule['prules'] as $key => $prule) {
	if($delete) {
	  unset($travelPrules[$travelId]['prules'][$key]);
	  continue;
	}

	foreach($prule['dpt_ids'] as $dptId => $data) {
	  //Get the normal price.
	  $price = $data[$baseNbPsgr + 1];
	  //Check if a previous price rule has been applied to the normal price.
	  if(isset($travelPrules[$travelId]['prules'][$key - 1]['dpt_ids'][$dptId][$baseNbPsgr + 2])) {
	    //Get the normal price previously modified by a price rule. 
	    $price = $travelPrules[$travelId]['prules'][$key - 1]['dpt_ids'][$dptId][$baseNbPsgr + 2];
	  }

	  //Apply the price rule and store the modified price.
	  $price = UtilityHelper::formatNumber(PriceruleHelper::computePriceRule($prule['operation'], $data[$baseNbPsgr], $price));
	  $travelPrules[$travelId]['prules'][$key]['dpt_ids'][$dptId][$baseNbPsgr + 2] = $price;  

	  //Compare and replace (if needed) the modified price in order to end up with 
	  //the lowest price for this travel.
	  if(!isset($lowestPrice[$travelId])) {
	    $lowestPrice[$travelId] = array('normal_price' => $data[$baseNbPsgr + 1], 'price' => $price);
	  }
	  else {
	    if($price < $lowestPrice[$travelId]['price']) {
	      $lowestPrice[$travelId]['normal_price'] = $data[$baseNbPsgr + 1];
	      $lowestPrice[$travelId]['price'] = $price;
	    }
	  }
	}

	//Check for a possible exclusive price rule. 
	if($prule['behavior'] == 'XOR') {
	  $delete = true;
	}
      }
    }
    //echo '<pre>';
    //var_dump($lowestPrice);
    //var_dump($travelPrules);
    //echo '</pre>';

    return $lowestPrice;
  }


  //Once the addons have been selected we need to merge their price rules with the travel's
  //and check them in case an exclusive addon price rule cancels one or more
  //travel price rules. 
  public static function mergeAndCheckPriceRules()
  {
    //Grab the user session.
    $session = JFactory::getSession();
    $travel = $session->get('travel', array(), 'odyssey'); 
    $addons = $session->get('addons', array(), 'odyssey'); 
    $travelPrules = $addonPrules = 0;
    $priceRules = array();

    //Collect the travel price rules.
    if(isset($travel['pricerules'])) {
      foreach($travel['pricerules'] as $priceRule) {
	//Set a tag which will be useful later.
	$priceRule['travel_prule'] = true;
	$priceRules[] = $priceRule;
	$travelPrules++;
      }
    }

    //No need to go further.
    if(!$travelPrules) {
      return;
    }

    //Collect all of the addon and addon option price rules.
    foreach($addons as $addon) {
      if(isset($addon['pricerules'])) {
	foreach($addon['pricerules'] as $priceRule) {
	  $priceRules[] = $priceRule;
	  $addonPrules++;
	}
      }

      if(isset($addon['options'])) {
	foreach($addon['options'] as $option) {
	  if(isset($option['pricerules'])) {
	    foreach($option['pricerules'] as $priceRule) {
	      $priceRules[] = $priceRule;
	      $addonPrules++;
	    }
	  }
	}
      }
    }

    //No need to go further.
    if(!$addonPrules) {
      return;
    }

    //Remove possible travel price rules due to an exclusive addon (or addon option) price rule.
    $priceRules = PriceruleHelper::checkExclusivePriceRules($priceRules);
    //Get the ids of the remaining travel price rules. 
    $pruleIds = array();
    foreach($priceRules as $priceRule) {
      if(isset($priceRule['travel_prule'])) {
	$pruleIds[] = $priceRule['id'];
      }
    }

    //Some travel price rules have been removed.
    if(count($pruleIds) < $travelPrules) {
      //Reset the travel session array regarding price rules.
      $travel['travel_price'] = $travel['normal_price'];
      unset($travel['normal_price']);
      unset($travel['pricerules']);
      $session->set('travel', $travel, 'odyssey'); 

      //The remaining travel price rules have to be computed again.
      if(count($pruleIds)) {
	$travel = PriceruleHelper::getMatchingTravelPriceRules($pruleIds, $travel);
	$session->set('travel', $travel, 'odyssey'); 
      }
    }

    return;
  }


  public static function checkExclusivePriceRules($priceRules)
  {
    //The elements of the merged array must be reorder according to their "ordering" attribute.
    //In order to do so we use a simple bubble sort algorithm.
    $nbPrules = count($priceRules);
    for($i = 0; $i < $nbPrules; $i++) {
      for($j = 0; $j < $nbPrules - 1; $j++) {
	if($priceRules[$j]['ordering'] > $priceRules[$j + 1]['ordering']) {
	  $temp = $priceRules[$j + 1];
	  $priceRules[$j + 1] = $priceRules[$j];
	  $priceRules[$j] = $temp;
	}
      }
    }

    //Grab the user session.
    $session = JFactory::getSession();
    //Get the coupon array to check possible exclusive coupon price rules. 
    $coupons = $session->get('coupons', array(), 'odyssey'); 

    //Check for a possible exclusive price rule. 
    $delete = false;
    foreach($priceRules as $key => $priceRule) {
      if($delete) {
	unset($priceRules[$key]);
	continue;
      }

      //The price rule is exclusive.
      if($priceRule['behavior'] == 'XOR') {
	//Price rules coming next must be deleted.
	$delete = true;
      }

      //The exclusive coupon price rules must be checked first.
      if($priceRule['behavior'] == 'CPN_XOR') {
	//Allow deleting only if the coupon has been validated by the customer.
	if(in_array($priceRule['prule_id'], $coupons)) {
	  $delete = true;
	}
      }
    }

    return $priceRules;
  }
}

