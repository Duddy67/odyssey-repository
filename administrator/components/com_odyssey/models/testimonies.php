<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modellist');



class OdysseyModelTestimonies extends JModelList
{
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array(
	      'id', 't.id',
	      'travel_id', 't.travel_id', 'travel_name',
	      'author_name',
	      'title', 't.title',
	      'published', 't.published',
	      'created', 't.created',
	      'created_by', 't.created_by',
	      'user', 'user_id'
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
    if($layout = JFactory::getApplication()->input->get('layout')) {
      $this->context .= '.'.$layout;
    }

    //Get the state values set by the user.
    $search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
    $this->setState('filter.search', $search);

    $userId = $app->getUserStateFromRequest($this->context.'.filter.user_id', 'filter_user_id');
    $this->setState('filter.user_id', $userId);

    $travelId = $app->getUserStateFromRequest($this->context.'.filter.travel_id', 'filter_travel_id');
    $this->setState('filter.travel_id', $travelId);

    $published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
    $this->setState('filter.published', $published);

    // List state information.
    parent::populateState('t.title', 'asc');
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.published');
    $id .= ':'.$this->getState('filter.user_id');
    $id .= ':'.$this->getState('filter.travel_id');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 't.id,t.travel_id,t.title,t.created,t.author_name,'.
				   't.published,t.created_by,t.checked_out,t.checked_out_time'))
	  ->from('#__odyssey_testimony AS t');

    //Get the user name.
    $query->select('u.name AS user')
	  ->join('LEFT', '#__users AS u ON u.id = t.created_by');


    //Filter by title or id search.
    $search = $this->getState('filter.search');
    if(!empty($search)) {
      if(stripos($search, 'id:') === 0) {
        $search = substr($search, 6);
	$query->where('t.id='.(int)$search);
      }
      else {
	$search = $db->Quote('%'.$db->escape($search, true).'%');
	$query->where('(t.title LIKE '.$search.')');
      }
    }

    //Filter by publication state.
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      $query->where('t.published= '.(int)$published);
    }
    elseif($published === '') {
      $query->where('(t.published IN (0, 1))');
    }

    // Join over the users for the checked out user.
    $query->select('uc.name AS editor')
	  ->join('LEFT', '#__users AS uc ON uc.id=t.checked_out');

    // Join over the travel.
    $query->select('tr.name AS travel_name')
	  ->join('LEFT', '#__odyssey_travel AS tr ON tr.id=t.travel_id');

    //Filter by user.
    $userId = $this->getState('filter.user_id');
    if(is_numeric($userId)) {
      $type = $this->getState('filter.user_id.include', true) ? '= ' : '<>';
      $query->where('t.created_by'.$type.(int) $userId);
    }

    //Filter by travel id.
    $travelId = $this->getState('filter.travel_id');
    if((int)$travelId) {
      $query->where('t.travel_id='.(int)$travelId);
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 't.title');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }
}


