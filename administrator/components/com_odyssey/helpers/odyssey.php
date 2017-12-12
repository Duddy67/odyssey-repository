<?php
/**
 * @package Odyssey component
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.


/**
 * Odyssey component helper.
 *
 */
class OdysseyHelper
{
  /**
   * Create the tabs bar ($viewName = name of the active view).
   *
   * @param string  The name of the view to display.
   *
   * @return void
   */
  public static function addSubmenu($viewName)
  {
    JHtmlSidebar::addEntry(JText::_('COM_ODYSSEY_SUBMENU_TRAVELS'),
				      'index.php?option=com_odyssey&view=travels', $viewName == 'travels');

    JHtmlSidebar::addEntry(JText::_('COM_ODYSSEY_SUBMENU_STEPS'),
				      'index.php?option=com_odyssey&view=steps', $viewName == 'steps');

    JHtmlSidebar::addEntry(JText::_('COM_ODYSSEY_SUBMENU_ADDONS'),
				      'index.php?option=com_odyssey&view=addons', $viewName == 'addons');

    JHtmlSidebar::addEntry(JText::_('COM_ODYSSEY_SUBMENU_PRICERULES'),
				      'index.php?option=com_odyssey&view=pricerules', $viewName == 'pricerules');

    JHtmlSidebar::addEntry(JText::_('COM_ODYSSEY_SUBMENU_COUPONS'),
				      'index.php?option=com_odyssey&view=coupons', $viewName == 'coupons');

    JHtmlSidebar::addEntry(JText::_('COM_ODYSSEY_SUBMENU_CUSTOMERS'),
				      'index.php?option=com_odyssey&view=customers', $viewName == 'customers');

    JHtmlSidebar::addEntry(JText::_('COM_ODYSSEY_SUBMENU_ORDERS'),
				      'index.php?option=com_odyssey&view=orders', $viewName == 'orders');

    JHtmlSidebar::addEntry(JText::_('COM_ODYSSEY_SUBMENU_CITIES'),
				      'index.php?option=com_odyssey&view=cities', $viewName == 'cities');

    JHtmlSidebar::addEntry(JText::_('COM_ODYSSEY_SUBMENU_COUNTRIES'),
				      'index.php?option=com_odyssey&view=countries', $viewName == 'countries');

    JHtmlSidebar::addEntry(JText::_('COM_ODYSSEY_SUBMENU_CURRENCIES'),
				      'index.php?option=com_odyssey&view=currencies', $viewName == 'currencies');

    JHtmlSidebar::addEntry(JText::_('COM_ODYSSEY_SUBMENU_PAYMENT_MODES'),
				      'index.php?option=com_odyssey&view=paymentmodes', $viewName == 'paymentmodes');

    JHtmlSidebar::addEntry(JText::_('COM_ODYSSEY_SUBMENU_TAXES'),
				      'index.php?option=com_odyssey&view=taxes', $viewName == 'taxes');

    JHtmlSidebar::addEntry(JText::_('COM_ODYSSEY_SUBMENU_TESTIMONIES'),
				      'index.php?option=com_odyssey&view=testimonies', $viewName == 'testimonies');

    JHtmlSidebar::addEntry(JText::_('COM_ODYSSEY_SUBMENU_CATEGORIES'),
				      'index.php?option=com_categories&extension=com_odyssey', $viewName == 'categories');

    if($viewName == 'categories') {
      $document = JFactory::getDocument();
      $document->setTitle(JText::_('COM_ODYSSEY_ADMINISTRATION_CATEGORIES'));
    }
  }

  
  /**
   * Get the list of the allowed actions for the user.
   *
   * @param array  The ids of the categories to check.
   *
   * @return JObject
   */
  public static function getActions($catIds = array())
  {
    $user = JFactory::getUser();
    $result = new JObject;

    $actions = array('core.admin', 'core.manage', 'core.create', 'core.edit',
		     'core.edit.own', 'core.edit.state', 'core.delete');

    //Get from the core the user's permission for each action.
    foreach($actions as $action) {
      //Check permissions against the component. 
      if(empty($catIds)) { 
	$result->set($action, $user->authorise($action, 'com_odyssey'));
      }
      else {
	//Check permissions against the component categories.
	foreach($catIds as $catId) {
	  if($user->authorise($action, 'com_odyssey.category.'.$catId)) {
	    $result->set($action, $user->authorise($action, 'com_odyssey.category.'.$catId));
	    break;
	  }

	  $result->set($action, $user->authorise($action, 'com_odyssey.category.'.$catId));
	}
      }
    }

    return $result;
  }


  
  /**
   * Build the user list for the filter.
   *
   *
   * @param string  Name of the item to check.
   *
   * @return mixed  An array of the result set rows or null if no result found.
   */
  public static function getUsers($itemName)
  {
    // Create a new query object.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('u.id AS value, u.name AS text');
    $query->from('#__users AS u');
    //Get only the names of users who have created items, this avoids to
    //display all of the users in the drop down list.
    $query->join('INNER', '#__odyssey_'.$itemName.' AS i ON i.created_by = u.id');
    $query->group('u.id');
    $query->order('u.name');

    // Setup the query
    $db->setQuery($query);

    // Return the result
    return $db->loadObjectList();
  }


