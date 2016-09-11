<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modellist');



class OdysseyModelPricerules extends JModelList
{
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array(
	      'id', 'p.id',
	      'name', 'p.name',
	      'created', 'p.created',
	      'created_by', 'p.created_by',
	      'published', 'p.published',
	      'prule_type', 'p.prule_type',
	      'behavior', 'p.behavior',
	      'operation', 'p.operation',
	      'target', 'p.target',
	      'user', 'user_id',
	      'ordering', 'p.ordering',
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

    $published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
    $this->setState('filter.published', $published);

    // List state information.
    parent::populateState('p.name', 'asc');
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.published');
    $id .= ':'.$this->getState('filter.user_id');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 'p.id,p.name,p.created,p.prule_type,p.behavior,p.operation,p.value,'.
				   'p.target,p.published,p.ordering,p.created_by,p.checked_out,p.checked_out_time'));

    $query->from('#__odyssey_pricerule AS p');

    //Get the user name.
    $query->select('u.name AS user');
    $query->join('LEFT', '#__users AS u ON u.id = p.created_by');


    //Filter by title search.
    $search = $this->getState('filter.search');
    if(!empty($search)) {
      if(stripos($search, 'id:') === 0) {
	$query->where('p.id = '.(int) substr($search, 3));
      }
      else {
	$search = $db->Quote('%'.$db->escape($search, true).'%');
	$query->where('(p.name LIKE '.$search.')');
      }
    }

    //Filter by publication state.
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      $query->where('p.published='.(int)$published);
    }
    elseif($published === '') {
      $query->where('(p.published IN (0, 1))');
    }

    // Join over the users for the checked out user.
    $query->select('uc.name AS editor');
    $query->join('LEFT', '#__users AS uc ON uc.id=p.checked_out');

    //Filter by user.
    $userId = $this->getState('filter.user_id');
    if(is_numeric($userId)) {
      $type = $this->getState('filter.user_id.include', true) ? '= ' : '<>';
      $query->where('p.created_by'.$type.(int) $userId);
    }

    //Get the possible option sent by a link to a modal window.
    $modalOption = JFactory::getApplication()->input->get->get('modal_option', '', 'string');

    if($modalOption == 'coupon_only') {
      //Display price rules with coupon behavior only.
      $query->where('(p.behavior="CPN_XOR" OR p.behavior="CPN_AND")');
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 'p.name');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }
}


