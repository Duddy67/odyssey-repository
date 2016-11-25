<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modellist');



class OdysseyModelSearch extends JModelList
{
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array(
	      'id', 't.id',
	      'name', 't.name',
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

    //Get and set the default filter type set in the component global configuration.
    $this->setState('search.filters', JComponentHelper::getParams('com_odyssey')->get('search_filters'));

    //Get the state values set by the user.
    $search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
    $this->setState('filter.search', $search);

    $country = $app->getUserStateFromRequest($this->context.'.filter.country', 'filter_country');
    $this->setState('filter.country', $country);

    $region = $app->getUserStateFromRequest($this->context.'.filter.region', 'filter_region');
    $this->setState('filter.region', $region);

    $city = $app->getUserStateFromRequest($this->context.'.filter.city', 'filter_city');
    $this->setState('filter.city', $city);

    $date = $app->getUserStateFromRequest($this->context.'.filter.date', 'filter_date');
    $this->setState('filter.date', $date);

    // List state information.
    parent::populateState('t.name', 'asc');
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.country');
    $id .= ':'.$this->getState('filter.region');
    $id .= ':'.$this->getState('filter.city');
    $id .= ':'.$this->getState('filter.date');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Get the default currency display.
    $parameters = JComponentHelper::getParams('com_odyssey');
    $display = $parameters->get('currency_display');

    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    $filterType = $this->getState('search.filters');

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 't.name'));

    $query->from('#__odyssey_travel AS t')
	  ->join('INNER', '#__odyssey_search_filter AS sf ON sf.travel_id=t.id');

    //Display only published travels.
    $query->where('t.published=1');

    //Filter by title search.
    $search = $this->getState('filter.search');
    if(!empty($search)) {
      if(stripos($search, 'id:') === 0) {
	$query->where('t.id = '.(int) substr($search, 3));
      }
      else {
	$search = $db->Quote('%'.$db->escape($search, true).'%');
	$query->where('(t.name LIKE '.$search.')');
      }
    }

    //Filter by country.
    $country = $this->getState('filter.country');
    if(!empty($country)) {
      $query->where('sf.country_code='.$db->Quote($country));
    }

    //Filter by region.
    $region = $this->getState('filter.region');
    if(!empty($region)) {
      $query->where('sf.region_code='.$db->Quote($region));
    }

    //Filter by city.
    $city = $this->getState('filter.city');
    if(is_numeric($city)) {
      $query->where('sf.city_id='.$city);
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 't.name');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }
}


