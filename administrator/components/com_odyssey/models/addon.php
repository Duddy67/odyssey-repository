<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modeladmin');
require_once JPATH_ADMINISTRATOR.'/components/com_odyssey/helpers/utility.php';


class OdysseyModelAddon extends JModelAdmin
{
  //Prefix used with the controller messages.
  protected $text_prefix = 'COM_ODYSSEY';

  //Returns a Table object, always creating it.
  //Table can be defined/overrided in the file: tables/mycomponent.php
  public function getTable($type = 'Addon', $prefix = 'OdysseyTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  public function getForm($data = array(), $loadData = true) 
  {
    $form = $this->loadForm('com_odyssey.addon', 'addon', array('control' => 'jform', 'load_data' => $loadData));

    if(empty($form)) {
      return false;
    }

    return $form;
  }


  protected function loadFormData() 
  {
    // Check the session for previously entered form data.
    $data = JFactory::getApplication()->getUserState('com_odyssey.edit.addon.data', array());

    if(empty($data)) {
      $data = $this->getItem();
    }

    return $data;
  }


  /**
   * Method to get a single record.
   *
   * @param   integer  $pk  The id of the primary key.
   *
   * @return  mixed  Object on success, false on failure.
   */
  public function getItem($pk = null)
  {
    if($item = parent::getItem($pk)) {

      if($item->addon_type == 'hosting') {
	$db = $this->getDbo();
	$query = $db->getQuery(true);
	//Get the value of the attribute from the mapping table.
	$query->select('nb_persons')
	      ->from('#__odyssey_addon_hosting')
	      ->where('addon_id='.(int)$item->id);
	$db->setQuery($query);
	$item->nb_persons = $db->loadResult();
      }
    }

    return $item;
  }


  protected function canDelete($record)
  {
    //An addon is about to be deleted.
    //Check if this addon is part of a step.
    if(OdysseyHelper::isInStep($record->id, 'addon')) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_WARNING_ADDON_USED_IN_STEP'), 'warning');
      return false;
    }

    return parent::canDelete($record);
  }
}

