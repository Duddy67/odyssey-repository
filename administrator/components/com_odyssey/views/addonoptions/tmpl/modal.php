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
$function = $jinput->get('function', 'jQuery.selectItem', 'str');
//Get the modal option if any.
$modalOption = $jinput->get('modal_option', '', 'str');
if(!empty($modalOption)) {
  $modalOption = '&modal_option='.$modalOption;
}

//Called from a dynamical item.
$idNb = $jinput->get('id_nb', 0, 'int');
$type = $jinput->get('type', 'addon', 'str');

$statuses = array(0 => 'JUNPUBLISHED', 1 => 'JPUBLISHED', 2 => 'JARCHIVED', -2 => 'JTRASHED');

$addonTypes = array('excursion' => 'COM_ODYSSEY_OPTION_EXCURSION',
		    'hosting' => 'COM_ODYSSEY_OPTION_HOSTING',
		    'insurance' => 'COM_ODYSSEY_OPTION_INSURANCE',
		    'vehicle' => 'COM_ODYSSEY_OPTION_VEHICLE');

//Create both the addon type and group options to pass as argument to the select.options function.
$addonTypeOptions = $typeOptions = array();
foreach($addonTypes as $value => $text) {
  $addonTypeOptions[] = JHtml::_('select.option', $value, JText::_($text));
}

$typeOptions[] = JHtml::_('select.option', 'single_sel', JText::_('COM_ODYSSEY_OPTION_SINGLE_SELECT'));
$typeOptions[] = JHtml::_('select.option', 'multi_sel', JText::_('COM_ODYSSEY_OPTION_MULTI_SELECT'));
?>

<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=addonoptions&layout=modal&tmpl=component'.$modalOption.'&function='.$function.'&id_nb='.$idNb);?>" method="post" name="adminForm" id="adminForm">

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
      <select name="filter_published" class="input-large" onchange="this.form.submit()">
	<option value=""><?php echo JText::_('JOPTION_SELECT_PUBLISHED');?></option>
	<?php echo JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true);?>
      </select>

      <select name="filter_option_type" id="filter_option_type" class="input-large" onchange="this.form.submit()">
	<option value=""><?php echo JText::_('COM_ODYSSEY_OPTION_SELECT_OPTION_TYPE');?></option>
	<?php echo JHtml::_('select.options', $typeOptions, 'value', 'text', $this->state->get('filter.option_type'), true);?>
      </select>

      <select name="filter_addon_type" class="input-large" onchange="this.form.submit()">
	<option value=""><?php echo JText::_('COM_ODYSSEY_OPTION_SELECT_ADDON_TYPE');?></option>
	<?php echo JHtml::_('select.options', $addonTypeOptions, 'value', 'text', $this->state->get('filter.addon_type'), true);?>
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
	    <?php echo JHtml::_('grid.sort', 'COM_ODYSSEY_HEADING_NAME', 'ao.name', $listDirn, $listOrder); ?>
	  </th>
	  <th width="10%">
	    <?php echo JHtml::_('grid.sort', 'COM_ODYSSEY_HEADING_OPTION_TYPE', 'a.option_type', $listDirn, $listOrder); ?>
	  </th>
	  <th width="10%">
	    <?php echo JHtml::_('grid.sort', 'JSTATUS', 'ao.published', $listDirn, $listOrder); ?>
	  </th>
	  <th>
	    <?php echo JHtml::_('grid.sort', 'COM_ODYSSEY_HEADING_PARENT_ADDON', 'parent_addon', $listDirn, $listOrder); ?>
	  </th>
	  <th width="10%" class="center nowrap">
	    <?php echo JHtml::_('grid.sort', 'COM_ODYSSEY_HEADING_ADDON_TYPE', 'a.addon_type', $listDirn, $listOrder); ?>
	  </th>
	  <th width="1%" class="center nowrap">
	    <?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'ao.id', $listDirn, $listOrder); ?>
	  </th>
	</tr>
      </thead>
      <tfoot>
	<tr>
	  <td colspan="6">
	    <?php echo $this->pagination->getListFooter(); ?>
	  </td>
	</tr>
      </tfoot>

      <tbody>
      <?php foreach ($this->items as $i => $item) : ?>
      <tr class="row<?php echo $i % 2; ?>">
	<td>
	  <div class="pull-left">
	    <a class="pointer" style="color:#025a8d;" onclick="if(window.parent) window.parent.<?php echo $this->escape($function);?>(<?php echo $item->id; ?>, '<?php echo $this->escape(addslashes($item->name).' ('.addslashes($item->parent_addon).')'); ?>', '<?php echo $idNb; ?>', '<?php echo $type; ?>');" ><?php echo $this->escape($item->name); ?></a>
	  </div>
	</td>
	<td>
	  <?php
		echo JText::_('COM_ODYSSEY_OPTION_SINGLE_SELECT');
		if($item->option_type == 'multi_sel') {
		  echo JText::_('COM_ODYSSEY_OPTION_MULTI_SELECT');
		}
	  ?>
	</td>
	<td class="small hidden-phone">
	  <?php echo $this->escape(JText::_($statuses[$item->published])); ?>
	</td>
	<td>
	  <?php echo $this->escape($item->parent_addon); ?>
	</td>
	<td>
	  <?php echo JText::_($addonTypes[$item->addon_type]); ?>
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

