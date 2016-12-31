<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modellist');



class OdysseyModelAddons extends JModelList
{
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array(
	      'id', 'a.id',
	      'addon_type', 'a.addon_type',
	      'group_nb', 'a.group_nb',
	      'global', 'a.global',
	      'created', 'a.created',
	      'created_by', 'a.created_by',
	      'published', 'a.published',
	      'user', 'user_id',
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

    $addonType = $this->getUserStateFromRequest($this->context.'.filter.addon_type', 'filter_addon_type', '');
    $this->setState('filter.addon_type', $addonType);

    $groupNb = $this->getUserStateFromRequest($this->context.'.filter.group_nb', 'filter_group_nb', '');
    $this->setState('filter.group_nb', $groupNb);

    // List state information.
    parent::populateState('a.name', 'asc');
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.published');
    $id .= ':'.$this->getState('filter.user_id');
    $id .= ':'.$this->getState('filter.addon_type');
    $id .= ':'.$this->getState('filter.group_nb');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 'a.id,a.name,a.addon_type,a.group_nb,a.global,a.created,'.
						  'a.published,a.created_by,a.checked_out,a.checked_out_time'));

    $query->from('#__odyssey_addon AS a');

    //Get the user name.
    $query->select('u.name AS user');
    $query->join('LEFT', '#__users AS u ON u.id = a.created_by');


    //Filter by title search.
    $search = $this->getState('filter.search');
    if(!empty($search)) {
      if(stripos($search, 'id:') === 0) {
	$query->where('a.id = '.(int) substr($search, 3));
      }
      else {
	$search = $db->Quote('%'.$db->escape($search, true).'%');
	$query->where('(a.name LIKE '.$search.')');
      }
    }

    //Filter by publication state.
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      $query->where('a.published='.(int)$published);
    }
    elseif($published === '') {
      $query->where('(a.published IN (0, 1))');
    }

    // Join over the users for the checked out user.
    $query->select('uc.name AS editor');
    $query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');

    //Filter by user.
    $userId = $this->getState('filter.user_id');
    if(is_numeric($userId)) {
      $type = $this->getState('filter.user_id.include', true) ? '= ' : '<>';
      $query->where('a.created_by'.$type.(int) $userId);
    }

    //Get the possible option sent by a modal window.
    $modalOption = JFactory::getApplication()->input->get->get('modal_option', '', 'string');
    if($modalOption == 'no_global') {
      $query->where('a.global=0');
    }

    //Filter by addon type.
    $addonType = $this->getState('filter.addon_type');
    if(!empty($addonType)) {
      $query->where('a.addon_type='.$db->Quote($addonType));
    }

    //Filter by group number.
    $groupNb = $this->getState('filter.group_nb');
    if(!empty($groupNb)) {
      $query->where('a.group_nb='.$db->Quote($groupNb));
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 'a.name');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }
}


