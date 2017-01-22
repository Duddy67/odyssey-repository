<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
 


class OdysseyControllerAddon extends JControllerForm
{

  /**
   * Overrided Joomla's method.
   * Method to save a record.
   *
   * @param   string  $key     The name of the primary key of the URL variable.
   * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
   *
   * @return  boolean  True if successful, false otherwise.
   *
   * @since   12.2
   */
  public function save($key = null, $urlVar = null)
  {
    //Get the jform data.
    $data = $this->input->post->get('jform', array(), 'array');
$post = JFactory::getApplication()->input->post->getArray();
var_dump($post);
//return;
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

    //Reset the jform data array 
    $this->input->post->set('jform', $data);

    //Hand over to the parent function.
    return parent::save($key = null, $urlVar = null);
  }


  /**
   * Overrided Joomla's method.
   * Method to check if you can edit an existing record.
   *
   * Extended classes can override this if necessary.
   *
   * @param   array   $data  An array of input data.
   * @param   string  $key   The name of the key for the primary key; default is id.
   *
   * @return  boolean
   *
   * @since   12.2
   */
  protected function allowEdit($data = array(), $key = 'id')
  {
    $itemId = $data['id'];
    $user = JFactory::getUser();

    //Get the item owner id.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('created_by')
	  ->from('#__odyssey_addon')
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

