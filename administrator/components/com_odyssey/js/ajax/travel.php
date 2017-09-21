<?php

//Initialize the Joomla framework
define('_JEXEC', 1);
//First we get the number of letters we want to substract from the path.
$length = strlen('/administrator/components/com_odyssey/js');
//Turn the length number into a negative value.
$length = $length - ($length * 2);
//
define('JPATH_BASE', substr(dirname(__DIR__), 0, $length));
require_once (JPATH_BASE.'/administrator/components/com_odyssey/helpers/utility.php');

//Get the required files
require_once (JPATH_BASE.'/includes/defines.php');
require_once (JPATH_BASE.'/includes/framework.php');
//We need to use Joomla's database class 
require_once (JPATH_BASE.'/'.UtilityHelper::getFactoryFilePath());
//Create the application
$mainframe = JFactory::getApplication('site');
$mainframe->initialise();

//Get the required variables.
$travelId = JFactory::getApplication()->input->get->get('travel_id', 0, 'uint');
//$dptStepId = JFactory::getApplication()->input->get->get('dpt_step_id', 0, 'uint');

//Create mapping between search filter and column names.
$filters = array('country' => 'country_code', 'region' => 'region_code', 'city' => 'city_id');

$data = array();

$db = JFactory::getDbo();
$query = $db->getQuery(true);

//Run a query for each filter type. It allows to get the result properly ordered according
//to the type.
foreach($filters as $filter => $column) {
  $query->clear()
	->select($column)
	->from('#__odyssey_search_filter')
	->where('travel_id='.(int)$travelId)
	->where($column.' IS NOT NULL')
	->order($column);
  $db->setQuery($query);
  $results = $db->loadColumn();

  $data[$filter] = $results;
}

//Get images linked to the travel.
$query->clear();
$query->select('src, width, height, alt, ordering') 
      ->from('#__odyssey_travel_image')
      ->where('travel_id='.$travelId)
      ->order('ordering');
$db->setQuery($query);
$images = $db->loadAssocList();

//Add "../" to the path of each image as we are in the administrator area.
foreach($images as $key => $image) {
  $image['src'] = '../'.$image['src'];
  $images[$key] = $image;
}

$data['image'] = $images;


echo json_encode($data);

