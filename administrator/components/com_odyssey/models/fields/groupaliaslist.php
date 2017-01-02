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

class JFormFieldGroupaliasList extends JFormFieldList
{
  protected $type = 'groupaliaslist';

  protected function getOptions()
  {
    $options = array();
      
    //Get the group alias of all the departure steps.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('group_alias')
	  ->from('#__odyssey_step')
	  ->where('step_type="departure"')
	  ->order('group_alias');
    $db->setQuery($query);
    $groupAliases = $db->loadColumn();

    //Build the first option.
    $options[] = JHtml::_('select.option', '', JText::_('COM_ODYSSEY_OPTION_SELECT_GROUP_ALIAS'));

    //Build the select options.
    foreach($groupAliases as $groupAlias) {
      $options[] = JHtml::_('select.option', $groupAlias, $groupAlias);
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}

