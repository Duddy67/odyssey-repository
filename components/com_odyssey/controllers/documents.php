<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
require_once JPATH_ROOT.'/administrator/components/com_odyssey/helpers/odms.php';
 
jimport('joomla.application.component.controlleradmin');
jimport('joomla.filesystem.file');
 

class OdysseyControllerDocuments extends JControllerAdmin
{
  /**
   * Proxy for getModel.
   * @since 1.6
  */
  public function getModel($name = 'Document', $prefix = 'OdysseyModel', $config = array('ignore_request' => true))
  {
    $model = parent::getModel($name, $prefix, $config);

    return $model;
  }


  public function uploadFile()
  {
    //Get the POST data.
    $post = $this->input->post->getArray();
    $document = OdmsHelper::uploadFile('customer');

    if(empty($document['error'])) {
      $document['item_id'] = $post['item_id'];
      $document['item_type'] = 'customer';
      $document['uploaded_by'] = 'customer';
      //
      OdmsHelper::addDocument($document);

      $this->setMessage(JText::sprintf('COM_ODYSSEY_FILE_SUCCESSFULLY_UPLOADED', $document['file_name']));
    }
    else {
      $this->setMessage(JText::_($document['error']), 'warning');
    }

    $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=documents', false));

    return;
  }


  public function removeDocument()
  {
    $post = $this->input->post->getArray();

    if(OdmsHelper::removeFile($post['document_id'])) {
      OdmsHelper::removeDocument($post['document_id']);
      $this->setMessage(JText::_('COM_ODYSSEY_FILE_REMOVED_FROM_SERVER'));
    }

    $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=documents', false));

    return;
  }
}



