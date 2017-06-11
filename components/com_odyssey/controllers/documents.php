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
    //Get the jform data.
    $data = $this->input->post->get('jform', array(), 'array');
    $post = $this->input->post->getArray();
    //file_put_contents('debog_file_controller.txt', print_r($_FILES, true)); 
    $document = OdmsHelper::uploadFile('customer');

    $user = JFactory::getUser();
    $userId = $user->get('id');

    $document['item_id'] = $userId;
    $document['item_type'] = 'customer';
    $document['uploaded_by'] = 'customer';
    OdmsHelper::addDocument($document);

    $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=documents', false));
  }


  public function removeDocument()
  {
    $post = $this->input->post->getArray();
    OdmsHelper::removeDocument($post['document_id']);
    //file_put_contents('debog_file.txt', print_r($post['jform'], true)); 
    $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=documents', false));
  }
}



