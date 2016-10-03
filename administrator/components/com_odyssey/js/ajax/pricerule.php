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
require_once (JPATH_BASE.'/administrator/components/com_odyssey/helpers/utility.php');
//Create the application
$mainframe = JFactory::getApplication('site');
$mainframe->initialise();

//Get the required variables.
$pruleId = JFactory::getApplication()->input->get->get('prule_id', 0, 'uint');
$travelId = JFactory::getApplication()->input->get->get('travel_id', 0, 'uint');
$dptStepId = JFactory::getApplication()->input->get->get('dpt_step_id', 0, 'uint');
$pruleType = JFactory::getApplication()->input->get->get('prule_type', '', 'str');
$target = JFactory::getApplication()->input->get->get('target', '', 'str');
$recipient = JFactory::getApplication()->input->get->get('recipient', '', 'str');

$data = $targets = $recipients = $conditions = array();

$db = JFactory::getDbo();
$query = $db->getQuery(true);

//Called from the createPriceRuleTable js function.
if($travelId && $dptStepId) {
  //Note: To take advantage of the combinePriceRows function the "value" field is renamed.
  $query->select('d.dpt_id, d.date_time, d.date_time_2, d.max_passengers, c.name AS city, c.lang_var, p.psgr_nb, p.value AS price')
	->from('#__odyssey_departure_step_map AS d')
	->join('LEFT', '#__odyssey_travel_pricerule AS p ON p.prule_id='.(int)$pruleId.' AND p.dpt_id=d.dpt_id AND p.travel_id='.(int)$travelId.' AND p.dpt_step_id='.(int)$dptStepId)
	->join('LEFT', '#__odyssey_city AS c ON c.id=d.city_id')
	->where('d.step_id='.(int)$dptStepId)
	->order('d.date_time, p.psgr_nb');
  $db->setQuery($query);
  $results = $db->loadAssocList();
  //file_put_contents('debog_file.txt', print_r($query->__toString(), true));

  $travelPriceRuleRows = UtilityHelper::combinePriceRows($results);

  $data['travel_pricerule'] = $travelPriceRuleRows;
  echo json_encode($data);

  return;
}

if($pruleId) {
  //
  $table = '#__usergroups';
  $field = 'u.title AS name';
  if($recipient == 'customer') {
    $table = '#__users';
    $field = 'u.name';
  }

  $query->select('r.item_id AS id,'.$field)
	->from('#__odyssey_prule_recipient AS r')
	->join('LEFT', $table.' AS u ON u.id=r.item_id')
	->where('r.prule_id='.(int)$pruleId);
  $db->setQuery($query);
  $recipients = $db->loadAssocList();


  if($pruleType == 'catalog') {
    if($target == 'travel') {
      $query->clear();
      $query->select('tp.travel_id, tp.dpt_step_id, t.name')
	    ->from('#__odyssey_travel_pricerule AS tp')
	    ->join('LEFT', '#__odyssey_travel AS t ON t.id=tp.travel_id')
	    ->where('tp.prule_id='.(int)$pruleId)
	    ->group('tp.travel_id');
      $db->setQuery($query);
      $targets = $db->loadAssocList();
    }
    else { //travel_cat, addon, addon_option
      $table = '#__categories';
      $field = 'title AS name';
      if($target == 'addon' || $target == 'addon_option') {
	$table = '#__odyssey_'.$target;
	$field = 'name';
      }

      $query->clear();
      $query->select('t.item_id AS id, t.psgr_nbs, t.dpt_nbs, t.travel_ids, t.step_ids, i.'.$field)
	    ->from('#__odyssey_prule_target AS t')
	    ->join('LEFT', $table.' AS i ON i.id=t.item_id');

      //Get the name of the parent addon.
      if($target == 'addon_option') {
	$query->select('a.name AS parent_addon')
	      ->join('LEFT', '#__odyssey_addon AS a ON a.id=i.addon_id');
      }

      $query->where('t.prule_id='.(int)$pruleId);
      $db->setQuery($query);
      $targets = $db->loadAssocList();

      //Add the name of the parent addon after the option name.
      if($target == 'addon_option') {
	foreach($targets as $key => $item) {
	  $targets[$key]['name'] = $item['name'].' ('.$item['parent_addon'].')';
	}
      }
    }
  }
  else { //cart
      $table = '#__categories';
      $field = 'title AS name';
      if($target == 'travel') {
	$table = '#__odyssey_travel';
	$field = 'name';
      }

      $query->clear();
      $query->select('c.item_id AS id, c.operator, c.item_amount, c.item_qty, i.'.$field)
	    ->from('#__odyssey_prule_condition AS c')
	    ->join('LEFT', $table.' AS i ON i.id=c.item_id')
	    ->where('c.prule_id='.(int)$pruleId);
      $db->setQuery($query);
      $conditions = $db->loadAssocList();
  }
}

$data['recipient'] = $recipients;
$data['target'] = $targets;
$data['condition'] = $conditions;

echo json_encode($data);



