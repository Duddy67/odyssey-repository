<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

$data = $displayData;
//Get the filter setting from the component global configuration.
$searchFilters = $data['search_filters'];
//Duration and date filters are always showed.
$showedFilters = array('price', 'duration', 'date'); 

//Set the filters to show according to the filter setting.
if($searchFilters != 'region' && $searchFilters != 'city' && $searchFilters != 'region_city') {
  $showedFilters[] = 'country';
}

if($searchFilters != 'country' && $searchFilters != 'city' && $searchFilters != 'country_city') {
  $showedFilters[] = 'region';
}

if($searchFilters != 'country' && $searchFilters != 'region' && $searchFilters != 'country_region') {
  $showedFilters[] = 'city';
}

// Check for show on fields.
$filters = $data['view']->filterForm->getGroup('filter');
foreach ($filters as $field)
{
	if ($showonstring = $field->getAttribute('showon'))
	{
		$showonarr = array();
		foreach (preg_split('%\[AND\]|\[OR\]%', $showonstring) as $showonfield)
		{
			$showon   = explode(':', $showonfield, 2);
			$showonarr[] = array(
				'field'  => $showon[0],
				'values' => explode(',', $showon[1]),
				'op'     => (preg_match('%\[(AND|OR)\]' . $showonfield . '%', $showonstring, $matches)) ? $matches[1] : ''
			);
		}
		$data['view']->filterForm->setFieldAttribute($field->fieldname, 'dataShowOn', json_encode($showonarr), $field->group);
	}
}

// Load the form filters
$filters = $data['view']->filterForm->getGroup('filter');
?>

<?php if ($filters) : ?>
	<?php foreach ($filters as $fieldName => $field) : ?>
		<?php if ($fieldName != 'filter_search' && in_array($field->getAttribute('name'), $showedFilters)) : ?>
			<?php
			$showOn = '';
			if ($showOnData = $field->getAttribute('dataShowOn'))
			{
				JHtml::_('jquery.framework');
				JHtml::_('script', 'jui/cms.js', false, true);
				$showOn = " data-showon='" . $showOnData . "'";
			}
			?>
			<div class="js-stools-field-filter"<?php echo $showOn; ?>>
				<?php echo $field->input; ?>
			</div>
		<?php endif; ?>
	<?php endforeach; ?>
<?php endif; ?>
