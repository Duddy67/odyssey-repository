<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
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
$finalAmount = $travel['travel_price'];
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

//Check for travel price rules.
foreach($addons as $addon) {
  $bookingDetails .= '<tr><td>'.$addon['name'].'</td><td>'.JText::_('COM_ODYSSEY_HEADING_ADDON').'</td>';

  if($addon['price'] > 0) {
    $bookingDetails .= '<td>'.UtilityHelper::formatNumber($addon['price'], $settings['digits_precision']).' '.$currency.'</td></tr>';
    $finalAmount = $finalAmount + $addon['price'];
  }
  else {
    $bookingDetails .= '<td>'.JText::_('COM_ODYSSEY_HEADING_INCLUDED').'</td></tr>';
  }

  foreach($addon['options'] as $option) {
    $bookingDetails .= '<tr><td>'.$option['name'].'</td><td>'.JText::_('COM_ODYSSEY_HEADING_ADDON_OPTION').'</td>';

    if($option['price'] > 0) {
      $bookingDetails .= '<td>'.UtilityHelper::formatNumber($option['price'], $settings['digits_precision']).' '.$currency.'</td></tr>';
      $finalAmount = $finalAmount + $option['price'];
    }
    else {
      $bookingDetails .= '<td>'.JText::_('COM_ODYSSEY_HEADING_INCLUDED').'</td></tr>';
    }
  }
}

$bookingDetails .= '<tr><td>'.$travel['name'].'</td><td>'.JText::_('COM_ODYSSEY_HEADING_TRAVEL').'</td><td>';

if(isset($travel['pricerules'])) {
  $bookingDetails .= '<span style="text-decoration: line-through;">'.
		   UtilityHelper::formatNumber($travel['normal_price'], $settings['digits_precision']).' '.$currency.'</span><br />';
}

$bookingDetails .= UtilityHelper::formatNumber($travel['travel_price'], $settings['digits_precision']).' '.$currency.'</td></tr>';

if($travel['transit_price'] > 0) {
  $bookingDetails .= '<tr><td>'.$travel['dpt_city_name'].'</td><td>'.JText::_('COM_ODYSSEY_HEADING_TRANSIT_CITY').'</td><td>'.
		    UtilityHelper::formatNumber($travel['transit_price'], $settings['digits_precision']).' '.$currency.'</td></tr>';
  $finalAmount = $finalAmount + $travel['transit_price'];
}

$bookingDetails .= '<tr><td></td><td></td><td>'.
                 UtilityHelper::formatNumber($finalAmount, $settings['digits_precision']).' '.$currency.'</td></tr>';
$bookingDetails .= '</table>';

echo $bookingDetails;
?>
</div>





