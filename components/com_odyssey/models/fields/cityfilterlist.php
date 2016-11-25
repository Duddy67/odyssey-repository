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


//Script which build the select html tag containing the city names and ids.

class JFormFieldCityfilterList extends JFormFieldList
{
  protected $type = 'cityfilterlist';

  protected function getOptions()
  {
    $options = array();
      
    //Get the city names.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('id,name,lang_var')
	  ->from('#__odyssey_city')
	  //Get only the cities defined in the search filter table.
	  ->join('INNER', '#__odyssey_search_filter ON city_id=id')
	  ->where('published=1')
	  ->group('city_id')
	  ->order('city_id');
    $db->setQuery($query);
    $cities = $db->loadObjectList();

    //Build the first option.
    $options[] = JHtml::_('select.option', '', JText::_('COM_ODYSSEY_OPTION_SELECT'));

    //Build the select options.
    foreach($cities as $city) {
      $options[] = JHtml::_('select.option', $city->id, (empty($city->lang_var)) ? JText::_($city->name) : JText::_($city->lang_var));
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}



