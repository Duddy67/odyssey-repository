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
require_once (JPATH_BASE.'/administrator/components/com_odyssey/helpers/step.php');
//Create the application
$mainframe = JFactory::getApplication('site');
$mainframe->initialise();

//Get the required variables.
$stepId = JFactory::getApplication()->input->get->get('step_id', 0, 'uint');
$dptStepId = JFactory::getApplication()->input->get->get('dpt_step_id', 0, 'uint');
$langTag = JFactory::getApplication()->input->get->get('lang_tag', '', 'str');

$db = JFactory::getDbo();
$query = $db->getQuery(true);
$query->select('d.dpt_id, d.date_time, d.date_time_2, IFNULL(tg.time_gap, "") AS time_gap, IFNULL(tg.dpt_id, "") AS selected,'.
               'IFNULL(tg.group_prev, "") AS group_prev, c.name AS city, c.lang_var')
      ->from('#__odyssey_departure_step_map AS d')
      ->join('LEFT', '#__odyssey_timegap_step_map AS tg ON tg.dpt_id=d.dpt_id AND tg.step_id='.(int)$stepId)
      ->join('LEFT', '#__odyssey_city AS c ON c.id=d.city_id')
      ->where('d.step_id='.(int)$dptStepId)
      ->order('d.date_time');
$db->setQuery($query);
$departures = $db->loadAssocList();

//In order to work with JText we have to load the language.
//Note: As we load language from an external file the site language cannot be properly
//identified and we end up with the en-GB tag by default.
$lang = JFactory::getLanguage();
//Check the lang tag parameter has been properly retrieved.
if(empty($langTag)) {
  //If not, we'll use english by default.
  $langTag = $lang->getTag();
}
//Load language.
$lang->load('com_odyssey', JPATH_ROOT.'/components/com_odyssey', $langTag);

foreach($departures as $key => $departure) {
  //Set days hours and minutes values by default.
  $departures[$key]['days'] = '0';
  $departures[$key]['hr_mn'] = '00:00'; 

  if(!empty($departure['time_gap'])) {
    $result = StepHelper::getDaysHoursMinutes($departure['time_gap']);
    $departures[$key]['days'] = $result['days'];
    $departures[$key]['hr_mn'] = $result['hours'].':'.$result['minutes'];
  }

  //If a language variable is set we use it.
  if(!empty($departure['lang_var'])) {
    $departures[$key]['city'] = JText::_($departure['lang_var']);
  }

  //Remove the useless variables.
  unset($departures[$key]['lang_var']);
  unset($departures[$key]['time_gap']);
}

echo json_encode($departures);

