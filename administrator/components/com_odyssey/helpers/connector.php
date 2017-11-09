<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.
require_once JPATH_ADMINISTRATOR.'/components/com_odyssey/helpers/odyssey.php';
//require_once JPATH_ADMINISTRATOR.'/components/com_odyssey/helpers/odyssey.php';


class ConnectorHelper
{
  public static function createItem($itemType, $data)
  {
    JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_odyssey/tables');
    $table = JTable::getInstance($itemType, 'OdysseyTable', array());

    // Bind data
    if(!$table->bind($data)) {
      JFactory::getApplication()->enqueueMessage(JText::sprintf('PLG_ODYSSEYAPICONNECTOR_API_ERROR',
								$table->getError(), 'error'), 'error');
      return false;
    }

    // Check the data.
    if(!$table->check()) {
      JFactory::getApplication()->enqueueMessage(JText::sprintf('PLG_ODYSSEYAPICONNECTOR_API_ERROR',
								$table->getError(), 'error'), 'error');
      return false;
    }

    // Store the data.
    if(!$table->store()) {
      $table->getError();
      JFactory::getApplication()->enqueueMessage(JText::sprintf('PLG_ODYSSEYAPICONNECTOR_API_ERROR',
								$table->getError(), 'error'), 'error');
      return false;
    }

    //Returns the last inserted id.
    return $table->id;
  }


  public static function createDepartureItem($data)
  {
    //Get the existing departure ids for the given departure step.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('dpt_id')
	  ->from('#__odyssey_departure_step_map')
	  ->where('step_id='.(int)$data['step_id'])
	  //Reverses the order so that we can get the highest id from the first array
	  //index (ie: $array[0]) 
          ->order('dpt_id DESC');
    $db->setQuery($query);
    try {
      $dptIds = $db->loadColumn();
    }
    catch(RuntimeException $e) {
      JFactory::getApplication()->enqueueMessage(JText::sprintf('PLG_ODYSSEYAPICONNECTOR_API_ERROR',
								$e->getMessage(), 'error'), 'error');
      return false;
    }

    //Compute a valid departure id.

    //There is no departure yet in this step.
    if(empty($dptIds)) {
      $newDptId = 1;
    }
    else {
      //Set the new departure id (ie: the highest existing id plus one).
      $newDptId = $dptIds[0] + 1;
    }

    $data['dpt_id'] = $newDptId;

    //Set fields.
    $columns = array('step_id','city_id','dpt_id','date_time','date_time_2','max_passengers',
	             'nb_days','nb_nights','code','published','allotment','altm_subtract');

    $query->clear();
    $query->insert('#__odyssey_departure_step_map')
	  ->columns($columns)
	  ->values(implode(',', $data));
    $db->setQuery($query);
    try {
      $db->execute();
    }
    catch(RuntimeException $e) {
      JFactory::getApplication()->enqueueMessage(JText::sprintf('PLG_ODYSSEYAPICONNECTOR_API_ERROR',
								$e->getMessage(), 'error'), 'error');
      return false;
    }

    return $newDptId;
  }


  public static function getItems($table, $columns, $itemIds = array(), $where = '')
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select($columns)
	  ->from($table);

    if(!empty($itemIds)) {
      $query->where('id IN('.implode(',', $itemIds).')');
    }

    if(!empty($where)) {
      $query->where($where);
    }

    $db->setQuery($query);

    return $db->loadAssocList();
  }


  public static function getChildItems($table, $parents, $foreignKey, $itemType = 'item_type', $embedded = false)
  {
    //Note: The "parents" argument can be either an array of parent ids or a multidimensional 
    //array of parents.
    $parentIds = array();

    //Checks whether the array is multidimensional.
    if(is_array($parents[0])) {
      foreach($parents as $key => $parent) {
        //Collects the parent id.
	$parentIds[] = $parent['id'];
	//Used with the possible "embedded" option.
	$parents[$key][$itemType] = array();
      }
    }
    else {
      $parentIds = $parents;
    }

    //Gets the child items for each parents.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('*')
	  ->from($table)
	  ->where($foreignKey.' IN('.implode(',', $parentIds).')')
	  ->order($foreignKey);    
    $db->setQuery($query);
    $childItems = $db->loadAssocList();

    //Embedds child items in their corresponding parent.
    if($embedded && is_array($parents[0])) {
      foreach($parents as $key => $parent) {
	foreach($childItems as $childItem) {
	  if($childItem[$foreignKey] == $parent['id']) {
	    $parents[$key][$itemType][] = $childItem;
	  }
	}
      }

      return $parents;
    }

    return $childItems;
  }


  public static function addMappingRows($table, $columns, $values)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->insert($table)
	  ->columns($columns)
	  ->values($values);
    $db->setQuery($query);
    try {
      $db->execute();
    }
    catch(RuntimeException $e) {
      JFactory::getApplication()->enqueueMessage(JText::sprintf('PLG_ODYSSEYAPICONNECTOR_API_ERROR',
								$e->getMessage(), 'error'), 'error');
      return false;
    }

    return true;
  }


  public static function getUserByEmail($userEmail)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('id, name, username, email, registerDate, lastvisitDate')
	  ->from('#__users')
	  ->where('email='.$db->quote($userEmail));
    $db->setQuery($query);

    return $db->loadObject();
  }


  public static function getCityIdMap()
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('id, code')
	  ->from('#__odyssey_city')
	  ->where(1);
    $db->setQuery($query);
    $cities = $db->loadObjectList();

    $cityIdMap = array();
    foreach($cities as $city) {
      if(!empty($city->code)) {
	$cityIdMap[$city->code] = $city->id;
      }
    }

    return $cityIdMap;
  }


  public static function getItemByCode($items, $code, $codeField = 'code')
  {
    foreach($items as $item) {
      if($item[$codeField] == $code) {
	return $item;
      }
    }

    return null;
  }


  public static function getCategoryId()
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('id')
	  ->from('#__categories')
	  ->where('extension="com_odyssey" AND level=1')
	  ->order('created_time')
	  ->setLimit(1);
    $db->setQuery($query);

    return $db->loadResult();
  }


  public static function getUniqueGroupAlias($groupAlias)
  {
    $index = 1;
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    do {
      if($index > 1) {
        $groupAlias = $groupAlias.'-'.$index; 
      }

      $query->clear();
      $query->select('COUNT(*)')
	    ->from('#__odyssey_step')
	    ->where('group_alias='.$db->quote($groupAlias));
      $db->setQuery($query);
      $result = $db->loadResult();

      $index++;
    }
    while($result > 0);

    return $groupAlias;
  }


  public static function isCodeUnique($code, $itemType, $where = '')
  {
    $tables = array('step' => '#__odyssey_step', 
		    'departure' => '#__odyssey_departure_step_map',
		    'addon' => '#__odyssey_addon',
		    'addon_option' => '#__odyssey_addon_option',
		    'city' => '#__odyssey_city');

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('COUNT(*)')
	  ->from($tables[$itemType]);

    if(!empty($where)) {
      $query->where($where);
    }

    $query->where('code='.$db->quote($code));
    $db->setQuery($query);

    //The given code has been found in the item table.
    if((int)$db->loadResult()) {
      //The given code is not unique.
      return false;
    }

    return true;
  }
}

