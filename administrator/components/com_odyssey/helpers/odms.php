<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.


class OdmsHelper
{

  /**
   * Upload the file on the server.
   *
   * @param string  Name of the folder in which the file will be stored.
   *
   * @return array  An array containing the document data.
   */
  public static function uploadFile($folderName)
  {
    //Array to store the file data. Set an error index for a possible error message.
    $document = array('error' => '');

    $jinput = JFactory::getApplication()->input;
    $files = $jinput->files->get('jform');

    //In front-end we don't use jform so we have to get files in the regular post form.
    if($files === null) {
      $files = $jinput->files->getArray();
    }

    $files = $files['uploaded_file'];

    //Get the component parameters:
    $params = JComponentHelper::getParams('com_odyssey');
    //- The allowed extensions table
    $allowedExt = explode(';', $params->get('allowed_extensions'));
    //- The authorised file size (in megabyte) for upload. 
    $maxFileSize = $params->get('max_file_size');
    //Convert in byte. 
    $maxFileSize = $maxFileSize * 1048576;

    //Check if the file exists and if no error occurs.
    if($files['error'] == 0) {
      //Get the file extension and convert it to lowercase.
      $ext = strtolower(JFile::getExt($files['name']));

      //Check if the extension is allowed.
      if(!in_array($ext, $allowedExt)) {
	$document['error'] = 'COM_ODYSSEY_EXTENSION_NOT_ALLOWED';
	return $document;
      }

      //Check the size of the file.
      if($files['size'] > $maxFileSize) {
	$document['error'] = 'COM_ODYSSEY_FILE_SIZE_TOO_LARGE';
	return $document;
      }

      $db = JFactory::getDbo();
      $query = $db->getQuery(true);

      $count = 1;
      while($count > 0) {
	//Create an unique id for this file.
	$file = uniqid();
	$file = $file.'.'.$ext;

	//To ensure it is unique check against the database.
	//If the id is not unique the loop goes on and a new id is generated.
	$query->clear();
	$query->select('COUNT(*)');
	$query->from('#__odyssey_document');
	$query->where('file='.$db->Quote($file));
	$db->setQuery($query);
	$count = (int)$db->loadResult();
      }

      //Get the file name without its extension.
      preg_match('#(.+)\.[a-zA-Z0-9\#?!$~@()-_]{1,}$#', $files['name'], $matches);
      $fileName = $matches[1];

      //Sanitize the file name which will be used for downloading, (see stringURLSafe function for details).
      $fileName = JFilterOutput::stringURLSafe($fileName);

      //Note: So far the document root directory is unchangeable but who knows in a futur version..
      $docRootDir = 'odms';

      //Create a table containing all data about the file.
      $document['file'] = $file;
      $document['file_name'] = $fileName.'.'.$ext;
      $document['file_type'] = $files['type'];
      $document['file_size'] = $files['size'];
      //Build the file path.
      $document['file_path'] = $docRootDir.'/'.$folderName;

      //Move the file on the server.
      if(!JFile::upload($files['tmp_name'], JPATH_ROOT.'/'.$docRootDir.'/'.$folderName.'/'.$document['file'])) {
	$document['error'] = 'COM_ODYSSEY_FILE_TRANSFER_ERROR';
	return $document;
      }

      //File transfert has been successful.
      return $document;
    }
    else { //The upload of the file has failed.
      //Return the error which has occured.
      switch ($files['error']) { 
        case 1:
	  $document['error'] = 'COM_ODYSSEY_FILES_ERROR_1';
	  break;
	case 2:
	  $document['error'] = 'COM_ODYSSEY_FILES_ERROR_2';
	  break;
	case 3:
	  $document['error'] = 'COM_ODYSSEY_FILES_ERROR_3';
	  break;
	case 4:
	  $document['error'] = 'COM_ODYSSEY_FILES_ERROR_4';
	  break;
      }

      return $document;
    }
  }


