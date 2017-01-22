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


//Script which build the select html tag containing the addon groups.

class JFormFieldAddonGroupList extends JFormFieldList
{
  protected $type = 'addongrouplist';

  protected function getOptions()
  {
    $options = array();

    //Build the select options.
    for($i = 1; $i < 21; $i++) {
      $options[] = JHtml::_('select.option', $i.':no_sel', JText::sprintf('COM_ODYSSEY_OPTION_GROUP_NO_SEL', $i));
      $options[] = JHtml::_('select.option', $i.':single_sel', JText::sprintf('COM_ODYSSEY_OPTION_GROUP_SINGLE_SEL', $i));
      $options[] = JHtml::_('select.option', $i.':multi_sel', JText::sprintf('COM_ODYSSEY_OPTION_GROUP_MULTI_SEL', $i));
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}



