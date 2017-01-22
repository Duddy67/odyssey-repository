<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access.
defined('_JEXEC') or die;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');

// Load the tooltip behavior.
JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');

$jinput = JFactory::getApplication()->input;
$idNb = $jinput->get->get('id_nb', 0, 'uint');
$type = $jinput->get->get('type', '', 'string');
$function = $jinput->get('function', 'jQuery.selectItem');

$user = JFactory::getUser();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
?>

<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=groups&layout=modal&tmpl=component&id_nb='.$idNb.'&type='.$type.'&function='.$function);?>" method="post" name="adminForm" id="adminForm">
	<fieldset class="filter clearfix">
	  <div class="btn-toolbar">
	    <div class="btn-group pull-left">
		    <label for="filter_search">
			    <?php echo JText::_('JSEARCH_FILTER_LABEL'); ?>
		    </label>
	    </div>
	    <div class="btn-group pull-left">
		    <input type="text" name="filter_search" id="filter_search"
		    placeholder="<?php echo JText::_('COM_ODYSSEY_ITEMS_SEARCH_FILTER'); ?>"
		    value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
		    size="30" title="<?php echo JText::_('COM_ODYSSEY_ITEMS_SEARCH_FILTER'); ?>" />
	    </div>
	    <div class="btn-group pull-left">
		    <button type="submit" class="btn hasTooltip" data-placement="bottom" title="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>">
			    <span class="icon-search"></span><?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?></button>
		    <button type="button" class="btn hasTooltip" data-placement="bottom" title="<?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?>" onclick="document.getElementById('filter_search').value='';this.form.submit();">
			    <span class="icon-remove"></span><?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?></button>
	    </div>
	    <div class="clearfix"></div>
	  </div>
	</fieldset>

	<table class="table table-striped">
		<thead>
			<tr>
				<th class="left">
					<?php echo JText::_('COM_ODYSSEY_HEADING_GROUP_TITLE'); ?>
				</th>
				<th width="10%">
					<?php echo JText::_('COM_ODYSSEY_HEADING_USERS_IN_GROUP'); ?>
				</th>
				<th width="5%">
					<?php echo JText::_('JGRID_HEADING_ID'); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="4">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
		<?php foreach ($this->items as $i => $item) :
			$canCreate	= $user->authorise('core.create',		'com_users');
			$canEdit	= $user->authorise('core.edit',			'com_users');
			// If this group is super admin and this user is not super admin, $canEdit is false
			if (!$user->authorise('core.admin') && (JAccess::checkGroup($item->id, 'core.admin'))) {
				$canEdit = false;
			}
			$canChange	= $user->authorise('core.edit.state',	'com_users');
		?>
			<tr class="row<?php echo $i % 2; ?>">
				<td>
					<?php echo str_repeat('<span class="gi">&mdash;</span>', $item->level) ?>

	    <a class="pointer" onclick="if(window.parent) window.parent.<?php echo $this->escape($function);?>('<?php echo $item->id; ?>', '<?php echo $this->escape(addslashes($item->title)); ?>', '<?php echo $this->escape($idNb); ?>', 'recipient');"><?php echo $this->escape($item->title); ?></a>
				</td>
				<td class="center">
					<?php echo $item->user_count ? $item->user_count : ''; ?>
				</td>
				<td class="center">
					<?php echo (int) $item->id; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<div>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>

