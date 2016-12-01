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

class JFormFieldCountryfilterList extends JFormFieldList
{
  protected $type = 'countryfilterlist';

  protected function getOptions()
  {
    $options = array();
    $post = JFactory::getApplication()->input->post->getArray();
    $duration = '';

    if(isset($post['filter']['duration'])) {
      $duration = $post['filter']['duration'];
    }

      
    //Get the country names.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('c.alpha_2,c.name,c.lang_var')
	  ->from('#__odyssey_country AS c')
	  //Get only the countries defined in the search filter table.
	  ->join('INNER', '#__odyssey_search_filter AS sf_co ON sf_co.country_code=c.alpha_2');

    //Display only countries linked to travels which match the given duration.
    if(!empty($duration)) {
      $query->join('INNER', '#__odyssey_travel AS t ON sf_co.travel_id=t.id')
	    ->where('t.travel_duration='.$db->Quote($duration));
    }

    $query->where('c.published=1')
	  ->group('c.alpha_2')
	  ->order('c.alpha_3');
    $db->setQuery($query);
    $countries = $db->loadObjectList();

    //Build the first option.
    $options[] = JHtml::_('select.option', '', JText::_('COM_ODYSSEY_OPTION_SELECT_COUNTRY'));

    //Build the select options.
    foreach($countries as $country) {
      $options[] = JHtml::_('select.option', $country->alpha_2, (empty($country->lang_var)) ? JText::_($country->name) : JText::_($country->lang_var));
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}



