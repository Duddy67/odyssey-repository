<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
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

$finalAmount = UtilityHelper::formatNumber($travel['final_amount'], $settings['digits_precision']);
$allowDeposit = false;
if($travel['outstanding_balance'] == $travel['final_amount']) {
  $allowDeposit = true;
  $depositAmount = $finalAmount * ($settings['deposit_rate'] / 100);
  $depositAmount = UtilityHelper::roundNumber($depositAmount, $settings['rounding_rule'], $settings['digits_precision']);
  $depositAmount = UtilityHelper::formatNumber($depositAmount, $settings['digits_precision']);
}

$outstandingBalance = UtilityHelper::formatNumber($travel['outstanding_balance'], $settings['digits_precision']);
?>

<form action="index.php?option=com_odyssey&task=payment.setBooking" method="post" name="booking" id="booking">

  <div class="outstdbal-options">
    <?php if($allowDeposit) : //Give a choice to the customer. ?>
      <div class="deposit">
	<?php echo JText::_('COM_ODYSSEY_DEPOSIT_TITLE'); ?>
	<span class="price"><?php echo $depositAmount; ?></span>
	<span class="currency"><?php echo $settings['currency']; ?></span>
	<input type="radio" name="booking_options" id="deposit" value="deposit" >
      </div>

    <div class="whole-price">
      <?php echo JText::_('COM_ODYSSEY_WHOLE_PRICE_TITLE'); ?>
      <span class="price"><?php echo $finalAmount; ?></span>
      <span class="currency"><?php echo $settings['currency']; ?></span>
      <input type="radio" name="booking_options" id="whole-price" value="whole_price" checked="checked" >
    </div>
    <?php else : //Pay the remaining amount. ?>
      <?php echo JText::_('COM_ODYSSEY_OUTSTANDING_BALANCE_TITLE'); ?>
      <span class="price"><?php echo $outstandingBalance; ?></span>
      <span class="currency"><?php echo $settings['currency']; ?></span>
      <input type="hidden" name="booking_options" value="remaining" >
    <?php endif; ?>
  </div>

  <div id="btn-message">
    <input type="submit" class="btn btn-warning" onclick="hideButton('btn')" value="<?php echo JText::_('COM_ODYSSEY_BUTTON_NEXT'); ?>" />
  </div>
</form>

