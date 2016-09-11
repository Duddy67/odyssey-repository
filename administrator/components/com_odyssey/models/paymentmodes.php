<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modellist');



class OdysseyModelPaymentmodes extends JModelList
{
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array(
	      'id', 'p.id',
	      'name', 'p.name',
	      'published', 'p.published',
	      'plugin_element', 'p.plugin_element',
	      'plugin_name',
	      'created', 'p.created',
	      'created_by', 'p.created_by',
	      'user', 'user',
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
    $query->select($this->getState('list.select', 'p.id, p.name, p.created, p.plugin_element,'.
						  'p.published, p.ordering, p.created_by,'.
						  'p.checked_out, p.checked_out_time'));

    $query->from('#__odyssey_payment_mode AS p');

    //Get the user name.
    $query->select('u.name AS user');
    $query->join('LEFT', '#__users AS u ON u.id = p.created_by');

    // Join over the users for the checked out user.
    $query->select('uc.name AS editor');
    $query->join('LEFT', '#__users AS uc ON uc.id=p.checked_out');

    //Get the plugin name.
    $query->select('e.name AS plugin_name');
    $query->join('LEFT', '#__extensions AS e ON e.element=p.plugin_element AND e.folder="odysseypayment"');


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
      $query->where('p.published= '.(int)$published);
    }
    elseif($published === '') {
      $query->where('(p.published IN (0, 1))');
    }

    //Filter by user.
    $userId = $this->getState('filter.user_id');
    if(is_numeric($userId)) {
      $type = $this->getState('filter.user_id.include', true) ? '= ' : '<>';
      $query->where('p.created_by'.$type.(int) $userId);
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 'p.name');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }


  //Check if all the plugins currently used by JooShop are still installed
  //and/or enabled.
  public function getMissingPlugins()
  {
    $items= $this->getItems();

    //Store all of the plugins which are currently used by JooShop.
    $usedPlugins = array();
    foreach($items as $item) {
      $usedPlugins[] = $item->plugin_element;
    }

    //Since offline plugin can have several payment modes, we must remove
    //duplicate values before running array test.
    $usedPlugins = array_unique($usedPlugins);

    //Get all the enabled odysseypayment plugins.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('element')
	  ->from('#__extensions')
	  ->where('type="plugin" AND folder="odysseypayment" AND enabled=1');
    $db->setQuery($query);
    $paymentPlugins = $db->loadColumn();

    //Running the array test.
    $missingPlugins = array();
    foreach($usedPlugins as $usedPlugin) {
      //If a plugin is missing we store it into the missing plugins array.
      if(!in_array($usedPlugin, $paymentPlugins)) {
	$missingPlugins[] = $usedPlugin;
      }
    }

    return $missingPlugins;
  }
}


