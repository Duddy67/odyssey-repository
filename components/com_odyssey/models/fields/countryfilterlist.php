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
      
    //Get the country names.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('alpha_2,name,lang_var')
	  ->from('#__odyssey_country')
	  //Get only the countries defined in the search filter table.
	  ->join('INNER', '#__odyssey_search_filter ON country_code=alpha_2')
	  ->where('published=1')
	  ->group('alpha_2')
	  ->order('alpha_3');
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



