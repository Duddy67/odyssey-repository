<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('JPATH_BASE') or die;

JHtml::_('bootstrap.framework');

$canEdit = $displayData['item']->params->get('access-edit');
$item = $displayData['item'];
$user = $displayData['user'];
$uri = $displayData['uri'];
?>

<div class="icons">
  <?php if($canEdit) : ?>
    <div class="btn-group pull-right">
      <a class="btn dropdown-toggle" data-toggle="dropdown" href="#"><span class="icon-cog"></span><span class="caret"></span></a>
      <?php // Note the actions class is deprecated. Use dropdown-menu instead. ?>
      <ul class="dropdown-menu">
	<?php if($canEdit) :
	    //First check if the travel is checked out by a different user
	    if($item->checked_out > 0 && $item->checked_out != $user->id) :
	      $checkoutUser = JFactory::getUser($item->checked_out);
	      $date = JHtml::_('date', $item->checked_out_time);
	      $tooltip = JText::_('JLIB_HTML_CHECKED_OUT').' :: '.
			 JText::sprintf('COM_ODYSSEY_CHECKED_OUT_BY', $checkoutUser->name).' <br /> '.$date;
	  ?>
	      <li class="checked-out-icon">
		 <a href="#"><span class="hasTooltip icon-checkedout" title="<?php echo JHtml::tooltipText($tooltip.'', 0); ?>">
		   <?php echo JText::_('JLIB_HTML_CHECKED_OUT'); ?></span></a>
	      </li>
	   <?php else : 
	      //Build the edit link and display it. 
	      $url = 'index.php?option=com_odyssey&task=travel.edit&t_id='.$item->id.'&return='.base64_encode($uri);
	      ?>
	      <li class="edit-icon"><a href="<?php echo JRoute::_($url); ?>"><span class="icon-edit"></span>
				     <?php echo JText::_('COM_ODYSSEY_EDIT'); ?></a>
	      </li>
	  <?php endif; ?>
	<?php endif; ?>
      </ul>
    </div>
  <?php endif; ?>
</div>

