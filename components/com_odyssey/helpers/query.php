<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/**
 * Odyssey Component Query Helper
 *
 * @static
 * @package     Joomla.Site
 * @subpackage  com_odyssey
 * @since       1.5
 */
class OdysseyHelperQuery
{
  /**
   * Translate an order code to a field for primary category ordering.
   *
   * @param   string	$orderby	The ordering code.
   *
   * @return  string	The SQL field(s) to order by.
   * @since   1.5
   */
  public static function orderbyPrimary($orderby)
  {
    //Set the prefix to use according to the view.
    $view = JFactory::getApplication()->input->get('view');
    $prefix = 'ca.'; //Use odyssey table.
    if($view == 'tag') {
      $prefix = 'n.'; //Use mapping table.
    }

    switch ($orderby)
    {
      case 'alpha' :
	      $orderby = $prefix.'path, ';
	      break;

      case 'ralpha' :
	      $orderby = $prefix.'path DESC, ';
	      break;

      case 'order' :
	      $orderby = $prefix.'lft, ';
	      break;

      default :
	      $orderby = '';
	      break;
    }

    return $orderby;
  }

  /**
   * Translate an order code to a field for secondary category ordering.
   *
   * @param   string	$orderby	The ordering code.
   * @param   string	$orderDate	The ordering code for the date.
   *
   * @return  string	The SQL field(s) to order by.
   * @since   1.5
   */
  public static function orderbySecondary($orderby, $orderDate = 'created')
  {
    $queryDate = self::getQueryDate($orderDate);

    //Set the prefix to use according to the view.
    $view = JFactory::getApplication()->input->get('view');
    $prefix = 't.'; //Use odyssey table.
    if($view == 'tag') {
      $prefix = 'tm.'; //Use mapping table.
    }

    switch ($orderby)
    {
      case 'date' :
	      $orderby = $queryDate;
	      break;

      case 'rdate' :
	      $orderby = $queryDate.' DESC ';
	      break;

      case 'alpha' :
	      $orderby = 't.name';
	      break;

      case 'ralpha' :
	      $orderby = 't.name DESC';
	      break;

      case 'order' :
	      $orderby = $prefix.'ordering';
	      break;

      case 'rorder' :
	      $orderby = $prefix.'ordering DESC';
	      break;

      case 'author' :
	      $orderby = 't.author';
	      break;

      case 'rauthor' :
	      $orderby = 't.author DESC';
	      break;

      default :
	      $orderby = $prefix.'ordering';
	      break;
    }

    return $orderby;
  }

  /**
   * Translate an order code to a field for primary category ordering.
   *
   * @param   string	$orderDate	The ordering code.
   *
   * @return  string	The SQL field(s) to order by.
   * @since   1.6
   */
  public static function getQueryDate($orderDate)
  {
    $db = JFactory::getDbo();

    switch($orderDate) {
      case 'modified' :
	      $queryDate = ' CASE WHEN t.modified = '.$db->quote($db->getNullDate()).' THEN t.created ELSE t.modified END';
	      break;

      // use created if publish_up is not set
      case 'published' :
	      $queryDate = ' CASE WHEN t.publish_up = '.$db->quote($db->getNullDate()).' THEN t.created ELSE t.publish_up END ';
	      break;

      case 'created' :
      default :
	      $queryDate = ' t.created ';
	      break;
    }

    return $queryDate;
  }

