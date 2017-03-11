<?php
/**
 * @package Odyssey 
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.tooltip');
//JHtml::_('script','system/multiselect.js',false,true);
JHtml::_('formbehavior.chosen', 'select');


$user = JFactory::getUser();
$userId = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));

$jinput = JFactory::getApplication()->input;
//Set the Javascript function to call.  
$function = $jinput->get('function', 'selectItem', 'str');
//Get the modal option if any.
$modalOption = $jinput->get('modal_option', '', 'str');
if(!empty($modalOption)) {
  $modalOption = '&modal_option='.$modalOption;
}

//Called from a dynamical item.
$idNb = $jinput->get->get('id_nb', 0, 'int');
$itemType = $jinput->get->get('item_type', '', 'string');

$statuses = array(0 => 'JUNPUBLISHED', 1 => 'JPUBLISHED', 2 => 'JARCHIVED', -2 => 'JTRASHED');
?>

<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=steps&layout=modal&tmpl=component'.$modalOption.'&function='.$function);?>" method="post" name="adminForm" id="adminForm">

  <fieldset class="filter clearfix">
    <div class="btn-toolbar">
      <div class="btn-group pull-left">
	      <label for="filter_search">
		      <?php echo JText::_('JSEARCH_FILTER_LABEL'); ?>
	      </label>
      </div>
      <div class="btn-group pull-left input-append">
	      <input type="text" name="filter_search" id="filter_search" value="<?php echo
	      $this->escape($this->state->get('filter.search')); ?>" size="30"
	      title="<?php echo JText::_('COM_ODYSSEY_SEARCH_IN_TITLE'); ?>" />
	      <button type="submit" class="btn hasTooltip" title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_SUBMIT'); ?>" data-placement="bottom">
		      <span class="icon-search"></span><?php echo '&#160;' . JText::_('JSEARCH_FILTER_SUBMIT'); ?></button>
	      <button type="button" class="btn hasTooltip" title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_CLEAR'); ?>" data-placement="bottom" onclick="document.id('filter_search').value='';this.form.submit();">
		      <span class="icon-remove"></span><?php echo '&#160;' . JText::_('JSEARCH_FILTER_CLEAR'); ?></button>
      </div>
	<div class="clearfix"></div>
    </div>
    <hr class="hr-condensed" />
    <div class="filters">
      <select name="filter_access" class="input-medium" onchange="this.form.submit()">
	<option value=""><?php echo JText::_('JOPTION_SELECT_ACCESS');?></option>
	<?php echo JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text', $this->state->get('filter.access'));?>
      </select>

      <select name="filter_published" class="input-medium" onchange="this.form.submit()">
	<option value=""><?php echo JText::_('JOPTION_SELECT_PUBLISHED');?></option>
	<?php echo JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true);?>
      </select>

      <select name="filter_category_id" class="input-medium" onchange="this.form.submit()">
	<option value=""><?php echo JText::_('JOPTION_SELECT_CATEGORY');?></option>
	<?php echo JHtml::_('select.options', JHtml::_('category.options', 'com_odyssey'), 'value', 'text', $this->state->get('filter.category_id'));?>
      </select>
    </div>
  </fieldset>

  <?php if (empty($this->items)) : ?>
	<div class="alert alert-no-items">
		<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
	</div>
  <?php else : ?>
    <table class="table table-striped table-condensed">
      <thead>
	<tr>
	  <th class="title">
	    <?php echo JHtml::_('grid.sort', 'COM_ODYSSEY_HEADING_NAME', 's.name', $listDirn, $listOrder); ?>
	  </th>
	  <th width="15%">
	    <?php echo JHtml::_('grid.sort', 'JSTATUS', 's.published', $listDirn, $listOrder); ?>
	  </th>
	  <th width="15%">
	    <?php echo JHtml::_('grid.sort', 'JGRID_HEADING_CREATED_BY', 'user', $listDirn, $listOrder); ?>
	  </th>
	  <th width="15%" class="center nowrap">
	    <?php echo JHtml::_('grid.sort', 'JDATE', 's.created', $listDirn, $listOrder); ?>
	  </th>
	  <th width="1%" class="center nowrap">
	    <?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 's.id', $listDirn, $listOrder); ?>
	  </th>
	</tr>
      </thead>
      <tfoot>
	<tr>
	  <td colspan="5">
	    <?php echo $this->pagination->getListFooter(); ?>
	  </td>
	</tr>
      </tfoot>

      <tbody>
      <?php foreach ($this->items as $i => $item) : ?>
      <tr class="row<?php echo $i % 2; ?>">
	<td>
	  <div class="pull-left">
	  <?php if($idNb && !empty($itemType)) : //Called from a dynamical item.  ?>
	    <a class="pointer" style="color:#025a8d;" onclick="if(window.parent) window.parent.<?php echo $this->escape('jQuery.'.$function);?>(<?php echo $item->id; ?>, '<?php echo $this->escape(addslashes($item->name)); ?>', '<?php echo $idNb; ?>', '<?php echo $this->escape($itemType); ?>');" ><?php echo $this->escape($item->name); ?></a>
	  <?php else : ?>
	    <a class="pointer" style="color:#025a8d;" onclick="if(window.parent) window.parent.<?php echo $this->escape($function);?>(<?php echo $item->id; ?>, '<?php echo $this->escape(addslashes($item->name)).' - ('.$this->escape(addslashes($item->group_alias)).')'; ?>');" >
		<?php echo $this->escape($item->name); ?></a>
	  <?php endif; ?>
	    <span class="small break-word">
	      <?php echo JText::sprintf('COM_ODYSSEY_LIST_GROUP_ALIAS', $this->escape($item->group_alias)); ?>
	    </span>
	    <div class="small">
	      <?php echo JText::_('JCATEGORY').': '.$this->escape($item->category_title); ?>
	    </div>
	  </div>
	</td>
	<td>
	  <?php echo $this->escape(JText::_($statuses[$item->published])); ?>
	</td>
	<td>
	  <?php echo $this->escape($item->user); ?>
	</td>
	<td>
	  <?php echo JHTML::_('date',$item->created, JText::_('DATE_FORMAT_LC4')); ?>
	</td>
	<td class="center">
	  <?php echo (int) $item->id; ?>
	</td></tr>

      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <input type="hidden" name="boxchecked" value="0" />
  <input type="hidden" name="task" value="" />
  <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
  <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
  <?php echo JHtml::_('form.token'); ?>
</form>

