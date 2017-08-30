<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

$travel = $displayData['travel'];
$settings = $displayData['settings'];
$addons = $displayData['addons'];


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

    $orderDetails .= '<tr><td class="details-addon-option">'.$option['name'].'</td><td>'.JText::_('COM_ODYSSEY_HEADING_ADDON_OPTION').'</td>';

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

echo $orderDetails;

