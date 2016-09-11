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


//Script which build the select html tag containing the tax rates previously
//defined.

class JFormFieldTaxList extends JFormFieldList
{
  protected $type = 'taxlist';

  protected function getOptions()
  {
    $options = array();
      
    //Get the tax rates.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('id,rate')
	  ->from('#__odyssey_tax')
	  ->where('published=1')
	  ->order('ordering');
    $db->setQuery($query);
    $taxes = $db->loadObjectList();

    // Get all the view level of the user.
    $user = JFactory::getUser();

    //Build the first option.
    $options[] = JHtml::_('select.option', '', JText::_('COM_ODYSSEY_OPTION_SELECT'));

    //Build the select options.
    foreach($taxes as $tax) {
      $options[] = JHtml::_('select.option', $tax->id, JText::_($tax->rate.' %'));
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}

