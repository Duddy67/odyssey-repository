<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
JHtml::_('behavior.framework');

// Check for at least one editable article
$isEditable = false;

if(!empty($this->items)) {
  foreach($this->items as $item) {
    if($item->params->get('access-edit')) {
      $isEditable = true;
      break;
    }
  }
}
?>

<table class="table category table-striped">
  <thead>
  <tr>
    <th id="categorylist_header_title">
      <?php echo JText::_('COM_ODYSSEY_FIELD_NAME_LABEL'); ?>
    </th>
    <?php if($this->params->get('list_show_author')) : ?>
      <th width="25%">
	<?php echo JText::_('COM_ODYSSEY_HEADING_CREATE_BY'); ?>
      </th>
    <?php endif; ?>
    <?php if($this->params->get('list_show_date')) : ?>
      <th width="15%" id="categorylist_header_date">
	<?php $date = $this->params->get('order_date'); ?>
	<?php echo JText::_('COM_ODYSSEY_'.strtoupper($date).'_DATE'); ?>
      </th>
    <?php endif; ?>
  </tr>
  </thead>

  <tbody>

    <?php foreach($this->items as $i => $item) : ?>
      <tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->catid?>">

	<td>
	<?php  //Build the link to the login page for the user to login or register.
	      if(!$item->params->get('access-view')) : 
		$menu = JFactory::getApplication()->getMenu();
		$active = $menu->getActive();
		$itemId = $active->id;
		$link1 = JRoute::_('index.php?option=com_users&view=login&Itemid='.$itemId);
		$returnURL = JRoute::_(OdysseyHelperRoute::getTravelRoute($item->slug, $item->catid));
		$link = new JUri($link1);
		$link->setVar('return', base64_encode($returnURL));
	      endif; ?>

	<?php if($item->params->get('access-view')) : //Set the link to the travel page.
	      $link = JRoute::_(OdysseyHelperRoute::getTravelRoute($item->slug, $item->catid));
	  endif; ?>

	  <a href="<?php echo $link;?>"><?php echo $this->escape($item->name); ?></a>

	  </td>
	  <?php if($this->params->get('list_show_author')) : ?>
	    <td>
	      <?php echo $this->escape($item->author); ?>
	    </td>
	  <?php endif; ?>
	  <?php if($this->params->get('list_show_date')) : ?>
	    <td>
	      <?php if($date == 'modified' && $item->displayDate == '0000-00-00 00:00:00') : ?>
		<?php echo JText::_('COM_ODYSSEY_UNMODIFIED'); ?>
	      <?php else : ?>
		<?php echo JHtml::_('date', $item->displayDate, $this->escape($this->params->get('date_format', JText::_('DATE_FORMAT_LC4')))); ?>
	      <?php endif; ?>
	    </td>
	  <?php endif; ?>
	  </tr>
    <?php endforeach; ?>
    </table>

