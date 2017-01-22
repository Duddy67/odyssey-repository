<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');


$user = JFactory::getUser();
$userId = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
$archived = $this->state->get('filter.published') == 2 ? true : false;
$trashed = $this->state->get('filter.published') == -2 ? true : false;
//Check only against component permission as order items have no categories.
$canOrder = $user->authorise('core.edit.state', 'com_odyssey');
//
$finalizeTimeLimit = JComponentHelper::getParams('com_odyssey')->get('finalize_time_limit');

//Build a status array.
$status = array();
$status['completed'] = 'COM_ODYSSEY_OPTION_COMPLETED_STATUS';
$status['pending'] = 'COM_ODYSSEY_OPTION_PENDING_STATUS';
$status['other'] = 'COM_ODYSSEY_OPTION_OTHER_STATUS';
$status['cancelled'] = 'COM_ODYSSEY_OPTION_CANCELLED_STATUS';
$status['error'] = 'COM_ODYSSEY_OPTION_ERROR_STATUS';
$status['unfinished'] = 'COM_ODYSSEY_OPTION_UNFINISHED_STATUS';
$status['deposit'] = 'COM_ODYSSEY_OPTION_DEPOSIT_STATUS';
?>


<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=orders');?>" method="post" name="adminForm" id="adminForm">

<?php if (!empty( $this->sidebar)) : ?>
  <div id="j-sidebar-container" class="span2">
	  <?php echo $this->sidebar; ?>
  </div>
  <div id="j-main-container" class="span10">
<?php else : ?>
  <div id="j-main-container">
<?php endif;?>

