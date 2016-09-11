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

class JFormFieldCityList extends JFormFieldList
{
  protected $type = 'citylist';

  protected function getOptions()
  {
    $options = array();
      
    //Get the cities.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('id,name,lang_var,country_code,region_code')
	  ->from('#__odyssey_city')
	  ->where('published=1')
	  ->order('name');
    $db->setQuery($query);
    $cities = $db->loadObjectList();

    //Build the first option.
    $options[] = JHtml::_('select.option', '', JText::_('COM_ODYSSEY_OPTION_SELECT'));

    //Build the select options.
    foreach($cities as $city) {
      $codes = ' ('.$city->country_code.' - '.$city->region_code.')';
      $options[] = JHtml::_('select.option', $city->id, (empty($city->lang_var)) ?  JText::_($city->name).$codes : JText::_($city->lang_var).$codes);
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}



