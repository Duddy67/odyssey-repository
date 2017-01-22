<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

//Create a shortcut for params and for the filter_ordering.
$params = $displayData->params;
$filterOrdering = $displayData->filter_ordering;

//Display some options according to the default layout settings.
$optionDate = $optionAuthor = $optionDownloads = true;
if($params->get('active_layout') == 'default') {
  //
  if(!$params->get('list_show_author')) {
    $optionAuthor = false;
  }

  if(!$params->get('list_show_date')) {
    $optionDate = false;
  }

  if(!$params->get('list_show_downloads')) {
    $optionDownloads = false;
  }
}
?>

  <?php if($params->get('filter_ordering')) : ?>
    <div class="span4">
      <label class="filter-ordering-lbl element-invisible" for="filter-ordering">
	<?php echo JText::_('COM_ODYSSEY_SORTING_FILTER_LABEL').'&#160;'; ?>
      </label>
      <select name="filter-ordering" id="filter-ordering" onchange="odyssey.submitForm();">
	<option value=""><?php JText::_('JGLOBAL_SORT_BY'); ?></option>
	<?php $selected = ($filterOrdering === 'order') ? 'selected="selected"' : ''; ?>
	<option value="order" <?php echo $selected; ?>><?php echo JText::_('COM_ODYSSEY_ORDERING_ORDERING_ASC'); ?></option>
	<?php $selected = ($filterOrdering === 'rorder') ? 'selected="selected"' : ''; ?>
	<option value="rorder" <?php echo $selected; ?>><?php echo JText::_('COM_ODYSSEY_ORDERING_ORDERING_DESC'); ?></option>
	<?php $selected = ($filterOrdering === 'alpha') ? 'selected="selected"' : ''; ?>
	<option value="alpha" <?php echo $selected; ?>><?php echo JText::_('COM_ODYSSEY_ORDERING_NAME_ASC'); ?></option>
	<?php $selected = ($filterOrdering === 'ralpha') ? 'selected="selected"' : ''; ?>
	<option value="ralpha" <?php echo $selected; ?>><?php echo JText::_('COM_ODYSSEY_ORDERING_NAME_DESC'); ?></option>
	<?php if($optionDate) : ?>
	  <?php $selected = ($filterOrdering === 'date') ? 'selected="selected"' : ''; ?>
	  <option value="date" <?php echo $selected; ?>><?php echo JText::_('COM_ODYSSEY_ORDERING_DATE_ASC'); ?></option>
	  <?php $selected = ($filterOrdering === 'rdate') ? 'selected="selected"' : ''; ?>
	  <option value="rdate" <?php echo $selected; ?>><?php echo JText::_('COM_ODYSSEY_ORDERING_DATE_DESC'); ?></option>
	<?php endif; ?>
	<?php if($optionAuthor) : ?>
	  <?php $selected = ($filterOrdering === 'author') ? 'selected="selected"' : ''; ?>
	  <option value="author" <?php echo $selected; ?>><?php echo JText::_('COM_ODYSSEY_ORDERING_AUTHOR_ASC'); ?></option>
	  <?php $selected = ($filterOrdering === 'rauthor') ? 'selected="selected"' : ''; ?>
	  <option value="rauthor" <?php echo $selected; ?>><?php echo JText::_('COM_ODYSSEY_ORDERING_AUTHOR_DESC'); ?></option>
	<?php endif; ?>
	<?php if($optionDownloads) : ?>
	  <?php $selected = ($filterOrdering === 'hits') ? 'selected="selected"' : ''; ?>
	  <option value="downloads" <?php echo $selected; ?>><?php echo JText::_('COM_ODYSSEY_ORDERING_HITS_ASC'); ?></option>
	  <?php $selected = ($filterOrdering === 'rhits') ? 'selected="selected"' : ''; ?>
	  <option value="rdownloads" <?php echo $selected; ?>><?php echo JText::_('COM_ODYSSEY_ORDERING_HITS_DESC'); ?></option>
	<?php endif; ?>
      </select>
    </div>
  <?php endif; ?>
