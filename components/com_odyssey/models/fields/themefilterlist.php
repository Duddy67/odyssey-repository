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


//Script which build the select html tag containing the country names and codes.

class JFormFieldThemefilterList extends JFormFieldList
{
  protected $type = 'themefilterlist';

  protected function getOptions()
  {
    $options = array();
    $post = JFactory::getApplication()->input->post->getArray();
    $country = $region = $city = $price = '';

    if(isset($post['filter']['country'])) {
      $country = $post['filter']['country'];
    }

    if(isset($post['filter']['region'])) {
      $region = $post['filter']['region'];
    }

    if(isset($post['filter']['city'])) {
      $city = $post['filter']['city'];
    }
      
    if(isset($post['filter']['price'])) {
      $price = $post['filter']['price'];
    }
      
    if(isset($post['filter']['duration'])) {
      $duration = $post['filter']['duration'];
    }
      
    //Get the country names.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('t.theme')
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

    if(!empty($price)) {
      $query->where('t.price_range='.$db->Quote($price));
    }

    if(!empty($duration)) {
      $query->where('t.travel_duration='.$db->Quote($duration));
    }

    $query->where('t.published=1')
	  ->group('t.theme')
	  ->order('t.theme DESC');
    $db->setQuery($query);
    $themes = $db->loadColumn();

    //Build the first option.
    $options[] = JHtml::_('select.option', '', JText::_('COM_ODYSSEY_OPTION_SELECT_THEME'));

    //Build the select options.
    foreach($themes as $theme) {
      $options[] = JHtml::_('select.option', $theme, JText::_('COM_ODYSSEY_OPTION_THEME_'.strtoupper($theme)));
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}



