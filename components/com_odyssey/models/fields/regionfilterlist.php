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


//Script which build the select html tag containing the region names and codes.

class JFormFieldRegionfilterList extends JFormFieldList
{
  protected $type = 'regionfilterlist';

  protected function getOptions()
  {
    $options = array();
      
    //Get the region names.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('id_code,lang_var')
	  ->from('#__odyssey_region')
	  //Get only the regions defined in the search filter table.
	  ->join('INNER', '#__odyssey_search_filter ON region_code=id_code')
	  ->group('id_code')
	  ->order('id_code');
    $db->setQuery($query);
    $regions = $db->loadObjectList();

    //Build the first option.
    $options[] = JHtml::_('select.option', '', JText::_('COM_ODYSSEY_OPTION_SELECT'));

    //Build the select options.
    foreach($regions as $region) {
      $options[] = JHtml::_('select.option', $region->id_code, JText::_($region->lang_var));
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}



