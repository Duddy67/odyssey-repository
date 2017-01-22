<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
// import the list field type
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');
require_once JPATH_ROOT.'/administrator/components/com_odyssey/helpers/utility.php';
require_once JPATH_ROOT.'/components/com_odyssey/models/search.php';


//Script which build the select html tag containing the available departure dates.

class JFormFieldDatefilterList extends JFormFieldList
{
  protected $type = 'datefilterlist';

  protected function getOptions()
  {
    $options = $dates = array();
    $nowDate = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);
    $post = JFactory::getApplication()->input->post->getArray();
    $country = $region = $city = $duration = '';

    if(isset($post['filter']['country'])) {
      $country = $post['filter']['country'];
    }

    if(isset($post['filter']['region'])) {
      $region = $post['filter']['region'];
    }

    if(isset($post['filter']['city'])) {
      $city = $post['filter']['city'];
    }
      
    if(isset($post['filter']['duration'])) {
      $duration = $post['filter']['duration'];
    }

    //Get departure dates
    //Note: Get only the date part, (ie: yyyy-mm-dd).
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('SUBSTRING(ds.date_time, 1, 10) AS date_time, SUBSTRING(ds.date_time_2, 1, 10) AS date_time_2')
	  ->from('#__odyssey_departure_step_map AS ds')
	  ->join('INNER', '#__odyssey_travel AS t ON t.dpt_step_id=ds.step_id');

    //Get only departure linked to the selected country, region, or city.
    if(!empty($country)) {
      $query->join('INNER', '#__odyssey_search_filter AS sf_co ON sf_co.travel_id=t.id')
	    ->where('sf_co.country_code='.$db->Quote($country));
    }

    if(!empty($region)) {
      $query->join('INNER', '#__odyssey_search_filter AS sf_re ON sf_re.travel_id=t.id')
	    ->where('sf_re.region_code='.$db->Quote($region));
    }

    if(!empty($city)) {
      $query->join('INNER', '#__odyssey_search_filter AS sf_ci ON sf_ci.travel_id=t.id')
	    ->where('sf_ci.city_id='.(int)$city);
    }

    //Display only departures linked to travels which match the given duration.
    if(!empty($duration)) {
      $query->where('t.travel_duration='.$db->Quote($duration));
    }

    $query->where('t.published=1')
	  ->where('(ds.date_time > '.$db->Quote($nowDate).' OR date_time_2 > '.$db->Quote($nowDate).')')
	  ->order('ds.date_time');
    $db->setQuery($query);
    $results = $db->loadObjectList();

    foreach($results as $result) {
      //Departure per period.
      if($result->date_time_2 > 0) { 
	//Join the 2 dates with an underscore.
	$period = $result->date_time.'_'.$result->date_time_2;
	if(!in_array($period, $dates)) {
	  $dates[] = $period;
	}
      }
      //Standard departure.
      elseif(!in_array($result->date_time, $dates)) {
	$dates[] = $result->date_time;
      }
    }

    //Resort the date array just in case by using a bubble sort algorithm.
    $nbDates = count($dates);
    for($i = 0; $i < $nbDates; $i++) {
      for($j = 0; $j < $nbDates - 1; $j++) {
	if($dates[$j] > $dates[$j + 1]) {
	  $temp = $dates[$j + 1];
	  $dates[$j + 1] = $dates[$j];
	  $dates[$j] = $temp;
	}
      }
    }

    //Build the first option.
    $options[] = JHtml::_('select.option', '', JText::_('COM_ODYSSEY_OPTION_SELECT_DEPARTURE'));

    //Build the select options.
    foreach($dates as $date) {
      //Departure per period.
      if(preg_match('#^([0-9]{4}-[0-9]{2}-[0-9]{2})_([0-9]{4}-[0-9]{2}-[0-9]{2})$#', $date, $matches)) {
	//Format the 2 dates.
	$date1 = JHtml::_('date', $matches[1], JText::_('DATE_FORMAT_LC3'));
	$date2 = JHtml::_('date', $matches[2], JText::_('DATE_FORMAT_LC3'));
	$options[] = JHtml::_('select.option', $date, JText::_('COM_ODYSSEY_PERIOD_FROM').$date1.' '.JText::_('COM_ODYSSEY_PERIOD_TO').$date2);
      }
      //Standard departure.
      else {
	$options[] = JHtml::_('select.option', $date, JHtml::_('date', $date, JText::_('DATE_FORMAT_LC3')));
      }
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}



