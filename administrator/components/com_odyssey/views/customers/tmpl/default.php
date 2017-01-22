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
$canOrder = $user->authorise('core.edit.state', 'com_odyssey.category');
?>

<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=customers');?>" method="post" name="adminForm" id="adminForm">

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
    <table class="table table-striped" id="customerList">
      <thead>
      <tr>
	<th width="1%">
	<input type="checkbox" name="checkall-toggle" value="" onclick="checkAll(this)" />
	</th>
	<th>
	  <?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_FIELD_LASTNAME_LABEL', 'u.name', $listDirn, $listOrder); ?>
	</th>
	<th>
	  <?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_FIELD_FIRSTNAME_LABEL', 'c.firstname', $listDirn, $listOrder); ?>
	</th>
	<th class="nowrap" width="10%">
		<?php echo JHtml::_('searchtools.sort', 'JGLOBAL_USERNAME', 'u.username', $listDirn, $listOrder); ?>
	</th>
	<th class="nowrap" width="15%">
		<?php echo JHtml::_('searchtools.sort', 'JGLOBAL_EMAIL', 'u.email', $listDirn, $listOrder); ?>
	</th>
	<th width="10%">
	  <?php echo JText::_('COM_ODYSSEY_HEADING_GROUPS'); ?>
	</th>
	<th class="nowrap" width="10%">
		<?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_LAST_VISIT_DATE', 'u.lastvisitDate', $listDirn, $listOrder); ?>
	</th>
	<th class="nowrap" width="10%">
		<?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_REGISTRATION_DATE', 'u.registerDate', $listDirn, $listOrder); ?>
	</th>
	<th class="nowrap" width="3%">
		<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'userid', $listDirn, $listOrder); ?>
	</th>
      </tr>
      </thead>

      <tbody>
      <?php foreach ($this->items as $i => $item) :
      //$canCreate = $user->authorise('core.create', 'com_odyssey.category.'.$item->catid);
      $canEdit= $user->authorise('core.edit','com_odyssey.customer.'.$item->id);
      $canCheckin= $user->authorise('core.manage','com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
      $canChange = $user->authorise('core.edit.state','com_odyssey.customer.'.$item->id) && $canCheckin;

      ?>
      <tr class="row<?php echo $i % 2; ?>">
	      <td class="center">
		      <?php echo JHtml::_('grid.id', $i, $item->id); ?>
	      </td><td>
	    <?php if ($item->checked_out) : ?>
	      <?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'customers.', $canCheckin); ?>
	    <?php endif; ?>

	    <?php if($canEdit || $canEditOwn) : ?>
	      <a href="<?php echo JRoute::_('index.php?option=com_odyssey&task=customer.edit&id='.$item->id);?>">
		      <?php echo $this->escape($item->name); ?></a>
	    <?php else : ?>
	      <?php echo $this->escape($item->name); ?>
	    <?php endif; ?>
	      </td>
	      <td>
		<?php echo $this->escape($item->firstname); ?>
	      </td>
	      <td>
		<?php echo $this->escape($item->username); ?>
	      </td>
	      <td>
		<?php echo $this->escape($item->email); ?>
	      </td>
	      <td class="center">
		  <?php if(count($item->groups) > 2) : ?>
		    <?php echo JText::_('COM_ODYSSEY_USERS_MULTIPLE_GROUPS');?></p>
		  <?php else : ?>
		      <?php for($j = 0; $j < count($item->groups); $j++) : //Display group titles. ?>
			<?php echo $this->escape($item->groups[$j]); ?>
			    <?php if($j + 1 < count($item->groups)) : //Separate group titles with coma. ?>
			      <?php echo ', '; ?>
			    <?php endif; ?>
		      <?php endfor; ?>
		  <?php endif; ?>
	      </td>
	      <td>
		<?php if ($item->lastvisitDate != '0000-00-00 00:00:00'):?>
			<?php echo JHtml::_('date', $item->lastvisitDate, JText::_('DATE_FORMAT_LC3')); ?>
		<?php else:?>
			<?php echo JText::_('JNEVER'); ?>
		<?php endif;?>
	      </td>
	      <td>
		<?php echo JHTML::_('date',$item->registerDate, JText::_('DATE_FORMAT_LC3')); ?>
	      </td>
	      <td class="center">
		<?php echo (int) $item->userid; ?>
	      </td></tr>

      <?php endforeach; ?>
      <tr>
	  <td colspan="9"><?php echo $this->pagination->getListFooter(); ?></td>
      </tr>
      </tbody>
    </table>
  <?php endif; ?>

<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="option" value="com_odyssey" />
<input type="hidden" name="task" value="" />
<?php echo JHtml::_('form.token'); ?>
</form>

