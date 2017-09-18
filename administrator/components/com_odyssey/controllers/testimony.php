<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
require_once JPATH_ADMINISTRATOR.'/components/com_odyssey/helpers/odyssey.php';


class OdysseyControllerTestimony extends JControllerForm
{

  public function save($key = null, $urlVar = null)
  {
    //Get the jform data.
    $data = $this->input->post->get('jform', array(), 'array');

    //Set some jform fields.
    
    //Get current date and time (equal to NOW() in SQL).
    $now = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);
    //Update the modification.
    $data['modified'] = $now;

    if($data['id'] == 0) { //New item
      //Set the possible undefined parameters.
      if(empty($data['created'])) {
	$data['created'] = $now;
      }

      if(empty($data['created_by'])) {
	//Get the current user id.
	$user = JFactory::getUser();
	$data['created_by'] = $user->id;
      }
    }
    else { //Item exists.
      //A testimony is about to be trashed, archived or unpublished.
      if($data['published'] != 1) {
	//Check if this testimony is used by a city.
	if(OdysseyHelper::isInCity($data['alpha_2'])) {
	  $app = JFactory::getApplication();
	  // Save the data in the session.
	  $app->setUserState($this->option.'.edit.'.$this->context.'.data', $data);
	  $app->enqueueMessage(JText::_('COM_ODYSSEY_WARNING_COUNTRY_USED_BY_CITY'), 'warning');
	  $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view='.$this->view_item.'&layout=edit&id='.$data['id'], false));
	  return false;
	}
      }
    }

    //Reset the jform data array 
    $this->input->post->set('jform', $data);

    //Hand over to the parent function.
    return parent::save($key = null, $urlVar = null);
  }


  //Overrided function.
  protected function allowEdit($data = array(), $key = 'id')
  {
    $itemId = $data['id'];
    $user = JFactory::getUser();

    //Get the item owner id.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('created_by')
	  ->from('#__odyssey_testimony')
	  ->where('id='.(int)$itemId);
    $db->setQuery($query);
    $createdBy = $db->loadResult();

    $canEdit = $user->authorise('core.edit', 'com_odyssey');
    $canEditOwn = $user->authorise('core.edit.own', 'com_odyssey') && $createdBy == $user->id;

    //Allow edition. 
    if($canEdit || $canEditOwn) {
      return 1;
    }

    //Hand over to the parent function.
    return parent::allowEdit($data = array(), $key = 'id');
  }
}

