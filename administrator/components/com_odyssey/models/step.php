<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modeladmin');
require_once JPATH_ADMINISTRATOR.'/components/com_odyssey/helpers/odyssey.php';


class OdysseyModelStep extends JModelAdmin
{
  //Prefix used with the controller messages.
  protected $text_prefix = 'COM_ODYSSEY';

  //Returns a Table object, always creating it.
  //Table can be defined/overrided in the file: tables/mycomponent.php
  public function getTable($type = 'Step', $prefix = 'OdysseyTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  public function getForm($data = array(), $loadData = true) 
  {
    $form = $this->loadForm('com_odyssey.step', 'step', array('control' => 'jform', 'load_data' => $loadData));

    if(empty($form)) {
      return false;
    }

    return $form;
  }


  protected function loadFormData() 
  {
    // Check the session for previously entered form data.
    $data = JFactory::getApplication()->getUserState('com_odyssey.edit.step.data', array());

    if(empty($data)) {
      $data = $this->getItem();
    }

    return $data;
  }

  //Overrided functions.

  public function getItem($pk = null)
  {
    $item = parent::getItem($pk = null);

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    //Get the title of the category the step is linked to.
    if($item->id && $item->step_type == 'link') {
      $query->select('title')
	    ->from('#__categories')
	    ->where('id='.(int)$item->catid);
      $db->setQuery($query);
      $item->category_title = $db->loadResult();
    }
    elseif($item->id && $item->step_type == 'departure') {
      $query->select('travel_code')
	    ->from('#__odyssey_travel')
	    ->where('dpt_step_id='.(int)$item->id);
      $db->setQuery($query);
      $item->travel_code = $db->loadResult();

      if(empty($item->travel_code)) {
	$item->travel_code = JText::_('COM_ODYSSEY_NO_TRAVEL_CODE_AVAILABLE');
      }
    }

    return $item;
  }


  protected function canEditState($record)
  {
    //A departure step is about to be trashed, archived or unpublished.
    //Check if this departure step is part of a travel.
    if($record->step_type == 'departure' && $this->state->task != 'publish' && OdysseyHelper::isInTravel($record->id)) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_WARNING_DEPARTURE_STEP_USED_IN_TRAVEL'), 'warning');
      return false;
    }

    return parent::canEditState($record);
  }


  protected function canDelete($record)
  {
    //A departure step is about to be deleted.
    //Check if this departure step is part of a travel.
    if($record->step_type == 'departure' && OdysseyHelper::isInTravel($record->id)) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_WARNING_DEPARTURE_STEP_USED_IN_TRAVEL'), 'warning');
      return false;
    }

    //A link step is about to be deleted.
    //Check if its departure step is part of a travel.
    if($record->step_type == 'link' && OdysseyHelper::isInTravel($record->dpt_step_id)) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_WARNING_LINK_STEP_USED_IN_TRAVEL'), 'warning');
      return false;
    }

    return parent::canDelete($record);
  }
}

