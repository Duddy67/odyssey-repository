<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
require_once JPATH_ROOT.'/administrator/components/com_odyssey/helpers/odms.php';
require_once JPATH_ROOT.'/components/com_odyssey/helpers/travel.php';
 


class OdysseyControllerCustomer extends JControllerForm
{

  public function save($key = null, $urlVar = null)
  {
    //Get the jform data.
    $data = $this->input->post->get('jform', array(), 'array');

    //$post = JRequest::get('post');
    //Reset the jform data array 
    $this->input->post->set('jform', $data);

    //Hand over to the parent function.
    return parent::save($key = null, $urlVar = null);
  }


  public function uploadFile()
  {
    //Get the jform data.
    $data = $this->input->post->get('jform', array(), 'array');
    $document = OdmsHelper::uploadFile('customer');

    if(empty($document['error'])) {
      $document['item_id'] = $data['id'];
      $document['item_type'] = 'customer';
      $document['uploaded_by'] = 'admin';
      //Adds the file as document in the table.
      OdmsHelper::addDocument($document);

      $user = JFactory::getUser($data['id']);
      $websiteUrl = JURI::root();
      $subject = JText::_('COM_ODYSSEY_EMAIL_SENDING_DOCUMENT_SUBJECT');
      $body = JText::sprintf('COM_ODYSSEY_EMAIL_SENDING_DOCUMENT_BODY', $user->get('username'), $document['file_name'], $websiteUrl);
      $message = array('subject' => $subject, 'body' => $body);
      //Informs the customer.
      TravelHelper::sendEmail('sending_document', $data['id'], 0, $message);

      $this->setMessage(JText::sprintf('COM_ODYSSEY_FILE_SUCCESSFULLY_UPLOADED', $document['file_name']));
    }
    else {
      $this->setMessage(JText::_($document['error']), 'warning');
    }

    $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=customer&layout=edit&id='.$data['id'], false));

    return;
  }


  public function removeDocument()
  {
    //Get the POST and jform data.
    $post = $this->input->post->getArray();
    $data = $post['jform'];

    if(OdmsHelper::removeFile($post['document_id'])) {
      OdmsHelper::removeDocument($post['document_id']);
      $this->setMessage(JText::_('COM_ODYSSEY_FILE_REMOVED_FROM_SERVER'));
    }

    $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=customer&layout=edit&id='.$data['id'], false));

    return;
  }
}

