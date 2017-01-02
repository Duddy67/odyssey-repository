<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
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
$canOrder = $user->authorise('core.edit.state', 'com_odyssey.category');
$nbCol = 7;

//Set the step sequence flag.
$stepSeqDisplayed = false;
if($this->state->get('step_sequence.displayed')) {
  $stepSeqDisplayed = true;
  $stepDuration = $this->state->get('step_sequence.step_duration');
  $groupColor = 'a';
  $finalStepIndex = count($this->items) - 1;
  $nbCol = 8;
  //var_dump($stepDuration);
}
?>

<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=steps');?>" method="post" name="adminForm" id="adminForm">

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
//
echo JLayoutHelper::render('list.sequencetools', array('dpt_data' => $this->state->get('step_sequence.departure_data'),
                                                       'step_seq_displayed' => $stepSeqDisplayed), JPATH_COMPONENT.'/layouts/');
?>
  <div class="clr"> </div>
  <?php if (empty($this->items)) : ?>
	<div class="alert alert-no-items">
		<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
	</div>
  <?php else : ?>
    <table class="table table-striped" id="stepList">
      <thead>
	<tr>
	<th width="1%" class="hidden-phone">
	  <?php echo JHtml::_('grid.checkall'); ?>
	</th>
	<th width="1%" style="min-width:55px" class="nowrap center">
	  <?php echo JHtml::_('searchtools.sort', 'JSTATUS', 's.published', $listDirn, $listOrder); ?>
	</th>
	<th>
	  <?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_NAME', 's.name', $listDirn, $listOrder); ?>
	</th>
	<?php if($stepSeqDisplayed) : //Display the step sequence fields. ?>
	  <th width="10%">
	    <?php echo JText::_('COM_ODYSSEY_HEADING_TIME_GAP'); ?>
	  </th>
	  <th width="10%">
	    <?php echo JText::_('COM_ODYSSEY_HEADING_STEP_DURATION'); ?>
	  </th>
	<?php else : ?>
	  <th width="10%">
	    <?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_STEP_TYPE', 's.step_type', $listDirn, $listOrder); ?>
	  </th>
	<?php endif; ?>
	<th width="10%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_CREATED_BY', 'user', $listDirn, $listOrder); ?>
	</th>
	<th width="5%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort', 'JDATE', 's.created', $listDirn, $listOrder); ?>
	</th>
	<th width="1%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 's.id', $listDirn, $listOrder); ?>
	</th>
      </tr>
      </thead>

      <tbody>
      <?php foreach ($this->items as $i => $item) :

      $canCreate = $user->authorise('core.create', 'com_odyssey.category.'.$item->catid);
      $canEdit = $user->authorise('core.edit','com_odyssey.step.'.$item->id);
      $canEditOwn = $user->authorise('core.edit.own', 'com_odyssey.step.'.$item->id) && $item->created_by == $userId;
      $canCheckin = $user->authorise('core.manage','com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
      $canChange = ($user->authorise('core.edit.state','com_odyssey.step.'.$item->id) && $canCheckin) || $canEditOwn; 

      $muted = '';
      if($item->step_type != 'departure') {
	$muted = 'muted';
      }
      ?>

      <tr class="row<?php echo $i % 2; ?>">
	  <td class="center hidden-phone">
	    <?php
	        //Departure step must not be checked when step sequence is displayed.
		if(!$stepSeqDisplayed || ($stepSeqDisplayed && $i != 0)) { 
		  echo JHtml::_('grid.id', $i, $item->id);
		}
	    ?>
	  </td>
	  <td class="center">
	    <div class="btn-group">
	      <?php echo JHtml::_('jgrid.published', $item->published, $i, 'steps.', $canChange, 'cb'); ?>
	      <?php
	      // Create dropdown items
	      $action = $archived ? 'unarchive' : 'archive';
	      JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'steps');

	      $action = $trashed ? 'untrash' : 'trash';
	      JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'steps');

	      // Render dropdown list
	      echo JHtml::_('actionsdropdown.render', $this->escape($item->name));
	      ?>
	    </div>
	  </td>
	  <td class="has-context">
	    <div class="pull-left">
	      <?php if ($item->checked_out) : ?>
		  <?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'steps.', $canCheckin); ?>
	      <?php endif; ?>
	      <?php if($canEdit || $canEditOwn) : ?>
		<a href="<?php echo JRoute::_('index.php?option=com_odyssey&task=step.edit&id='.$item->id);?>" title="<?php echo JText::_('JACTION_EDIT'); ?>">
			<?php echo $this->escape($item->name); ?></a>
	      <?php else : ?>
		<?php echo $this->escape($item->name); ?>
	      <?php endif; ?>

	      <span class="small break-word">
		<?php echo JText::sprintf('COM_ODYSSEY_LIST_GROUP_ALIAS'.strtoupper($muted), $this->escape($item->group_alias)); ?>
	      </span>

	      <?php if (!empty($item->subtitle)) : ?>
		<div class="small break-word">
		  <?php echo JText::sprintf('COM_ODYSSEY_LIST_SUBTITLE', $this->escape($item->subtitle)); ?>
		</div>
	      <?php endif; ?>

	      <div class="small">
		<?php echo JText::_('JCATEGORY').': <span class="'.$muted.'">'.$this->escape($item->category_title).'</span>'; ?>
	      </div>
	    </div>
	  </td>
	  <?php if($stepSeqDisplayed) : //
	          //
		  if((int)$item->group_prev) {
		    $this->items[$i]->group_color = $this->items[$i - 1]->group_color;
		  }
		  else {
		    $this->items[$i]->group_color = $groupColor;
		    if($groupColor == 'a') {
		      $groupColor = 'b';
		    }
		    else {
		      $groupColor = 'a';
		    }
		  }
	      ?>
	    <td class="time-gap">
	      <?php
		  $days = '0';
		  $hours = $minutes = '00';
		  if($item->step_type != 'departure') {
		    $result = StepHelper::getDaysHoursMinutes($item->time_gap);
		    $days = $result['days'];
		    $hours = $result['hours'];
		    $minutes = $result['minutes'];
		  }
	      ?>
	      <span class="small break-word"><?php echo JText::sprintf('COM_ODYSSEY_TIME_GAP', $days, $hours.':'.$minutes); ?></span>
	      <span class="small break-word"><?php //echo JText::sprintf('COM_ODYSSEY_DAYS_HOURS_MINUTES', $days, $hours, $minutes); ?></span>
	    </td>
	    <td class="sequence-<?php echo $item->group_color; ?>">
	      <?php
		  $days = '0';
		  $hours = $minutes = '00';
		  if(!$item->group_prev && isset($stepDuration[$item->id])) {
		    $days = $stepDuration[$item->id]['days'];
		    $hours = $stepDuration[$item->id]['hours'];
		    $minutes = $stepDuration[$item->id]['minutes']; ?>
		    <span class="small break-word"><?php echo JText::sprintf('COM_ODYSSEY_DAYS_HOURS_MINUTES', $days, $hours, $minutes); ?></span>
	    <?php }
	          
	          if(!$item->group_prev && $i == $finalStepIndex) {
		    echo '<span class="small break-word">'.JText::sprintf('COM_ODYSSEY_MESSAGE_STEP_DURATION_UNVAILABLE').'</span>';
		  }
	      ?>
	      <span class="small break-word"><?php //echo JText::sprintf('COM_ODYSSEY_DAYS_HOURS_MINUTES', $days, $hours, $minutes); ?></span>
	    </td>
	  <?php else : ?>
	    <td>
	      <?php echo JText::_('COM_ODYSSEY_OPTION_'.strtoupper($item->step_type)); ?>
	    </td>
	  <?php endif; ?>
	  <td class="small hidden-phone">
	    <?php echo $this->escape($item->user); ?>
	  </td>
	  <td class="nowrap small hidden-phone">
	    <?php echo JHtml::_('date', $item->created, JText::_('DATE_FORMAT_LC4')); ?>
	  </td>
	  <td>
	    <?php echo $item->id; ?>
	  </td></tr>

      <?php endforeach; ?>
      <tr>
	  <td colspan="<?php echo $nbCol; ?>"><?php echo $this->pagination->getListFooter(); ?></td>
      </tr>
      </tbody>
    </table>
  <?php endif; ?>

<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="option" value="com_odyssey" />
<input type="hidden" name="task" value="" />
<?php echo JHtml::_('form.token'); ?>
</form>

