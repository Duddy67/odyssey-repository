<?php
/**
 * @package Odyssey 
 * @copyright Copyright (c) 2016 Lucas Sanner
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
$colSpan = 4;

$statuses = array(0 => 'JUNPUBLISHED', 1 => 'JPUBLISHED', 2 => 'JARCHIVED', -2 => 'JTRASHED');

$addonTypes = array('excursion' => 'COM_ODYSSEY_OPTION_EXCURSION',
		    'hosting' => 'COM_ODYSSEY_OPTION_HOSTING',
		    'insurance' => 'COM_ODYSSEY_OPTION_INSURANCE',
		    'vehicle' => 'COM_ODYSSEY_OPTION_VEHICLE',
		    'addon_option' => 'COM_ODYSSEY_OPTION_ADDON_OPTION');

if($modalOption != '&modal_option=option_only') {
  //Create both the addon type and group options to pass as argument to the select.options function.
  $addonTypeOptions = $groupOptions = array();
  foreach($addonTypes as $value => $text) {
    $addonTypeOptions[] = JHtml::_('select.option', $value, JText::_($text));
  }

  $groupOptions[] = JHtml::_('select.option', 'none', JText::_('COM_ODYSSEY_OPTION_NONE'));
  for($i = 1; $i < 21; $i++) {
    $groupOptions[] = JHtml::_('select.option', $i.':no_sel', JText::sprintf('COM_ODYSSEY_OPTION_GROUP_NO_SEL', $i));
    $groupOptions[] = JHtml::_('select.option', $i.':single_sel', JText::sprintf('COM_ODYSSEY_OPTION_GROUP_SINGLE_SEL', $i));
    $groupOptions[] = JHtml::_('select.option', $i.':multi_sel', JText::sprintf('COM_ODYSSEY_OPTION_GROUP_MULTI_SEL', $i));
  }

  $colSpan = 6;
}
?>

<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=addons&layout=modal&tmpl=component'.$modalOption.'&function='.$function.'&id_nb='.$idNb);?>" method="post" name="adminForm" id="adminForm">

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

      <?php if($modalOption != '&modal_option=option_only') : //Don't need filters for addon options. ?>
	<select name="filter_addon_type" class="input-large" onchange="this.form.submit()">
	  <option value=""><?php echo JText::_('COM_ODYSSEY_OPTION_SELECT_ADDON_TYPE');?></option>
	  <?php echo JHtml::_('select.options', $addonTypeOptions, 'value', 'text', $this->state->get('filter.addon_type'), true);?>
	</select>

	<select name="filter_group_nb" id="filter_group_nb" class="input-large" onchange="this.form.submit()">
	  <option value=""><?php echo JText::_('COM_ODYSSEY_OPTION_SELECT_GROUP_NB');?></option>
	  <?php echo JHtml::_('select.options', $groupOptions, 'value', 'text', $this->state->get('filter.group_nb'), true);?>
	</select>
      <?php endif; ?>
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
	    <?php echo JHtml::_('grid.sort', 'COM_ODYSSEY_HEADING_NAME', 'a.name', $listDirn, $listOrder); ?>
	  </th>
	  <?php if($modalOption != '&modal_option=option_only') : //Don't need these infos for addon options. ?>
	    <th width="10%">
	      <?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_ADDON_TYPE', 'a.addon_type', $listDirn, $listOrder); ?>
	    </th>
	    <th width="15%">
	      <?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_GROUP_NUMBER', 'a.group_nb', $listDirn, $listOrder); ?>
	    </th>
	  <?php endif; ?>
	  <th width="10%">
	    <?php echo JHtml::_('grid.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
	  </th>
	  <th width="10%" class="center nowrap">
	    <?php echo JHtml::_('grid.sort', 'JDATE', 'a.created', $listDirn, $listOrder); ?>
	  </th>
	  <th width="1%" class="center nowrap">
	    <?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
	  </th>
	</tr>
      </thead>
      <tfoot>
	<tr>
	  <td colspan="<?php echo $colSpan; ?>">
	    <?php echo $this->pagination->getListFooter(); ?>
	  </td>
	</tr>
      </tfoot>

      <tbody>
      <?php foreach ($this->items as $i => $item) : ?>
      <tr class="row<?php echo $i % 2; ?>">
	<td>
	  <div class="pull-left">
	    <a class="pointer" style="color:#025a8d;" onclick="if(window.parent) window.parent.<?php echo $this->escape($function);?>(<?php echo $item->id; ?>, '<?php echo $this->escape(addslashes($item->name)); ?>', '<?php echo $idNb; ?>', '<?php echo $type; ?>');" ><?php echo $this->escape($item->name); ?></a>
	  </div>
	</td>
	<?php if($modalOption != '&modal_option=option_only') : //Don't need these infos for addon options. ?>
	  <td>
	    <?php echo JText::_($addonTypes[$item->addon_type]); ?>
	  </td>
	  <td class="small hidden-phone">
	    <?php
		  if($item->group_nb == 'none') {

		    $muted = '';
		    if($item->addon_type == 'addon_option') {
		      $muted = 'class="muted"';
		    }

		    echo '<span '.$muted.'>'.JText::_('COM_ODYSSEY_OPTION_NONE').'</span>';
		  }
		  else {
		    preg_match('#^([0-9]+):(no_sel|single_sel|multi_sel)$#', $item->group_nb, $matches);
		    $nb = $matches[1];
		    $option = $matches[2];
		    echo JText::sprintf('COM_ODYSSEY_OPTION_GROUP_'.strtoupper($option), $nb);
		  }
	    ?>
	  </td>
	<?php endif; ?>
	<td>
	  <?php echo $this->escape(JText::_($statuses[$item->published])); ?>
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

