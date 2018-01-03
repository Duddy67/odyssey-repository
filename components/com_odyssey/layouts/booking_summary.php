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

$addons = array();
if(isset($displayData['addons'])) {
  $addons = $displayData['addons'];
}

//Set departure date according to the date type.
$dptDate = $travel['date_time'];
$dateFormat = 'DATE_FORMAT_LC2';
if($travel['date_type'] == 'period') {
  $dateFormat = 'DATE_FORMAT_LC3';
  $dptDate = $travel['date_picker'];
}

$currency = $settings['currency'];
//To avoid rounding number problems during the calculation, all figures are formated
//according to the digits precision parameter.
$finalAmount = UtilityHelper::formatNumber($travel['travel_price'], $settings['digits_precision']);
?>

<div class="booking-summary">
  <h2><?php echo $travel['name']; ?></h2>
  <h3><?php echo JText::_('COM_ODYSSEY_TRAVEL_DETAILS_TITLE'); ?></h3>
<?php
$travelDetails = '<table class="travel-details table table-condensed"><tr><th>'.JText::_('COM_ODYSSEY_HEADING_DEPARTURE_DATE').'</th><th>'.
		 JText::_('COM_ODYSSEY_HEADING_DEPARTURE_CITY').'</th><th>'.JText::_('COM_ODYSSEY_HEADING_PASSENGERS').'</th></tr>';
$travelDetails .= '<tr><td>'.JHtml::_('date', $dptDate, JText::_($dateFormat)).'</td>'.
                  '<td>'.$travel['dpt_city_name'].'</td><td>'.$travel['nb_psgr'].'</td></tr>';
$travelDetails .= '</table>';

echo $travelDetails;
?>

  <h3><?php echo JText::_('COM_ODYSSEY_BOOKING_DETAILS_TITLE'); ?></h3>
<?php
$bookingDetails = '<table class="booking-details table table-condensed"><tr><th>'.JText::_('COM_ODYSSEY_HEADING_NAME').'</th><th>'.
		   JText::_('COM_ODYSSEY_HEADING_TYPE').'</th><th>'.JText::_('COM_ODYSSEY_HEADING_PRICE').'</th></tr>';

foreach($addons as $addon) {
  $bookingDetails .= '<tr><td>'.$addon['name'].'</td><td>'.JText::_('COM_ODYSSEY_HEADING_ADDON').'</td>';

  if($addon['price'] > 0) {
    $bookingDetails .= '<td>';
    $addonPrice = UtilityHelper::formatNumber($addon['price'], $settings['digits_precision']);

    //Check for price rules.
    if(isset($addon['pricerules'])) {
      $bookingDetails .= '<span style="text-decoration: line-through;">'.
			 UtilityHelper::formatNumber($addon['normal_price'], $settings['digits_precision']).' '.$currency.'</span><br />';
    }

    $bookingDetails .= UtilityHelper::formatNumber($addon['price'], $settings['digits_precision']).' '.$currency.'</td></tr>';
    $finalAmount += $addonPrice;
    $finalAmount = UtilityHelper::formatNumber($finalAmount, $settings['digits_precision']);
  }
  else {
    $bookingDetails .= '<td>'.JText::_('COM_ODYSSEY_HEADING_INCLUDED').'</td></tr>';
  }

  foreach($addon['options'] as $option) {
    $bookingDetails .= '<tr><td class="summary-addon-option">'.$option['name'].'</td><td>'.JText::_('COM_ODYSSEY_HEADING_ADDON_OPTION').'</td>';

    if($option['price'] > 0) {
      $bookingDetails .= '<td>';
      $optionPrice = UtilityHelper::formatNumber($option['price'], $settings['digits_precision']);

      //Check for price rules.
      if(isset($option['pricerules'])) {
	$bookingDetails .= '<span style="text-decoration: line-through;">'.
			   UtilityHelper::formatNumber($option['normal_price'], $settings['digits_precision']).' '.$currency.'</span><br />';
      }

      $bookingDetails .= UtilityHelper::formatNumber($option['price'], $settings['digits_precision']).' '.$currency.'</td></tr>';
      $finalAmount += $optionPrice;
      $finalAmount = UtilityHelper::formatNumber($finalAmount, $settings['digits_precision']);
    }
    else {
      $bookingDetails .= '<td>'.JText::_('COM_ODYSSEY_HEADING_INCLUDED').'</td></tr>';
    }
  }
}

$bookingDetails .= '<tr><td>'.$travel['name'].'</td><td>'.JText::_('COM_ODYSSEY_HEADING_TRAVEL').'</td><td>';

//Check for price rules.
if(isset($travel['pricerules'])) {
  $bookingDetails .= '<span style="text-decoration: line-through;">'.
		     UtilityHelper::formatNumber($travel['normal_price'], $settings['digits_precision']).' '.$currency.'</span><br />';
}

$bookingDetails .= UtilityHelper::formatNumber($travel['travel_price'], $settings['digits_precision']).' '.$currency.'</td></tr>';

if($travel['transit_price'] > 0) {
  $transitPrice = UtilityHelper::formatNumber($travel['transit_price'], $settings['digits_precision']);

  $bookingDetails .= '<tr><td>'.$travel['dpt_city_name'].'</td><td>'.JText::_('COM_ODYSSEY_HEADING_TRANSIT_CITY').'</td><td>'.
		    UtilityHelper::formatNumber($travel['transit_price'], $settings['digits_precision']).' '.$currency.'</td></tr>';
  $finalAmount += $transitPrice;
  $finalAmount = UtilityHelper::formatNumber($finalAmount, $settings['digits_precision']);
}

$bookingDetails .= '<tr><td></td><td></td><td>'.
		   '<span id="js_display_total_amount">'.UtilityHelper::formatNumber($finalAmount, $settings['digits_precision']).
		   '</span> '.$currency.'</td></tr>';
$bookingDetails .= '</table>';

if($travel['overlapping']) {
  $bookingDetails .= '<div class="alert">'.JText::_('COM_ODYSSEY_OVERLAPPING_INFORMATION').'</div>';
}

echo $bookingDetails;

//For the Javascript dynamical addon prices functions. 
echo '<form method="post" name="js_addons" ><input type="hidden" name="js_total_amount" id="js_total_amount" value="'.$finalAmount.'"></form>';
?>
</div>





