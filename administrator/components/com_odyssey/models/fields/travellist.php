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


//Script which build the select html tag containing the travels.

class JFormFieldTravelList extends JFormFieldList
{
  protected $type = 'travellist';

  protected function getOptions()
  {
    $options = array();
      
    $user = JFactory::getUser();
    $groups = implode(',', $user->getAuthorisedViewLevels());

    //Get the tax rates.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('id,name')
	  ->from('#__odyssey_travel')
	  ->where('published=1')
	  ->where('access IN ('.$groups.')')
	  ->order('ordering');
    $db->setQuery($query);
    $travels = $db->loadObjectList();

    //Build the first option.
    //$options[] = JHtml::_('select.option', '', JText::_('COM_ODYSSEY_OPTION_SELECT'));

    //Build the select options.
    foreach($travels as $travel) {
      $options[] = JHtml::_('select.option', $travel->id, $travel->name);
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}