<?php
// Search tools bar 
echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
?>

  <div class="clr"> </div>
  <?php if (empty($this->items)) : ?>
	<div class="alert alert-no-items">
		<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
	</div>
  <?php else : ?>
    <table class="table table-striped" id="orderList">
      <thead>
	<tr>
	<th width="1%" class="hidden-phone">
	  <?php echo JHtml::_('grid.checkall'); ?>
	</th>
	<th width="1%" style="min-width:55px" class="nowrap center">
	  <?php echo JHtml::_('searchtools.sort', 'JSTATUS', 'o.published', $listDirn, $listOrder); ?>
	</th>
	<th width="10%">
	  <?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_ORDER_NUMBER', 'o.order_nb', $listDirn, $listOrder); ?>
	</th>
	<th>
	  <?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_TRAVEL', 'o.travel_name', $listDirn, $listOrder); ?>
	</th>
	<th width="10%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_CUSTOMER', 'lastname', $listDirn, $listOrder); ?>
	</th>
	<th width="10%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_ORDER_STATUS', 'o.order_status', $listDirn, $listOrder); ?>
	</th>
	<th width="10%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_PAYMENT_STATUS', 'o.payment_status', $listDirn, $listOrder); ?>
	</th>
	<th width="10%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_OUTSTANDING_BALANCE', 'o.outstanding_balance', $listDirn, $listOrder); ?>
	</th>
	<th width="10%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_AMOUNT', 'o.final_amount', $listDirn, $listOrder); ?>
	</th>
	<th width="5%" class="nowrap hidden-phone">
	  <?php echo JText::_('COM_ODYSSEY_HEADING_STATE'); ?>
	</th>
	<th width="5%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_DEPARTURE_DATE', 'o.departure_date', $listDirn, $listOrder); ?>
	</th>
	<th width="1%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'o.id', $listDirn, $listOrder); ?>
	</th>
      </tr>
      </thead>

      <tbody>
      <?php foreach ($this->items as $i => $item) :

      $canEdit = $user->authorise('core.edit','com_odyssey.order.'.$item->id);
      $canEditOwn = $user->authorise('core.edit.own', 'com_odyssey.order.'.$item->id) && $item->customer_id== $userId;
      $canCheckin = $user->authorise('core.manage','com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
      $canChange = ($user->authorise('core.edit.state','com_odyssey.order.'.$item->id) && $canCheckin) || $canEditOwn; 
      ?>

      <tr class="row<?php echo $i % 2; ?>">
	  <td class="center hidden-phone">
		  <?php echo JHtml::_('grid.id', $i, $item->id); ?>
	  </td>
	  <td class="center">
	    <div class="btn-group">
	      <?php echo JHtml::_('jgrid.published', $item->published, $i, 'orders.', $canChange, 'cb'); ?>
	      <?php
	      // Create dropdown items
	      $action = $archived ? 'unarchive' : 'archive';
	      JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'orders');

	      $action = $trashed ? 'untrash' : 'trash';
	      JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'orders');

	      // Render dropdown list
	      echo JHtml::_('actionsdropdown.render', $this->escape($item->order_nb));
	      ?>
	    </div>
	  </td>
	  <td class="has-context">
	    <div class="pull-left">
	      <?php if ($item->checked_out) : ?>
		  <?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'orders.', $canCheckin); ?>
	      <?php endif; ?>
	      <?php if($canEdit || $canEditOwn) : ?>
		<a href="<?php echo JRoute::_('index.php?option=com_odyssey&task=order.edit&id='.$item->id);?>" title="<?php echo JText::_('JACTION_EDIT'); ?>">
			<?php echo $this->escape($item->order_nb); ?></a>
	      <?php else : ?>
		<?php echo $this->escape($item->order_nb); ?>
	      <?php endif; ?>
	    </div>
	  </td>
	  <td>
	    <?php echo $this->escape($item->travel_name); ?>
	  </td>
	  <td class="small hidden-phone">
	    <?php echo $this->escape($item->lastname).' '.$this->escape($item->firstname); ?>
	  </td>
	  <td>
	    <?php echo JText::_($status[$item->order_status]); ?>
	  </td>
	  <td>
	    <?php echo JText::_($status[$item->payment_status]); ?>
	  </td>
	  <td>
	    <?php
		  echo UtilityHelper::formatNumber($item->outstanding_balance, $item->digits_precision);
		  echo ' '.$this->escape($item->currency);
	      ?>
	  </td>
	  <td>
	    <?php
		  echo UtilityHelper::formatNumber($item->final_amount, $item->digits_precision);
		  echo ' '.$this->escape($item->currency);
	      ?>
	  </td>
	  <td>
	    <?php
	          //Get the limit date after which the order state has to be displayed.
	          $limitDate = UtilityHelper::getLimitDate($finalizeTimeLimit, $item->departure_date, false);
	          if($limitDate < $this->nowDate) {
		    if($item->payment_status != 'completed') {
		      $src = '../media/com_odyssey/images/alert.jpg';
		      echo '<img src="'.$src.'" width="24" height="24" alt="alert" />';
		    }
		    elseif($item->payment_status == 'completed' && $item->order_status != 'completed') {
		      $src = '../media/com_odyssey/images/warning.jpg';
		      echo '<img src="'.$src.'" width="24" height="24" alt="warning" />';
		    }
		    elseif($item->payment_status == 'completed' && $item->order_status == 'completed') {
		      $src = '../media/com_odyssey/images/completed.jpg';
		      echo '<img src="'.$src.'" width="24" height="24" alt="completed" />';
		    }
		  }
		  else {
		    if($item->payment_status == 'completed' && $item->order_status == 'completed') {
		      $src = '../media/com_odyssey/images/completed.jpg';
		      echo '<img src="'.$src.'" width="24" height="24" alt="completed" />';
		    }
		    else {
		      $src = '../media/com_odyssey/images/pending.jpg';
		      echo '<img src="'.$src.'" width="24" height="24" alt="pending" />';
		    }
		  }
	      ?>
	  </td>
	  <td class="nowrap small hidden-phone">
	    <?php echo JHtml::_('date', $item->departure_date, JText::_('COM_ODYSSEY_DATE_FORMAT_PERIOD')); ?>
	  </td>
	  <td>
	    <?php echo $item->id; ?>
	  </td></tr>

      <?php endforeach; ?>
      <tr>
	  <td colspan="12"><?php echo $this->pagination->getListFooter(); ?></td>
      </tr>
      </tbody>
    </table>
  <?php endif; ?>

<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="option" value="com_odyssey" />
<input type="hidden" name="task" value="" />
<?php echo JHtml::_('form.token'); ?>
</form>

