<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('JPATH_BASE') or die;

$data = $displayData;

// Receive overridable options
$data['options'] = !empty($data['options']) ? $data['options'] : array();

// Set some basic options
$customOptions = array(
	'filtersHidden'       => isset($data['options']['filtersHidden']) ? $data['options']['filtersHidden'] : empty($data['view']->activeFilters),
	'defaultLimit'        => isset($data['options']['defaultLimit']) ? $data['options']['defaultLimit'] : JFactory::getApplication()->get('list_limit', 20),
	'searchFieldSelector' => '#filter_search',
	'orderFieldSelector'  => '#list_fullordering',
	'totalResults'        => isset($data['options']['totalResults']) ? $data['options']['totalResults'] : -1,
	'noResultsText'       => isset($data['options']['noResultsText']) ? $data['options']['noResultsText'] : JText::_('JGLOBAL_NO_MATCHING_RESULTS'),
);

$data['options'] = array_merge($customOptions, $data['options']);

$formSelector = !empty($data['options']['formSelector']) ? $data['options']['formSelector'] : '#adminForm';

// Load search tools
JHtml::_('searchtools.form', $formSelector, $data['options']);

$filtersClass = isset($data['view']->activeFilters) && $data['view']->activeFilters ? ' js-stools-container-filters-visible' : '';
?>

<?php echo JLayoutHelper::render('filters', $data, JPATH_SITE.'/components/com_odyssey/layouts/search/'); ?>
<div class="btn-wrapper">
  <button type="button" id="search-btn-clear" class="btn hasTooltip js-stools-btn-clear"
	  title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_CLEAR'); ?>">
	  <?php echo JText::_('JSEARCH_FILTER_CLEAR');?>
  </button>
</div>
<?php echo JLayoutHelper::render('joomla.searchtools.default.list', $data); ?>