  /**
   * Method used to check if travels have to be ordered or displayed according to the tag 
   * mapping table ordering.
   *
   * @param string  Name of the filter to check.
   * @param boolean  Flag to specify we want to check the filter is the only one to
   *        be selected. 
   * @param string  Value of the filter to check.
   *
   * @return boolean  True if filter matches the checking conditions, false otherwise.
   */
  public static function checkSelectedFilter($filterName, $unique = false, $value = '')
  {
    $post = JFactory::getApplication()->input->post->getArray();

    //Ensure the given filter has been selected.
    if(isset($post['filter'][$filterName]) && !empty($post['filter'][$filterName])) {
      //Ensure that only the given filter has been selected.
      if($unique) {
	$filter = 0;
	foreach($post['filter'] as $value) {
	  if(!empty($value)) {
	    $filter++;
	  }
	}

	if($filter > 1) {
	  return false;
	}
      }

      if(!empty($value) && $post['filter'][$filterName] !== $value) {
	return false;
      }

      return true;
    }

    return false;
  }


  /**
   * Orders a set of items which have the same tag. Ordering is stored in an  
   * alternative ordering table.
   *
   * @param array  An array of primary key ids.
   * @param integer  The id of the tag against which we want to order the items.
   * @param integer  Offset from start.
   *
   * @return boolean  True if successful, false otherwise.
   */
  public static function mappingTableOrder($pks, $tagId, $limitStart)
  {
    //Check first the user can edit state.
    $user = JFactory::getUser();
    if(!$user->authorise('core.edit.state', 'com_odyssey')) {
      return false;
    }

    //Start ordering from 1 by default.
    $ordering = 1;

    //When pagination is used set ordering from limitstart value.
    if($limitStart) {
      $ordering = (int)$limitStart + 1;
    }

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Update the ordering values of the mapping table. 
    foreach($pks as $pk) {
      $query->clear();
      $query->update('#__odyssey_travel_tag_map')
	    //Update the item ordering via the mapping table.
	    ->set('ordering='.$ordering)
	    ->where('travel_id='.(int)$pk)
	    ->where('tag_id='.(int)$tagId);
      $db->setQuery($query);
      $db->execute();

      $ordering++;
    }

    return true;
  }


  /**
   * Update a mapping table according to the variables passed as arguments.
   *
   * @param string  The name of the table to update (eg: #__table_name).
   * @param array  Array of table's column, (primary key name must be set as the first array's element).
   * @param array  Array of JObject containing the column values, (values order must match the column order).
   * @param array  Array containing the ids of the items to update.
   * @param string Extra WHERE clause.
   *
   * @return void
   */
  public static function updateMappingTable($table, $columns, $data, $ids, $where = '')
  {
    //Ensure we have a valid primary key.
    if(isset($columns[0]) && !empty($columns[0])) {
      $pk = $columns[0];
    }
    else {
      return;
    }

    // Create a new query object.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Delete all the previous items linked to the primary id(s).
    $query->delete($db->quoteName($table));
    $query->where($pk.' IN('.implode(',', $ids).')');

    if(!empty($where)) {
      $query->where($where);
    }

    $db->setQuery($query);
    $db->execute();

    //If no item has been defined no need to go further. 
    if(count($data)) {
      //List of the numerical fields (no quotes must be used).
      $integers = array('id','item_id','travel_id','step_id','dpt_id',
	                'addon_id','addon_option_id','dpt_step_id','max_passengers',
			'group_prev','city_id','prule_id','psgr_nb','price','ordering',
			'altm_subtract', 'altm_locked', 'nb_persons');

      //Build the VALUES clause of the INSERT MySQL query.
      $values = array();
      foreach($ids as $id) {
	foreach($data as $itemValues) {
	  //Set the primary id to link the item with.
	  $row = $id.',';

	  foreach($itemValues as $key => $value) {
	    //Handle the null value.
	    if($value === null) {
	      $row .= 'NULL,';
	    }
	    //No numerical values must be quoted.
	    elseif(in_array($key, $integers)) {
	      $row .= $value.',';
	    }
	    else { //Quote the other value types.
	      $row .= $db->Quote($value).',';
	    }
	  }

	  //Remove comma from the end of the string.
	  $row = substr($row, 0, -1);
	  //Insert a new row in the "values" clause.
	  $values[] = $row;
	}
      }

      //Insert a new row for each item linked to the primary id(s).
      $query->clear();
      $query->insert($db->quoteName($table));
      $query->columns($columns);
      $query->values($values);
      $db->setQuery($query);
      $db->execute();
    }

    return;
  }


