<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

$addonOptions = $displayData['addon_options'];
$addonOptionPrules = $displayData['addon_option_prules'];
$addon = $displayData['addon'];
$currency = $displayData['currency'];

echo '<div class="addon-options">';

foreach($addonOptions as $key => $addonOption) {
  //Check that the addon option belongs to the current addon.
  if($addonOption['step_id'] == $addon['step_id'] && $addonOption['addon_id'] == $addon['addon_id']) {
    //The first radio button of a group (single select) is checked by default.
    $checked = '';
    if($addonOption['ordering'] == 1) {
      $checked = ' checked="checked"';
    }

    //Display information and price.
    echo '<div class="addon-option">'.
         '<h2 class="addon-option-title">'.$this->escape($addonOption['name']).'</h2>';

    $normalPrice = $price = $addonOption['price'];
    //Check price rules for this addon.
    $isPriceRule = false;
    $priceRuleNames = array();
    if(isset($addonOptionPrules[$addonOption['step_id']][$addonOption['addon_id']][$addonOption['addon_option_id']])) {
      foreach($addonOptionPrules[$addonOption['step_id']][$addonOption['addon_id']][$addonOption['addon_option_id']] as $addonOptionPrule) {
	//Get the new price. 
	$price = PriceruleHelper::computePriceRule($addonOptionPrule['operation'], $addonOptionPrule['value'], $price);

	if($addonOptionPrule['show_rule']) {
	  echo '<div class="pricerule-name">'.$addonOptionPrule['name'].' <span class="pricerule-value">'.
	        UtilityHelper::formatPriceRule($addonOptionPrule['operation'], $addonOptionPrule['value']).'</span></div>';
	  $isPriceRule = true;
	}
	else { //Hidden price rule.
	  //We applied the hidden price rule values to the normal 
	  //price so that there is no misunderstanding about the 
	  //computing price in case other price rules are shown.
	  $normalPrice = PriceruleHelper::computePriceRule($addonOptionPrule['operation'], $addonOptionPrule['value'], $normalPrice);
	}

	//Don't go further in case of Exclusive price rule.
	if($addonOptionPrule['behavior'] == 'XOR') {
	  break;
	}
      }
    }

    if($addonOption['price'] > 0) {
      //Check for price rules.
      if($isPriceRule) {
	echo '<div class="addon-price"><span class="normal-price">'.
	      UtilityHelper::formatNumber($normalPrice).'</span><span class="currency">'.$currency.'</span></div>';
      }

      echo '<div class="addon-price">'.
	   '<span class="price">'.UtilityHelper::formatNumber($price).'</span>'.
	   '<span class="currency">'.$currency.'</span></div>';
    }

    //Set the addon option tag according to the selection type.
    if($addon['option_type'] == 'single_sel') {
      echo '<input type="radio" class="option-single" name="option_single_'.$addonOption['step_id'].'_'.$addonOption['addon_id'].'" '.
	   'value="'.$addonOption['addon_option_id'].'" '.$checked.'>';
    }
    else { //multi_sel
      echo '<input type="checkbox" class="option-multi" name="option_multi_'.$addonOption['step_id'].'_'.$addonOption['addon_id'].'[]" '.
	   'value="'.$addonOption['addon_option_id'].'" >';
    }

    echo '</div>';
  }
}

echo '</div>';
?>


