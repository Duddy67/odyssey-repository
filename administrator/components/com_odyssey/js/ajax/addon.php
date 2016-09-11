<?php
//Initialize the Joomla framework
define('_JEXEC', 1);
//First we get the number of letters we want to substract from the path.
$length = strlen('/administrator/components/com_odyssey/js');
//Turn the length number into a negative value.
$length = $length - ($length * 2);
//
define('JPATH_BASE', substr(dirname(__DIR__), 0, $length));

//Get the required files
require_once (JPATH_BASE.'/includes/defines.php');
require_once (JPATH_BASE.'/includes/framework.php');
//We need to use Joomla's database class 
require_once (JPATH_BASE.'/libraries/joomla/factory.php');
//Create the application
$mainframe = JFactory::getApplication('site');
$mainframe->initialise();

//Get the required variables.
$addonId = JFactory::getApplication()->input->get->get('addon_id', 0, 'uint');

$db = JFactory::getDbo();
$query = $db->getQuery(true);

//Get data of each item type.
$data = $options = array();

//Get the addon options if any.
$query->select('id AS option_id, ordering AS option_ordering, name AS option_name, published')
      ->from('#__odyssey_addon_option')
      ->where('addon_id='.(int)$addonId)
      ->order('ordering');
$db->setQuery($query);
$options = $db->loadAssocList();

$data['option'] = $options;

echo json_encode($data);