  /**
   * Method to order the intro travels array for ordering
   * down the columns instead of across.
   * The layout always lays the introtext travels out across columns.
   * Array is reordered so that, when travels are displayed in index order
   * across columns in the layout, the result is that the
   * desired travel ordering is achieved down the columns.
   *
   * @param   array    &$travels   Array of intro text travels
   * @param   integer  $numColumns  Number of columns in the layout
   *
   * @return  array  Reordered array to achieve desired ordering down columns
   *
   * @since   1.6
   */
  public static function orderDownColumns(&$travels, $numColumns = 1)
  {
    $count = count($travels);

    // Just return the same array if there is nothing to change
    if($numColumns == 1 || !is_array($travels) || $count <= $numColumns) {
      $return = $travels;
    }
    // We need to re-order the intro travels array
    else {
      // We need to preserve the original array keys
      $keys = array_keys($travels);

      $maxRows = ceil($count / $numColumns);
      $numCells = $maxRows * $numColumns;
      $numEmpty = $numCells - $count;
      $index = array();

      // Calculate number of empty cells in the array

      // Fill in all cells of the array
      // Put -1 in empty cells so we can skip later
      for($row = 1, $i = 1; $row <= $maxRows; $row++) {
	for($col = 1; $col <= $numColumns; $col++) {
	  if($numEmpty > ($numCells - $i)) {
	    // Put -1 in empty cells
	    $index[$row][$col] = -1;
	  }
	  else {
	    // Put in zero as placeholder
	    $index[$row][$col] = 0;
	  }

	  $i++;
	}
      }

      // Layout the travels in column order, skipping empty cells
      $i = 0;

      for($col = 1; ($col <= $numColumns) && ($i < $count); $col++) {
	for($row = 1; ($row <= $maxRows) && ($i < $count); $row++) {
	  if($index[$row][$col] != - 1) {
	    $index[$row][$col] = $keys[$i];
	    $i++;
	  }
	}
      }

      // Now read the $index back row by row to get travels in right row/col
      // so that they will actually be ordered down the columns (when read by row in the layout)
      $return = array();
      $i = 0;

      for($row = 1; ($row <= $maxRows) && ($i < $count); $row++) {
	for($col = 1; ($col <= $numColumns) && ($i < $count); $col++) {
	  $return[$keys[$i]] = $travels[$index[$row][$col]];
	  $i++;
	}
      }
    }

    return $return;
  }


  /**
   * Method which set up the JOIN and WHERE clauses for a given filter according the state
   * of the other filters.
   *
   * @param   string $filterName  The name of the filter for which the query is built.
   *
   * @return  array  The JOIN and WHERE clauses set according to the state of the other
   *                 filters.
   *
   */
  public static function getSearchFilterQuery($filterName)
  {
    $filterQuery = array('join' => array(), 'where' => array());
    $post = JFactory::getApplication()->input->post->getArray();
    $geographicalFilters = array('country', 'region', 'city');
    $db = JFactory::getDbo();

    //The geographical filters are not taken in account in the query  as they 
    //deal with each other in their respective file.
    if(in_array($filterName, $geographicalFilters)) {
      $filters = array('month', 'price', 'duration', 'theme', 'date');
    }
    else {
      $filters = array('country', 'region', 'city', 'month', 'price', 'duration', 'theme', 'date');
    }

    foreach($filters as $key => $filter) {
      //Stores the value of each available filter.
      //Note: The calling filter is not taken in account.
      if($filter != $filterName && isset($post['filter'][$filter])) {
	//Note: Uses a variable's variable
	${$filter} = $post['filter'][$filter];
      }
    }

    //Joins over the travel table for the geographical filters.
    if(in_array($filterName, $geographicalFilters)) {
      if((isset($price) && !empty($price)) || (isset($duration) && !empty($duration)) || (isset($theme) && !empty($theme)) || (isset($month) && !empty($month))) {
	$alias = substr($filterName, 0, 2);
	$filterQuery['join'][] = '#__odyssey_travel AS t ON sf_'.$alias.'.travel_id=t.id';
      }
    }
    //Joins over the search_fiter table for the "regular" filters.
    else {
      if(isset($country) && !empty($country)) {
	$filterQuery['join'][] = '#__odyssey_search_filter AS sf_co ON sf_co.travel_id=t.id';
	$filterQuery['where'][] = 'sf_co.country_code='.$db->Quote($country);
      }

      if(isset($region) && !empty($region)) {
	$filterQuery['join'][] = '#__odyssey_search_filter AS sf_re ON sf_re.travel_id=t.id';
	$filterQuery['where'][] = 'sf_re.region_code='.$db->Quote($region);
      }

      if(isset($city) && !empty($city)) {
	$filterQuery['join'][] = '#__odyssey_search_filter AS sf_ci ON sf_ci.travel_id=t.id';
	$filterQuery['where'][] = 'sf_ci.city_id='.(int)$city;
      }
    }

    //To get the months we also have to join over the departure step map table.
    if(isset($month) && !empty($month)) {
      $filterQuery['join'][] = '#__odyssey_departure_step_map AS ds ON ds.step_id=t.dpt_step_id';
      $filterQuery['where'][] = 'ds.date_time LIKE '.$db->Quote($month.'%');
    }

    if(isset($price) && !empty($price)) {
      $filterQuery['where'][] = 't.price_range='.$db->Quote($price);
    }

    if(isset($duration) && !empty($duration)) {
      $filterQuery['where'][] = 't.travel_duration='.$db->Quote($duration);
    }

    if(isset($theme) && !empty($theme)) {
      $filterQuery['where'][] = 't.theme='.$db->Quote($theme);
    }

    return $filterQuery;
  }
}