  /**
   * Checking function used to prevent an admin to delete (or change status) the items 
   * which are currently linked to other items.
   *
   * @param integer  The id of the item.
   * @param string  The type of the item.
   *
   * @return integer
   */
  public static function isInTravel($itemId, $itemType = 'departure_step')
  {
    $result = 0;
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    if($itemType == 'departure_step') {
      $query->select('COUNT(*)')
	    ->from('#__odyssey_travel')
	    ->where('dpt_step_id='.(int)$itemId);
      $db->setQuery($query);
      $result = $db->loadResult();
    }

    return $result;
  }


  /**
   * Checking function used to prevent an admin to delete (or change status) the items 
   * which are currently linked to other items.
   *
   * @param integer  The id of the item.
   * @param string  The type of the item.
   *
   * @return integer
   */
  public static function isInStep($itemId, $itemType)
  {
    $result = 0;
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    if($itemType == 'addon') {
      $query->select('COUNT(*)')
	    ->from('#__odyssey_step_addon_map')
	    ->where('addon_id='.(int)$itemId);
      $db->setQuery($query);
      //Note: The figure returned doesn't correspond to the number of steps in which the
      //given addon is in.
      $result = $db->loadResult();
    }

    if($itemType == 'city') {
      //Check if the given city is linked to one or more steps. 
      $subQueries = '(SELECT COUNT(*) FROM #__odyssey_departure_step_map WHERE city_id='.(int)$itemId.') AS table1Count,'.
		    '(SELECT COUNT(*) FROM #__odyssey_step_city_map WHERE city_id='.(int)$itemId.') AS table2Count,'.
		    //Note: The figure returned doesn't correspond to the number of steps in which the given addon is in.
		    '(SELECT COUNT(*) FROM #__odyssey_step_transit_city_map WHERE city_id='.(int)$itemId.') AS table3Count';
      $query->select($subQueries);
      $db->setQuery($query);
      $results = $db->loadAssoc();

      $result = $results['table1Count'] + $results['table2Count'] + $results['table3Count']; 
    }

    return $result;
  }


  /**
   * Checking function used to prevent an admin to delete (or change status) the items 
   * which are currently linked to other items.
   *
   * @param string  The alphanumeric code (2 letters) of the country the city belongs to.
   *
   * @return integer
   */
  public static function isInCity($alpha2)
  {
    $result = 0;
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('COUNT(*)')
	  ->from('#__odyssey_city')
	  ->where('country_code='.$db->Quote($alpha2));
    $db->setQuery($query);
    $result = $db->loadResult();

    return $result;
  }


  /**
   * Add, delete or update the set of addon options passed as first argument. 
   *
   * @param array  Array of associative array containing the option values.
   * @param integer  Id number of the parent addon.
   *
   * @return void
   */
  public static function setAddonOptions($addonOptions, $addonId)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Get the old addon option selection.
    $query->select('id')
	  ->from('#__odyssey_addon_option')
	  ->where('addon_id='.(int)$addonId);
    $db->setQuery($query);
    $old = $db->loadColumn();

    if(empty($old) && empty($addonOptions)) {
      return;
    }

