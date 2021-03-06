<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
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

    //getParams function return global parameters overrided by the menu parameters (if any).
    //Note: Some specific parameters of this menu are not returned.
    $params = $app->getParams();

    $menuParams = new JRegistry;

    //Get the menu with its specific parameters.
    if($menu = $app->getMenu()->getActive()) {
      $menuParams->loadString($menu->params);
    }

    //Merge Global and Menu Item params into a new object.
    $mergedParams = clone $menuParams;
    $mergedParams->merge($params);

    // Load the parameters in the session.
    $this->setState('params', $mergedParams);

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

    $month = $app->getUserStateFromRequest($this->context.'.filter.month', 'filter_month');
    $this->setState('filter.month', $month);

    $date = $app->getUserStateFromRequest($this->context.'.filter.date', 'filter_date');
    $this->setState('filter.date', $date);

    $duration = $app->getUserStateFromRequest($this->context.'.filter.duration', 'filter_duration');
    $this->setState('filter.duration', $duration);

    $price = $app->getUserStateFromRequest($this->context.'.filter.price', 'filter_price');
    $this->setState('filter.price', $price);

    $theme = $app->getUserStateFromRequest($this->context.'.filter.theme', 'filter_theme');
    $this->setState('filter.theme', $theme);

    parent::populateState('t.name', 'asc');

    //IMPORTANT: The pagination values must be set AFTER the call to the parent method or
    //these values will be overwritten.
    $limit = $params->get('display_num', 10);
    if($params->get('show_pagination_limit')) {
      $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $limit, 'uint');
    }

    $this->setState('list.limit', $limit);

    //Get the limitstart variable (used for the pagination) from the form variable.
    $limitstart = $app->input->get('limitstart', 0, 'uint');
    $this->setState('list.start', $limitstart);
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.country');
    $id .= ':'.$this->getState('filter.region');
    $id .= ':'.$this->getState('filter.city');
    $id .= ':'.$this->getState('filter.price');
    $id .= ':'.$this->getState('filter.duration');
    $id .= ':'.$this->getState('filter.date');
    $id .= ':'.$this->getState('filter.theme');
    $id .= ':'.$this->getState('filter.month');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $nowDate = $db->quote(JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true));

    $query->select($this->getState('list.select', 't.id, ANY_VALUE(ds.nb_days) AS nb_days, ANY_VALUE(ds.nb_nights) AS nb_nights,'.
                                                  'MIN(tp.price) AS price, MAX(ds.published) AS dpt_published, t.alias, t.name,'.
						  't.travel_duration, t.catid, t.theme, t.subtitle, t.intro_text,'.
						  't.image, t.extra_fields'))

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

    //Filter by month.
    $month = $this->getState('filter.month');
    if(!empty($month)) {
      $query->where('ds.date_time LIKE '.$db->Quote($month.'%'));
    }

    //Filter by price.
    $price = $this->getState('filter.price');
    if(!empty($price)) {
      $query->where('t.price_range='.$db->Quote($price));
    }

    //Filter by duration.
    $duration = $this->getState('filter.duration');
    if(!empty($duration)) {
      $query->where('t.travel_duration='.$db->Quote($duration));
    }

    //Filter by theme.
    $theme = $this->getState('filter.theme');
    if(!empty($theme)) {
      $query->where('t.theme='.$db->Quote($theme));
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

    return $query;
  }
}


