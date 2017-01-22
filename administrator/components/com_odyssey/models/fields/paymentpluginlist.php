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


//Script which build the select html tag containing the payment plugins names and element.

class JFormFieldPaymentPluginList extends JFormFieldList
{
  protected $type = 'paymentpluginlist';

  protected function getOptions()
  {
    $options = array();
      
    //Get the payment plugins.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('name,element')
	  ->from('#__extensions')
	  ->where('type="plugin" AND folder="odysseypayment" AND enabled=1')
	  ->order('ordering');
    $db->setQuery($query);
    $plugins = $db->loadObjectList();

    // Get all the view level of the user.
    $user = JFactory::getUser();

    //Build the first option.
    $options[] = JHtml::_('select.option', '', JText::_('COM_ODYSSEY_OPTION_SELECT'));

    //Build the select options.
    foreach($plugins as $plugin) {
      $options[] = JHtml::_('select.option', $plugin->element, $plugin->name);
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}


