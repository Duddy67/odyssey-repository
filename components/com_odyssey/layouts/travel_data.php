<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

$item = $displayData['item'];
$travelData = $displayData['travel_data'];
$travel = $travelData['travel'];
$travelPrules = $travelData['travel_pricerules'];
$transitCities = $travelData['transit_cities'];

//Grab the user session.
$session = JFactory::getSession();
//Get the coupon array to check possible coupon price rules. 
$coupons = $session->get('coupons', array(), 'odyssey'); 

//echo '<pre>';
//var_dump($travelPrules);
//echo '</pre>';
?>
<script type="text/javascript">
function checkForm() {
  //Check that a valid date has been properly picked.
  if(document.getElementById('date-type').value == 'period') {
    var datepicker = document.getElementById('date-picker');
    if(datepicker.value == '') {
    alert('<?php echo JText::_('COM_ODYSSEY_WARNING_NO_DEPARTURE_DATE_PICKED'); ?>');
      document.getElementById('datepicker').style.border = '1px solid red !important';
      return false;
    }
  }

  //Hide the submit button.
  hideButton('btn');
  //Submit the form.
  return true;
}
</script>

<form action="index.php?option=com_odyssey&task=travel.setTravel" method="post" name="travel" id="travel" onsubmit="return checkForm();" >
  <div class="travel-selection">
    <select name="departures" id="departures">
    </select>

    <select name="nb_psgr" id="nb-psgr">
    </select>

    <select name="dpt_cities" id="dpt-cities">
    </select>
  </div>

  <input type="text" name="datepicker" class="datepicker" readonly id="datepicker">
  <input type="hidden" name="date_picker" id="date-picker">

  <div id="travel-prices">
  <?php //Display all the price data for each departure at once. Some Javascript functions
	//are used to display prices properly according to the drop down lists selections. 
	foreach($travel as $data) {
	  //Don't display the departure if allotment is empty.
	  if($data['allotment'] == 0) {
	    continue;
	  }

	  echo '<div id="departure-'.$data['dpt_id'].'">';

	  foreach($data['price_per_psgr'] as $psgrNb => $price) { //Display travel prices per passenger.
	    //In case allotment is lower than max passengers we don't go further.
	    if($psgrNb > $data['allotment']) {
	      break;
	    }

	    //Check for price rules.
	    $isPriceRule = false;
	    //Store the regular price as normal price in case of price rules.
	    $normalPrice = $price;
	    foreach($travelPrules as $travelPrule) {
	      $apply = true;
	      //Check for coupon price rules. As long as they are not validated by the
	      //customer (ie: contained in the coupon array) we don't apply them.
	      if(($travelPrule['behavior'] == 'CPN_XOR' || $travelPrule['behavior'] == 'CPN_AND') 
		 && !in_array($travelPrule['prule_id'], $coupons)) {
		$apply = false;
	      }

	      if($apply && isset($travelPrule['dpt_ids'][$data['dpt_id']]) && $travelPrule['dpt_ids'][$data['dpt_id']][$psgrNb] > 0) {
		//Create a hidden field for each matching price rule with the needed data.
		//pattern: prule_[dpt_id]_[psgr_nb]_[prule_id]
		echo '<input type="hidden" name="prule_'.$data['dpt_id'].'_'.$psgrNb.'_'.$travelPrule['prule_id'].'" '.
		     'value="'.$travelPrule['prule_id'].'" />';

		//Get the new price. 
		$price = PriceruleHelper::computePriceRule($travelPrule['operation'], $travelPrule['dpt_ids'][$data['dpt_id']][$psgrNb], $price);
		//
		if($travelPrule['show_rule']) {
		  //echo '<div class="pricerule-name" id="pricerule-name-'.$psgrNb.'-'.$data['dpt_id'].'">'.$travelPrule['name'].'</div>';
		  $isPriceRule = true;
		}
	      }
	    }

	    if($isPriceRule) {
	      echo '<div id="normal-price-psgr-'.$psgrNb.'-'.$data['dpt_id'].'"><span class="normal-price">'.
		    $normalPrice.'</span><span class="currency">'.$item->currency.'</span></div>';
	    }

	    echo '<div id="price-psgr-'.$psgrNb.'-'.$data['dpt_id'].'">'.JText::_('COM_ODYSSEY_PRICE').'<span class="price">'.
		  $price.'</span><span class="currency">'.$item->currency.'</span></div>';
	  }

	  foreach($transitCities as $transitCity) { //Display transit city prices per passenger.
	    if($transitCity['dpt_id'] == $data['dpt_id']) {
	      echo '<div id="transitcity-'.$data['dpt_id'].'-'.$transitCity['city_id'].'">';
	      foreach($transitCity['price_per_psgr'] as $psgrNb => $price) {
		//In case allotment is lower than max passengers we don't go further.
		if($psgrNb > $data['allotment']) {
		  break;
		}

		if($price > 0) {
		  echo '<div id="transitcity-price-psgr-'.$psgrNb.'-'.$data['dpt_id'].'-'.$transitCity['city_id'].'">'.
		       JText::_('COM_ODYSSEY_EXTRA').'<span class="price">'.
		       $price.'</span><span class="currency">'.$item->currency.'</span></div>';
		}
	      }
	      echo '</div>';
	    }
	  }

	  echo '</div>';
	}
  ?>
  </div>

  <input type="hidden" name="date_type" id="date-type" value="<?php echo $item->date_type; ?>" />
  <input type="hidden" name="travel_id" value="<?php echo $item->id; ?>" />
  <input type="hidden" name="dpt_step_id" value="<?php echo $item->dpt_step_id; ?>" />
  <div id="btn-message">
    <input type="submit" class="btn btn-warning" value="<?php echo
    JText::_('COM_ODYSSEY_BUTTON_BOOK'); ?>" />
  </div>
</form>

<div class="coupon-information">
  <?php echo JText::_('COM_ODYSSEY_COUPON_INFORMATION'); ?>
  <form action="index.php?option=com_odyssey&task=travel.checkCatalogCoupon" method="post" name="coupon" id="coupon">
    <input type="text" name="code" class="coupon-code" id="coupon-code" value="" />
    <input type="submit" class="btn btn-success" value="<?php echo JText::_('COM_ODYSSEY_BUTTON_SEND'); ?>" />
    <input type="hidden" name="travel_id" value="<?php echo $item->id; ?>" />
    <input type="hidden" name="catid" value="<?php echo $item->catid; ?>" />
  </form>
</div>


