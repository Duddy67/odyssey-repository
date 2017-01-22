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


//Script which build the select html tag containing the city names and ids.

class JFormFieldCityfilterList extends JFormFieldList
{
  protected $type = 'cityfilterlist';

  protected function getOptions()
  {
    $options = array();
    $post = JFactory::getApplication()->input->post->getArray();
    $country = $region = $duration = '';

    if(isset($post['filter']['country'])) {
      $country = $post['filter']['country'];
    }
      
    if(isset($post['filter']['region'])) {
      $region = $post['filter']['region'];
    }
      
    if(isset($post['filter']['duration'])) {
      $duration = $post['filter']['duration'];
    }

    //Get the city names.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('c.id,c.name,c.lang_var')
	  ->from('#__odyssey_city AS c')
	  //Get only the cities defined in the search filter table.
	  ->join('INNER', '#__odyssey_search_filter AS sf_ci ON sf_ci.city_id=c.id');

    //Display only the cities linked to travels which have the same filter region or country. 
    if(!empty($country) || !empty($region)) {
      $column = 'country';
      $filter = $country;

      //If it set, use region over country.
      if(!empty($region)) {
	$column = 'region';
	$filter = $region;
      }

      $query->join('INNER', '#__odyssey_search_filter AS sf ON sf.'.$column.'_code='.$db->Quote($filter))
	    ->where('sf_ci.travel_id=sf.travel_id');
    }

    //Display only cities linked to travels which match the given duration.
    if(!empty($duration)) {
      $query->join('INNER', '#__odyssey_travel AS t ON sf_ci.travel_id=t.id')
	    ->where('t.travel_duration='.$db->Quote($duration));
    }

    $query->where('c.published=1')
	  ->group('sf_ci.city_id')
	  ->order('sf_ci.city_id');
    $db->setQuery($query);
    $cities = $db->loadObjectList();

    //Build the first option.
    $options[] = JHtml::_('select.option', '', JText::_('COM_ODYSSEY_OPTION_SELECT_CITY'));

    //Build the select options.
    foreach($cities as $city) {
      $options[] = JHtml::_('select.option', $city->id, (empty($city->lang_var)) ? JText::_($city->name) : JText::_($city->lang_var));
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}



