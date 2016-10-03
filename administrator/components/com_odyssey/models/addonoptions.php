<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modellist');



class OdysseyModelAddonoptions extends JModelList
{
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array(
	      'id', 'ao.id',
	      'published', 'ao.published',
	      'option_type', 'a.option_type',
	      'addon_type', 'a.addon_type',
	      'a.name', 'parent_addon',
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

    $published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
    $this->setState('filter.published', $published);

    $optionType = $app->getUserStateFromRequest($this->context.'.filter.option_type', 'filter_option_type');
    $this->setState('filter.option_type', $optionType);

    $addonType = $this->getUserStateFromRequest($this->context.'.filter.addon_type', 'filter_addon_type', '');
    $this->setState('filter.addon_type', $addonType);

    // List state information.
    parent::populateState('ao.name', 'asc');
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.published');
    $id .= ':'.$this->getState('filter.addon_type');
    $id .= ':'.$this->getState('filter.option_type');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 'ao.*, a.name AS parent_addon, a.addon_type, a.option_type'));
    $query->from('#__odyssey_addon_option AS ao');
    $query->join('INNER', '#__odyssey_addon AS a ON a.id=ao.addon_id');

    //Get the user name.
    $query->select('u.name AS user');
    $query->join('LEFT', '#__users AS u ON u.id = a.created_by');


    //Filter by title search.
    $search = $this->getState('filter.search');
    if(!empty($search)) {
      if(stripos($search, 'id:') === 0) {
	$query->where('ao.id = '.(int) substr($search, 3));
      }
      else {
	$search = $db->Quote('%'.$db->escape($search, true).'%');
	$query->where('(ao.name LIKE '.$search.')');
      }
    }

    //Filter by publication state.
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      $query->where('ao.published='.(int)$published);
    }
    elseif($published === '') {
      $query->where('(ao.published IN (0, 1))');
    }

    //Filter by option type.
    $optionType = $this->getState('filter.option_type');
    if(!empty($optionType)) {
      $query->where('ao.option_type='.$db->Quote($optionType));
    }

    //Filter by addon type.
    $addonType = $this->getState('filter.addon_type');
    if(!empty($addonType)) {
      $query->where('a.addon_type='.$db->Quote($addonType));
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 'ao.name');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }
}


