<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modeladmin');


class OdysseyModelCountry extends JModelAdmin
{
  //Prefix used with the controller messages.
  protected $text_prefix = 'COM_ODYSSEY';

  //Returns a Table object, always creating it.
  //Table can be defined/overrided in the file: tables/mycomponent.php
  public function getTable($type = 'Country', $prefix = 'OdysseyTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  public function getForm($data = array(), $loadData = true) 
  {
    $form = $this->loadForm('com_odyssey.country', 'country', array('control' => 'jform', 'load_data' => $loadData));

    if(empty($form)) {
      return false;
    }

    return $form;
  }


  protected function loadFormData() 
  {
    // Check the session for previously entered form data.
    $data = JFactory::getApplication()->getUserState('com_odyssey.edit.country.data', array());

    if(empty($data)) {
      $data = $this->getItem();
    }

    return $data;
  }


  protected function canEditState($record)
  {
    //A country is about to be trashed, archived or unpublished.
    //Check if this country is used by a city.
    if(OdysseyHelper::isInCity($record->alpha_2)) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_WARNING_COUNTRY_USED_BY_CITY'), 'warning');
      return false;
    }

    return parent::canEditState($record);
  }


  protected function canDelete($record)
  {
    //A country is about to be deleted.
    //Check if this country is used by a city.
    if(OdysseyHelper::isInCity($record->alpha_2)) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_WARNING_COUNTRY_USED_BY_CITY'), 'warning');
      return false;
    }

    return parent::canDelete($record);
  }
}