  /**
   * Removes a given file from the server.
   *
   * @param integer  The id of the document linked to the file to remove.
   *
   * @return boolean  False if the deletion failed, true otherwise.
   */
  public static function removeFile($documentId)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('file, file_path');
    $query->from('#__odyssey_document');
    $query->where('id='.(int)$documentId);
    $db->setQuery($query);
    $document = $db->loadObject();

    //Remove the file from the server or generate an error message in case of failure.
    //Warning: Don't ever use the JFile delete function cause if a problem occurs with
    //the file, the returned value is undefined (nor boolean or whatever). 
    //Stick to the unlink PHP function which is safer.
    if(!unlink(JPATH_ROOT.'/'.$document->file_path.'/'.$document->file)) {
      JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_ODYSSEY_FILE_COULD_NOT_BE_DELETED', $document->file), 'error');
      return false;
    }

    return true;
  }


  /**
   * Adds a document in the table.
   *
   * @param array  An array containing the document data.
   *
   * @return void
   */
  public static function addDocument($document)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $now = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);

    $columns = array('item_id', 'item_type', 'file', 'file_name',
		     'file_type', 'file_size', 'file_path',
		     'uploaded_by', 'created');

    $values = array($document['item_id'], 
		    $db->Quote($document['item_type']), 
		    $db->Quote($document['file']), 
		    $db->Quote($document['file_name']), 
		    $db->Quote($document['file_type']), 
		    $db->Quote($document['file_size']), 
		    $db->Quote($document['file_path']), 
		    $db->Quote($document['uploaded_by']), 
		    $db->Quote($now));

    $query->insert('#__odyssey_document')
	  ->columns($columns)
	  ->values(implode(',', $values));
    $db->setQuery($query);
    $db->execute();
  }


  /**
   * Deletes a document data row from the table.
   *
   * @param integer  The id of the document to remove.
   *
   * @return void
   */
  public static function removeDocument($documentId)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->delete('#__odyssey_document')
	  ->where('id='.(int)$documentId);
    $db->setQuery($query);
    $db->execute();
  }


  /**
   * Builds the rendering of the document tables.
   *
   * @param integer The id of the item the documents are linked to.
   * @param string  The type of the item (customer or travel).
   * @param boolean False if the function is called from the front-end, true otherwise.  
   *
   * @return string  The rendering of the document tables.
   */
  public static function renderDocuments($itemId, $itemType, $isSite = false)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('*')
	  ->from('#__odyssey_document')
	  ->where('item_id='.(int)$itemId.' AND item_type='.$db->Quote($itemType))
	  ->order('uploaded_by');
    $db->setQuery($query);
    $documents = $db->loadObjectList();

    $sent = $received = '';
    $uri = JUri::getInstance();

    //Build the document tables according to the uploading location (ie: front-end or back-end).
    foreach($documents as $document) {
      //Create link to the document and button to remove it.
      $link = '<a href="'.$uri->root().'odms/download.php?id='.$document->id.'">'.$document->file_name.'</a>';
      $removeButton = '<button onclick="removeDocument('.$document->id.');" '.
	              'id="remove-'.$document->id.'" class="btn btn-small">'.
	              '<span class="icon-trash icon-white"></span>'.JText::_('COM_ODYSSEY_BUTTON_REMOVE_LABEL').'</button>';

      if($isSite) { //We're in front-end. Customer is the uploader.
	if($document->uploaded_by == 'customer') {
	  $sent .= '<tr><td>'.$link.'</td><td>'.$removeButton.'</td></tr>';
	}
	else {
	  $received .= '<tr><td>'.$link.'</td></tr>';
	}
      }
      else { //We're in back-end. Admin is the uploader.
	if($document->uploaded_by == 'admin') {
	  $sent .= '<tr><td>'.$link.'</td><td>'.$removeButton.'</td></tr>';
	}
	else {
	  $received .= '<tr><td>'.$link.'</td></tr>';
	}
      }
    }

    if(!empty($sent)) {
      $sent = '<table class="table">'.$sent.'</table>';
    }

    if(!empty($received)) {
      $received = '<table class="table">'.$received.'</table>';
    }

    $html = array('sent' => $sent, 'received' => $received);

    return $html;
  }
}

