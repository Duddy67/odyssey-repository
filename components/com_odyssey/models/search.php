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
	      'price',
	      'travel_duration', 't.travel_duration'
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

    $duration = $app->getUserStateFromRequest($this->context.'.filter.duration', 'filter_duration');
    $this->setState('filter.duration', $duration);

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
    $id .= ':'.$this->getState('filter.duration');
    $id .= ':'.$this->getState('filter.date');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $nowDate = $db->quote(JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true));

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 't.id, t.alias, t.name, MIN(tp.price) AS price, t.travel_duration, t.catid'))
	  ->from('#__odyssey_travel AS t')
	  //Get the lowest price for each travel.
	  ->join('INNER', '#__odyssey_departure_step_map AS ds ON ds.step_id=t.dpt_step_id')
	  ->join('INNER', '#__odyssey_travel_price AS tp ON tp.travel_id=t.id')
	  //Don't get the old departures of the travel.
	  ->where('(ds.date_time > '.$nowDate.' OR ds.date_time_2 > '.$nowDate.')')
	  ->where('tp.dpt_step_id=t.dpt_step_id AND tp.psgr_nb=1');

    //Display only published travels.
    $query->where('t.published=1');
    $query->group('t.id');

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
      $query->join('INNER', '#__odyssey_search_filter AS sf_co ON sf_co.travel_id=t.id')
	    ->where('sf_co.country_code='.$db->Quote($country));
    }

    //Filter by region.
    $region = $this->getState('filter.region');
    if(!empty($region)) {
      $query->join('INNER', '#__odyssey_search_filter AS sf_re ON sf_re.travel_id=t.id')
	    ->where('sf_re.region_code='.$db->Quote($region));
    }

    //Filter by city.
    $city = $this->getState('filter.city');
    if(is_numeric($city)) {
      $query->join('INNER', '#__odyssey_search_filter AS sf_ci ON sf_ci.travel_id=t.id')
	    ->where('sf_ci.city_id='.(int)$city);
    }

    //Filter by duration.
    $duration = $this->getState('filter.duration');
    if(!empty($duration)) {
      $query->where('t.travel_duration='.$db->Quote($duration));
    }

    //Filter by departure date.
    $date = $this->getState('filter.date');
    if(!empty($date)) {
      if(preg_match('#^([0-9]{4}-[0-9]{2}-[0-9]{2})_([0-9]{4}-[0-9]{2}-[0-9]{2})$#', $date, $matches)) {
	$query->where('ds.date_time LIKE '.$db->Quote($matches[1].'%').' AND ds.date_time_2 LIKE '.$db->Quote($matches[2].'%'));
      }
      //Standard departure.
      else {
	$query->where('ds.date_time LIKE '.$db->Quote($date.'%'));
      }
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 't.name');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    $query->order($db->escape($orderCol.' '.$orderDirn));
//echo $query;
    return $query;
  }
}


