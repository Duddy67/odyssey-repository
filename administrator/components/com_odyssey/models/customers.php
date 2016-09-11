<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modellist');



class OdysseyModelCustomers extends JModelList
{
  public function __construct($config = array())
  {
    if(empty($config['filter_fields']))
    {
      $config['filter_fields'] = array(
	      'id', 'c.id',
	      'userid', 'u.id','userid',
	      'name', 'u.name',
	      'firstname', 'c.firstname',
	      'username', 'u.username',
	      'email', 'u.email',
	      'registerDate', 'u.registerDate',
	      'lastvisitDate', 'u.lastvisitDate',
	      'group_id',
	      'range',
      );
    }

    parent::__construct($config);
  }


  protected function populateState($ordering = null, $direction = null)
  {
    // Initialise variables.
    $app = JFactory::getApplication();
    $session = JFactory::getSession();

    // Adjust the context to support modal layouts.
    if($layout = JRequest::getVar('layout')) {
      $this->context .= '.'.$layout;
    }

    //Get the state values set by the user.
    $search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
    $this->setState('filter.search', $search);

    $groupId = $this->getUserStateFromRequest($this->context.'.filter.group', 'filter_group_id', null, 'int');
    $this->setState('filter.group_id', $groupId);

    $range = $this->getUserStateFromRequest($this->context.'.filter.range', 'filter_range');
    $this->setState('filter.range', $range);

    // List state information.
    parent::populateState('u.name', 'asc');
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.range');
    $id .= ':'.$this->getState('filter.group_id');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    // Select the required fields from the table.
    //Many of the selected fields come from the Joomla users table.
    $query->select($this->getState('list.select', 'c.id, c.firstname, u.id AS userid, u.username, u.name,'.
				   'u.email, u.registerDate, u.lastvisitDate, c.checked_out, c.checked_out_time'));

    $query->from('#__odyssey_customer AS c');

    //Get name, username ,email etc... of the customer from the users table.
    $query->join('INNER', '#__users AS u ON u.id = c.id');


    //Filter by title search.
    $search = $this->getState('filter.search');
    if(!empty($search)) {
      if(stripos($search, 'id:') === 0) {
	$query->where('u.id = '.(int) substr($search, 3));
      }
      else {
	$search = $db->Quote('%'.$db->escape($search, true).'%');
	$query->where('(u.name LIKE '.$search.')');
      }
    }

    // Filter the items over the group id if set.
    $groupId = $this->getState('filter.group_id');
    if($groupId) {
      $query->join('LEFT', '#__user_usergroup_map AS map2 ON map2.user_id = u.id');
      $query->where('map2.group_id = '.(int) $groupId);
    }

    // Join over the users for the checked out user.
    $query->select('uc.name AS editor');
    $query->join('LEFT', '#__users AS uc ON uc.id=c.checked_out');

    // Add filter for registration ranges select list
    $range = $this->getState('filter.range');
    // Apply the range filter.
    if($range = $this->getState('filter.range')) {
      jimport('joomla.utilities.date');

      // Get UTC for now.
      $dNow = new JDate;
      $dStart = clone $dNow;

      switch ($range)
      {
	case 'past_week':
		$dStart->modify('-7 day');
		break;

	case 'past_1month':
		$dStart->modify('-1 month');
		break;

	case 'past_3month':
		$dStart->modify('-3 month');
		break;

	case 'past_6month':
		$dStart->modify('-6 month');
		break;

	case 'post_year':
	case 'past_year':
		$dStart->modify('-1 year');
		break;

	case 'today':
		// Ranges that need to align with local 'days' need special treatment.
		$app    = JFactory::getApplication();
		$offset = $app->get('offset');

		// Reset the start time to be the beginning of today, local time.
		$dStart = new JDate('now', $offset);
		$dStart->setTime(0, 0, 0);

		// Now change the timezone back to UTC.
		$tz = new DateTimeZone('GMT');
		$dStart->setTimezone($tz);
		break;
      }

      if($range == 'post_year') {
	$query->where('u.registerDate < '.$db->quote($dStart->format('Y-m-d H:i:s')));
      }
      else {
	$query->where('u.registerDate >= '.$db->quote($dStart->format('Y-m-d H:i:s')).
		      ' AND u.registerDate <='.$db->quote($dNow->format('Y-m-d H:i:s')));
      }
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }


  /**
   * Method to get an array of data items.
   *
   * @return  mixed  An array of data items on success, false on failure.
   *
   * @since   11.1
   */
  public function getItems()
  {
    // Get a storage key.
    $store = $this->getStoreId();

    // Try to load the data from internal storage.
    if(isset($this->cache[$store])) {
      return $this->cache[$store];
    }

    // Load the list items.
    $query = $this->_getListQuery();
    $items = $this->_getList($query, $this->getStart(), $this->getState('list.limit'));

    // Check for a database error.
    if($this->_db->getErrorNum()) {
      $this->setError($this->_db->getErrorMsg());
      return false;
    }

    //Get the title of the groups which the customer belongs to 
    //then put them into an array.
    $db = JFactory::getDbo();
    foreach($items as $item) {
      //Note: Yes we run a query within a foreach loop, but so far the Joomla team hasn't
      //done a better job (see _getUserDisplayedGroups function in users model).
      $query = 'SELECT title FROM #__usergroups '. 
	       'JOIN #__user_usergroup_map ON group_id=id '.
               'AND user_id='.$item->userid;
      $db->setQuery($query);
      $groups = $db->loadColumn();
      $item->groups = $groups;
    }

    // Add the items to the internal cache.
    $this->cache[$store] = $items;

    return $this->cache[$store];
  }
}


