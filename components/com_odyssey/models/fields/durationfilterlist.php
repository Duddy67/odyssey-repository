<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
// import the list field type
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');


//Script which build the select html tag containing the country names and codes.

class JFormFieldDurationfilterList extends JFormFieldList
{
  protected $type = 'durationfilterlist';

  protected function getOptions()
  {
    $options = array();
    $post = JFactory::getApplication()->input->post->getArray();
    $country = $region = $city = '';

    if(isset($post['filter']['country'])) {
      $country = $post['filter']['country'];
    }

    if(isset($post['filter']['region'])) {
      $region = $post['filter']['region'];
    }

    if(isset($post['filter']['city'])) {
      $city = $post['filter']['city'];
    }
      
      
    //Get the country names.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('t.travel_duration')
	  ->from('#__odyssey_travel AS t');

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

    $query->where('t.published=1')
	  ->group('t.travel_duration')
	  ->order('t.travel_duration DESC');
    $db->setQuery($query);
    $durations = $db->loadColumn();

    //Build the first option.
    $options[] = JHtml::_('select.option', '', JText::_('COM_ODYSSEY_OPTION_SELECT_DURATION'));

    //Build the select options.
    foreach($durations as $duration) {
      $options[] = JHtml::_('select.option', $duration, JText::_('COM_ODYSSEY_OPTION_TRAVEL_DURATION_'.strtoupper($duration)));
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}



