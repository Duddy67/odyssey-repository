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


//Script which build the select html tag containing the region names and codes.

class JFormFieldRegionfilterList extends JFormFieldList
{
  protected $type = 'regionfilterlist';

  protected function getOptions()
  {
    $options = array();
    $post = JFactory::getApplication()->input->post->getArray();
    $country = $duration = '';

    if(isset($post['filter']['country'])) {
      $country = $post['filter']['country'];
    }

    if(isset($post['filter']['duration'])) {
      $duration = $post['filter']['duration'];
    }

    //Get the region names.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('r.id_code,r.lang_var')
	  ->from('#__odyssey_region AS r')
	  //Get only the regions defined in the search filter table.
	  ->join('INNER', '#__odyssey_search_filter AS sf_re ON sf_re.region_code=r.id_code');

    //Display only the regions linked to travels which have the same filter country. 
    if(!empty($country)) {
      $query->join('INNER', '#__odyssey_search_filter AS sf_co ON sf_co.country_code='.$db->Quote($country))
	    ->where('sf_re.travel_id=sf_co.travel_id');
    }

    //Display only regions linked to travels which match the given duration.
    if(!empty($duration)) {
      $query->join('INNER', '#__odyssey_travel AS t ON sf_re.travel_id=t.id')
	    ->where('t.travel_duration='.$db->Quote($duration));
    }

    $query->group('r.id_code')
	  ->order('r.id_code');
    $db->setQuery($query);
    $regions = $db->loadObjectList();

    //Build the first option.
    $options[] = JHtml::_('select.option', '', JText::_('COM_ODYSSEY_OPTION_SELECT_REGION'));

    //Build the select options.
    foreach($regions as $region) {
      $options[] = JHtml::_('select.option', $region->id_code, JText::_($region->lang_var));
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}



