<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

$travel = $displayData['travel'];
$position = $displayData['position'];

$activeLinks = array('travel' => 0, 'addons' => 0, 'passengers' => 0, 'booking' => 0, 'payment' => 0);

foreach($activeLinks as $key => $value) {
  if($key != $position) {
    $activeLinks[$key] = 1;
  }
  else {
    break;
  }
}
?>

<div class="booking-breadcrumb">
  <?php if($activeLinks['travel']) : ?>
    <a href="<?php echo JRoute::_('index.php?option=com_odyssey&view=travel&id='.$travel['travel_id'].':'.$travel['alias'].'&catid='.$travel['alias'].':category', false); ?>">
      <div class="travel"><?php echo JText::_('COM_ODYSSEY_BREADCRUMB_TRAVEL'); ?></div>
    </a>
  <?php else : ?>
    <div class="travel"><?php echo JText::_('COM_ODYSSEY_BREADCRUMB_TRAVEL'); ?></div>
  <?php endif; ?>

  <div class="breadcrumb-arrow">>></div>
  <?php if($activeLinks['addons']) : ?>
    <a href="<?php echo JRoute::_('index.php?option=com_odyssey&view=addons&alias='.$travel['alias'], false); ?>">
      <div class="addons"><?php echo JText::_('COM_ODYSSEY_BREADCRUMB_ADDONS'); ?></div>
    </a>
  <?php else :
          $currentPosition = '';
          if($position == 'addons') {
	    $currentPosition = 'current-position';
	  }
    ?>
    <div class="addons <?php echo $currentPosition; ?>"><?php echo JText::_('COM_ODYSSEY_BREADCRUMB_ADDONS'); ?></div>
  <?php endif; ?>

  <div class="breadcrumb-arrow">>></div>
  <?php if($activeLinks['passengers']) : ?>
    <a href="<?php echo JRoute::_('index.php?option=com_odyssey&view=passengers&alias='.$travel['alias'], false); ?>">
      <div class="passengers"><?php echo JText::_('COM_ODYSSEY_BREADCRUMB_PASSENGERS'); ?></div>
    </a>
  <?php else :
          $currentPosition = '';
          if($position == 'passengers') {
	    $currentPosition = 'current-position';
	  }
    ?>
    <div class="passengers <?php echo $currentPosition; ?>"><?php echo JText::_('COM_ODYSSEY_BREADCRUMB_PASSENGERS'); ?></div>
  <?php endif; ?>

  <div class="breadcrumb-arrow">>></div>
  <?php if($activeLinks['booking']) : ?>
    <a href="<?php echo JRoute::_('index.php?option=com_odyssey&view=booking&alias='.$travel['alias'], false); ?>">
      <div class="booking"><?php echo JText::_('COM_ODYSSEY_BREADCRUMB_BOOKING'); ?></div>
    </a>
  <?php else :
          $currentPosition = '';
          if($position == 'booking') {
	    $currentPosition = 'current-position';
	  }
    ?>
    <div class="booking <?php echo $currentPosition; ?>"><?php echo JText::_('COM_ODYSSEY_BREADCRUMB_BOOKING'); ?></div>
  <?php endif; ?>

  <div class="breadcrumb-arrow">>></div>
  <div class="payment"><?php echo JText::_('COM_ODYSSEY_BREADCRUMB_PAYMENT'); ?></div>
</div>


