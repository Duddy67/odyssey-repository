<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');


class OdysseyControllerOrder extends JControllerForm
{
  //Check the edit.own permission for this user.
  protected function allowEdit($data = array(), $key = 'id')
  {
    // Initialise variables.
    $recordId = (int) isset($data[$key]) ? $data[$key] : 0;

    //Since order table has no created_by field (cause orders are created
    //automaticaly), we need to get the customer_id field value to check it against
    //the user id.

    //Get the user id.
    $user = JFactory::getUser();
    $userId = $user->get('id');

    //Get the model.
    $record = $this->getModel()->getItem($recordId);

    if(empty($record)) {
      return false;
    }

    // If the owner matches 'me' then do the test.
    if($record->customer_id == $userId) {
      return true;
    }

    //Hand over to the parent function.
    return parent::allowEdit($data = array(), $key = 'id');
  }


  /**
   * Method to edit an existing record.
   *
   * @param   string  $key     The name of the primary key of the URL variable.
   * @param   string  $urlVar  The name of the URL variable if different from the primary key
   * (sometimes required to avoid router collisions).
   *
   * @return  boolean  True if access level check and checkout passes, false otherwise.
   *
   * @since   1.6
   */
  public function edit($key = null, $urlVar = 'o_id')
  {
    $result = parent::edit($key, $urlVar);

    return $result;
  }


  /**
   * Method to cancel an edit.
   *
   * @param   string  $key  The name of the primary key of the URL variable.
   *
   * @return  boolean  True if access level checks pass, false otherwise.
   *
   * @since   1.6
   */
  public function cancel($key = 'o_id')
  {
    parent::cancel($key);
  }


  /**
   * Method to get out of the form order, checkout then redirect to the outstdbal view.
   * Note: The code is a copy slighly modified of the cancel parent function.
   *
   * @param   string  $key  The name of the primary key of the URL variable.
   *
   * @return  boolean  True if access level checks pass, false otherwise.
   *
   */
  public function pay($key = 'o_id')
  {
    //JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

    $model = $this->getModel();
    $table = $model->getTable();
    $context = "$this->option.edit.$this->context";

    if(empty($key)) {
      $key = $table->getKeyName();
    }

    $recordId = $this->input->getInt($key);

    // Attempt to check-in the current record.
    if($recordId) {
      if(property_exists($table, 'checked_out')) {
	if($model->checkin($recordId) === false) {
	  // Check-in failed, go back to the record and display a notice.
	  $this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError()));
	  $this->setMessage($this->getError(), 'error');

	  $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view='.$this->view_item.$this->getRedirectToItemAppend($recordId, $key), false));

	  return false;
	}
      }
    }

    // Clean the session data and redirect.
    $this->releaseEditId($context, $recordId);
    JFactory::getApplication()->setUserState($context . '.data', null);

    $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=outstdbal', false));

    return true;
  }


  /**
   * Method to save a record.
   *
   * @param   string  $key     The name of the primary key of the URL variable.
   * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
   *
   * @return  boolean  True if successful, false otherwise.
   *
   * @since   1.6
   */
  public function save($key = null, $urlVar = 'o_id')
  {
    $app = JFactory::getApplication();
    $recordId = $this->input->getInt($urlVar);

    //Get the jform data.
    $data = $this->input->post->get('jform', array(), 'array');
    var_dump($data);
    //return;
    //Code...
    //Update jform with the modified data.
    //$this->input->post->set('jform', $data);

    $result = parent::save($key, $urlVar);

    // If ok, redirect to the order list view.
    if($result) {
      $this->setRedirect(JRoute::_('index.php?option=com_odyssey&view=orders'));
    }

    return $result;
  }
}

