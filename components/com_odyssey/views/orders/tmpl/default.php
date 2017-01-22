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
JHtml::_('behavior.tabstate');


$user = JFactory::getUser();
$userId = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));

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

<?php
// Search tools bar 
echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
?>
  <br />
  <table class="table table-striped">
    <thead>
      <th width="15%">
	<?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_ORDER_NUMBER', 'o.order_nb', $listDirn, $listOrder); ?>
      </th>
      <th width="20%">
	<?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_TRAVEL', 'travel_name', $listDirn, $listOrder); ?>
      </th>
      <th width="10%">
	<?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_ORDER_STATUS', 'o.order_status', $listDirn, $listOrder); ?>
      </th>
      <th width="15%">
	<?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_PAYMENT_STATUS', 'o.payment_status', $listDirn, $listOrder); ?>
      </th>
      <th width="10%">
	<?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_OUTSTANDING_BALANCE', 'o.outstanding_balance', $listDirn, $listOrder); ?>
      </th>
      <th width="10%">
	<?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_AMOUNT', 'o.final_amount', $listDirn, $listOrder); ?>
      </th>
      <th width="15%">
	<?php echo JHtml::_('searchtools.sort', 'JDATE', 'o.created', $listDirn, $listOrder); ?>
      </th>
      <th width="1%">
	<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'o.id', $listDirn, $listOrder); ?>
      </th>
    </thead>

    <tbody>
    <?php foreach ($this->items as $i => $item) : ?>

    <tr class="row-<?php echo $i % 2; ?>"><td>
	    <?php if ($item->checked_out) : ?>
		<?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'travels.', 0); ?>
	    <?php endif; ?>
	    <a href="index.php?option=com_odyssey&task=order.edit&o_id=<?php echo $item->id; ?>">
		    <?php echo $this->escape($item->order_nb); ?></a>
            </td>
	    <td>
	      <?php echo $this->escape($item->travel_name); ?>
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
	      <?php echo JHTML::_('date',$item->created, JText::_('DATE_FORMAT_LC3')); ?>
	    </td>
	    <td class="center">
	      <?php echo (int) $item->id; ?>
	    </td></tr>

    <?php endforeach; ?>
    <tr>
	<td colspan="8"><?php echo $this->pagination->getListFooter(); ?></td>
    </tr>
    </tbody>
  </table>

<input type="hidden" name="option" value="com_odyssey" />
<input type="hidden" name="task" value="" />
<?php echo JHtml::_('form.token'); ?>
</form>

