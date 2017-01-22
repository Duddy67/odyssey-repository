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


//Script which build the select html tag containing the currency names and ids.

class JFormFieldCurrencyList extends JFormFieldList
{
  protected $type = 'currencylist';

  protected function getOptions()
  {
    $options = array();
      
    //Get the currency names.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('alpha,name,lang_var')
	  ->from('#__odyssey_currency')
	  ->where('published=1')
	  ->order('alpha');
    $db->setQuery($query);
    $currencies = $db->loadObjectList();

    // Get all the view level of the user.
    $user = JFactory::getUser();

    //Build the first option.
    $options[] = JHtml::_('select.option', '', JText::_('COM_ODYSSEY_OPTION_SELECT'));

    //Build the select options.
    foreach($currencies as $currency) {
      //If language variable is not defined, the current name is displayed. 
      $options[] = JHtml::_('select.option', $currency->alpha,
			    (empty($currency->lang_var)) ? JText::_($currency->name) : JText::_($currency->lang_var));
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}