    $values = $whens = $optionIds = array();
    foreach($addonOptions as $addonOption) {
      if(empty($addonOption['id'])) { //insert
	//Store a value line for each addon option.
	$values[] = (int)$addonId.','.$db->quote($addonOption['name']).','.$db->quote($addonOption['code']).','.$db->quote($addonOption['description']).','.$db->quote($addonOption['image']).','.(int)$addonOption['published'].','.(int)$addonOption['ordering'];
      }
      else { //update
	//Build the WHEN clause for each field to update.
	if(isset($whens['name'])) {
	  $whens['name'] .= 'WHEN id = '.(int)$addonOption['id'].' THEN '.$db->quote($addonOption['name']).' '; 
	}
	else {
	  $whens['name'] = 'WHEN id = '.(int)$addonOption['id'].' THEN '.$db->quote($addonOption['name']).' '; 
	}

	if(isset($whens['code'])) {
	  $whens['code'] .= 'WHEN id = '.(int)$addonOption['id'].' THEN '.$db->quote($addonOption['code']).' '; 
	}
	else {
	  $whens['code'] = 'WHEN id = '.(int)$addonOption['id'].' THEN '.$db->quote($addonOption['code']).' '; 
	}

	if(isset($whens['description'])) {
	  $whens['description'] .= 'WHEN id = '.(int)$addonOption['id'].' THEN '.$db->quote($addonOption['description']).' '; 
	}
	else {
	  $whens['description'] = 'WHEN id = '.(int)$addonOption['id'].' THEN '.$db->quote($addonOption['description']).' '; 
	}

	if(isset($whens['image'])) {
	  $whens['image'] .= 'WHEN id = '.(int)$addonOption['id'].' THEN '.$db->quote($addonOption['image']).' '; 
	}
	else {
	  $whens['image'] = 'WHEN id = '.(int)$addonOption['id'].' THEN '.$db->quote($addonOption['image']).' '; 
	}

	if(isset($whens['published'])) {
	  $whens['published'] .= 'WHEN id = '.(int)$addonOption['id'].' THEN '.(int)$addonOption['published'].' '; 
	}
	else {
	  $whens['published'] = 'WHEN id = '.(int)$addonOption['id'].' THEN '.(int)$addonOption['published'].' '; 
	}

	if(isset($whens['ordering'])) {
	  $whens['ordering'] .= 'WHEN id = '.(int)$addonOption['id'].' THEN '.(int)$addonOption['ordering'].' '; 
	}
	else {
	  $whens['ordering'] = 'WHEN id = '.(int)$addonOption['id'].' THEN '.(int)$addonOption['ordering'].' '; 
	}

	$optionIds[] = $addonOption['id'];

	//Remove the updated option id which is also present in the old selection. So that
	//we know that the remaining ids are the options to delete.
	foreach($old as $key => $value) {
	  if($value == $addonOption['id']) {
	    unset($old[$key]);
	  }
	}
      }
    }

    //Remove the possible deleted addon options from the table.
    if(!empty($old)) {
      $query->clear();
      $query->delete('#__odyssey_addon_option')
	    ->where('id IN('.implode(',', $old).')');
      $db->setQuery($query);
      $db->execute();

      //Delete also the price rows corresponding to the removed options.
      $query->clear();
      $query->delete('#__odyssey_addon_option_price')
	    ->where('addon_id='.(int)$addonId.' AND addon_option_id IN('.implode(',', $old).')');
      $db->setQuery($query);
      $db->execute();
    }

    if(!empty($values)) {
      //Insert a new row for each option linked to the addon.
      $columns = array('addon_id', 'name', 'code', 'description', 'published', 'ordering');
      $query->clear();
      $query->insert('#__odyssey_addon_option')
	    ->columns($columns)
	    ->values($values);
      $db->setQuery($query);
      $db->execute();
    }

    if(!empty($whens)) {
      $cases = '';
      foreach($whens as $key => $when) {
	$cases .= $key.' = CASE '.$when.' ELSE '.$key.' END,';
      }

      //Remove comma from the end of the string.
      $cases = substr($cases, 0, -1);

      //Update the addon options.
      $query->clear();
      $query->update('#__odyssey_addon_option')
	    ->set($cases)
	    ->where('id IN('.implode(',', $optionIds).')');
      $db->setQuery($query);
      $db->execute();
    }

    return;
  }


  /**
   * Check and return the path to the overrided file of a given file.
   *
   * @param string  The absolute path to the original file.  
   *
   * @return string The absolute path to the overrided file or the given path otherwise.
   */
  public static function getOverridedFile($path)
  {
    //Get the name of the file from the path.
    $fileName = basename($path);

    //Check for an overrided file in the datafiles directory.
    if(file_exists(JPATH_ROOT.'/administrator/components/com_odyssey/extra/override/'.$fileName)) {
      return JPATH_ROOT.'/administrator/components/com_odyssey/extra/override/'.$fileName;
    }
    else {
      return $path;   
    }
  }  
 
 
  /**
   * Load an extra language file in the given language.
   *
   * @param JLanguage A JLanguage object instancied either in backend or in frontend.
   *
   * @return void
   */
  public static function addExtraLanguage($language)
  {  
    $path = JPATH_ROOT.'/administrator/components/com_odyssey/extra/language/';
    //Check for an extra language file in the given language.
    if(file_exists($path.$language->getTag().'/'.$language->getTag().'.com_odyssey.ini')) {
      $language->load('com_odyssey', JPATH_ROOT.'/administrator/components/com_odyssey/extra/', $language->getTag(), true);
    } //If the default extra language file is available we load it. 
    elseif(file_exists($path.$language->getDefault().'/'.$language->getDefault().'.com_odyssey.ini')) {
      $language->load('com_odyssey', JPATH_ROOT.'/administrator/components/com_odyssey/extra/', $language->getDefault(), true);
    }
  }
}


