<?php

//Initialize the Joomla framework
define('_JEXEC', 1);
//First we get the number of letters we want to substract from the path.
$length = strlen('/administrator/components/com_odyssey/js');
//Turn the length number into a negative value.
$length = $length - ($length * 2);
//
define('JPATH_BASE', substr(dirname(__DIR__), 0, $length));
define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_BASE.'/administrator/components/com_odyssey');
//JPATH_COMPONENT variable must also be difined as it is used in the step.php file.
define('JPATH_COMPONENT', JPATH_COMPONENT_ADMINISTRATOR);
require_once (JPATH_BASE.'/administrator/components/com_odyssey/helpers/utility.php');

//Get the required files
require_once (JPATH_BASE.'/includes/defines.php');
require_once (JPATH_BASE.'/includes/framework.php');
//We need to use Joomla's database class 
require_once (JPATH_BASE.'/'.UtilityHelper::getFactoryFilePath());
//We need to access to both travel and step table classes.
require_once (JPATH_COMPONENT_ADMINISTRATOR.'/tables/travel.php');
require_once (JPATH_COMPONENT_ADMINISTRATOR.'/tables/step.php');
//Create the application
$mainframe = JFactory::getApplication('site');
$mainframe->initialise();

//The aim of this Ajax script is to simulate the setting of the item alias. 
//This avoid the users to loose the dynamical items they've just set in case of
//unique (group) alias error.

//Get the required variables.
$task = JFactory::getApplication()->input->get->get('task', '', 'string');
$id = JFactory::getApplication()->input->get->get('id', 0, 'uint');
$catid = JFactory::getApplication()->input->get->get('catid', 0, 'uint');
$itemType = JFactory::getApplication()->input->get->get('item_type', '', 'string');
$name = JFactory::getApplication()->input->get->get('name', '', 'string');
$alias = JFactory::getApplication()->input->get->get('alias', '', 'string');
$code = JFactory::getApplication()->input->get->get('code', '', 'string');

$checking = 1;

//Set table and item names according to the item type.
//$tableName = '#__odyssey_travel';
$itemName = 'Travel';
if($itemType == 'step') {
  //$tableName = '#__odyssey_step';
  $itemName = 'Step';
}

//Note: name and alias variables have previously been encoded with the encodeURIComponent javascript function.
$name = urldecode($name);
$alias = urldecode($alias);

/*if($task == 'travel.save2copy' || $task == 'step.save2copy') {
  //Get the name of the original item.
  $db = JFactory::getDbo();
  $query = $db->getQuery(true);
  $query->select('name')
	->from($tableName)
	->where('id='.(int)$id);
  $db->setQuery($query);
  $origName = $db->loadResult();

  //The name is untouched. We can leave as it will be safely incremented later in the model. 
  if($name == $origName) {
    echo json_encode($checking);
    return;
  }
  //The name is different so we reset the alias and start testing.
  else {
    $alias = '';
  }
}*/

//Run the simulation.

//Created a sanitized alias, (see stringURLSafe function for details).
$alias = JFilterOutput::stringURLSafe($alias);

//In case no alias has been defined, create a sanitized alias from the name field.
if(empty($alias)) {
  $alias = JFilterOutput::stringURLSafe($name);
}

$attributes = array('alias' => $alias, 'catid' => $catid);
$field = 'alias';
//Change the attributes to check according to the item type.
if($itemType == 'step') {
  $attributes = array('group_alias' => $alias, 'step_type' => 'departure');
}

$result = array('checking' => $checking, 'value' => $alias, 'field' => $field);

// Verify that the alias is unique
$table = JTable::getInstance($itemName, 'OdysseyTable');
if($table->load($attributes) && ($table->id != $id || $id == 0)) {
  $result['checking'] = 0;
}

if($itemType == 'step') {
  // Verify also that the code (if any) is unique
  $code = preg_replace('/\s+/', '', $code);
  if(!empty($code) && $table->load(array('code' => $code)) && ($table->id != $id || $id == 0)) {
    $result['checking'] = 0;
    $result['field'] = 'code';
    $result['value'] = $code;
  }
}

echo json_encode($result);

