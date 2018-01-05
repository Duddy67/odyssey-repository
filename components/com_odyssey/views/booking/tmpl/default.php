<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// no direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');

//Grab the user session.
$session = JFactory::getSession();
$travel = $session->get('travel', array(), 'odyssey'); 
$addons = $session->get('addons', array(), 'odyssey'); 
$settings = $session->get('settings', array(), 'odyssey'); 
var_dump($session->get('passengers', array(), 'odyssey'));
$date = $travel['date_time'];
if($travel['date_type'] == 'period') {
  $date = $travel['date_picker'].' 12:00';
}

$finalAmount = TravelHelper::getFinalAmount($travel, $addons, $settings['digits_precision']);

$allowOption = $allowDeposit = false;
//Get the remaining time until the departure.
$days = UtilityHelper::getRemainingDays($date);

if($days >= $this->params->get('option_time_limit')) {
  $allowOption = true;
}

if($days >= $this->params->get('deposit_time_limit')) {
  $allowDeposit = true;
  $depositAmount = $finalAmount * ($settings['deposit_rate'] / 100);
  $depositAmount = UtilityHelper::formatNumber($depositAmount, $settings['digits_precision']);
}

//var_dump($time);
?>

<?php echo JLayoutHelper::render('booking_breadcrumb', array('position' => 'booking', 'travel' => $travel),
                                  JPATH_SITE.'/components/com_odyssey/layouts/'); ?>

<?php echo JLayoutHelper::render('booking_summary', array('travel' => $travel, 'settings' => $settings, 'addons' => $addons),
				  JPATH_SITE.'/components/com_odyssey/layouts/'); ?>

<form action="index.php?option=com_odyssey&task=payment.setBooking" method="post" name="booking" id="booking">

  <div class="booking-options">
    <?php if($allowOption) : ?>
      <div class="take-option">
	<?php echo JText::_('COM_ODYSSEY_TAKE_OPTION_TITLE'); ?>
	<input type="radio" name="booking_options" id="take-option" value="take_option" >
      </div>
    <?php endif; ?>

    <?php if($allowDeposit) : ?>
      <div class="deposit">
	<?php echo JText::_('COM_ODYSSEY_DEPOSIT_TITLE'); ?>
	<span class="deposit-amount"><?php echo $depositAmount; ?></span>
	<span class="currency"><?php echo $settings['currency']; ?></span>
	<input type="radio" name="booking_options" id="deposit" value="deposit" >
      </div>
    <?php endif; ?>

    <div class="whole-price">
      <?php echo JText::_('COM_ODYSSEY_WHOLE_PRICE_TITLE'); ?>
      <span class="price"><?php echo $finalAmount; ?></span>
      <span class="currency"><?php echo $settings['currency']; ?></span>
      <input type="radio" name="booking_options" id="whole-price" value="whole_price" checked="checked" >
    </div>
  </div>

  <div id="btn-message">
    <input type="submit" class="btn btn-warning" onclick="hideButton('btn')" value="<?php echo JText::_('COM_ODYSSEY_BUTTON_NEXT'); ?>" />
  </div>
</form>

