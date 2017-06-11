<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

//Since this file is called directly and it doesn't belong to any component, 
//module or plugin, we need first to initialize the Joomla framework in order to use 
//the Joomla API methods.
 
//Initialize the Joomla framework
define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__));
//Get the required files
require_once (JPATH_BASE.'/includes/defines.php');
require_once (JPATH_BASE.'/includes/framework.php');
//We need to use Joomla's database class 
require_once (JPATH_BASE.'/libraries/joomla/factory.php');
//Create the application
$mainframe = JFactory::getApplication('site');

//Get the id number passed through the url.
$jinput = JFactory::getApplication()->input;
$id = $jinput->get('id', 0, 'uint');

if($id) {
  //Retrieve some data from the document. 
  $db = JFactory::getDbo();
  $query = $db->getQuery(true);
  $query->select('file,file_path,file_name,file_type,file_size')
	->from('#__odyssey_document')
	->where('id='.(int)$id);
  $db->setQuery($query);
  $document = $db->loadObject();

  if($document->file_path) {
    //Build the path to the file.
    $download = JPATH_BASE.'/'.$document->file_path.'/'.$document->file;

    if(file_exists($download) === false) {
      echo 'The file cannot be found.';
      return;
    }

    header('Content-Description: File Transfer');
    header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');   // Date in the past
    header('Content-type: '.$document->file_type);
    header('Content-Transfer-Encoding: binary');
    header('Content-length: '.$document->file_size);
    header("Content-Disposition: attachment; filename=\"".$document->file_name."\"");
    ob_clean();
    flush();
    readfile($download);

    exit;
  } 
  else { //The document url is empty.
    echo 'Wrong document url.';
    return;
  }
}
else { //The document id is unset.
  echo 'The document doesn\'t exist.';
}


?>
