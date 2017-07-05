<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.

require_once JPATH_COMPONENT.'/helpers/travel.php';
require_once JPATH_COMPONENT.'/helpers/pricerule.php';
require_once JPATH_COMPONENT_ADMINISTRATOR.'/helpers/utility.php';


class OdysseyModelTravel extends JModelItem
{

  protected $_context = 'com_odyssey.travel';

  /**
   * Method to auto-populate the model state.
   *
   * Note. Calling getState in this method will result in recursion.
   *
   * @since   1.6
   *
   * @return void
   */
  protected function populateState()
  {
    $app = JFactory::getApplication('site');

    // Load state from the request.
    $pk = $app->input->getInt('id');
    $this->setState('travel.id', $pk);

    //Load the global parameters of the component.
    $params = $app->getParams();
    $this->setState('params', $params);

    //Get the current date.
    $nowDate = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);
    //Compute the date from which a customer is allowed to book a travel.
    $bookingDate = UtilityHelper::getLimitDate($params->get('allow_booking_from'), $nowDate);

    //Store dates.
    $this->setState('now_date', $nowDate);
    $this->setState('booking_date', $bookingDate);

    $this->setState('filter.language', JLanguageMultilang::isEnabled());
  }


  //Returns a Table object, always creating it.
  public function getTable($type = 'Travel', $prefix = 'OdysseyTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  /**
   * Method to get a single record.
   *
   * @param   integer  $pk  The id of the primary key.
   *
   * @return  mixed    Object on success, false on failure.
   *
   * @since   12.2
   */
  public function getItem($pk = null)
  {
    $pk = (!empty($pk)) ? $pk : (int)$this->getState('travel.id');
    $user = JFactory::getUser();
    $nowDate = $this->getState('now_date');

    if($this->_item === null) {
      $this->_item = array();
    }

    if(!isset($this->_item[$pk])) {
      $db = $this->getDbo();
      $query = $db->getQuery(true);
      $query->select($this->getState('list.select', 't.id,t.name,t.alias,t.intro_text,t.full_text,t.catid,t.published,t.image,'.
				     't.subtitle,t.checked_out,t.checked_out_time,t.created,t.created_by,t.access,t.params,t.metadata,'.
				     't.metakey,t.metadesc,t.hits,t.publish_up,t.publish_down,t.language,t.modified,t.modified_by,'.
				     't.dpt_step_id,t.show_steps,t.show_grouped_steps,t.departure_number,t.extra_desc_1,'.
				     't.extra_desc_2,t.extra_desc_3,t.extra_desc_4,MIN(tp.price) AS lowest_price'))
	    ->from($db->quoteName('#__odyssey_travel').' AS t')
	    //Get the lowest price of this travel for one passenger.
	    ->join('INNER', '#__odyssey_departure_step_map AS ds ON ds.step_id=t.dpt_step_id')
	    ->join('INNER', '#__odyssey_travel_price AS tp ON tp.travel_id=t.id')
	    //Don't get the old departures of the travel.
	    ->where('(ds.date_time > '.$db->quote($nowDate).' OR ds.date_time_2 > '.$db->quote($nowDate).')')
	    ->where('tp.dpt_step_id=t.dpt_step_id AND tp.psgr_nb=1')
	    ->where('t.id='.$pk);

      // Join on category table.
      $query->select('ca.title AS category_title, ca.alias AS category_alias, ca.access AS category_access')
	    ->join('LEFT', '#__categories AS ca on ca.id = t.catid');

      // Join on user table.
      $query->select('us.name AS author')
	    ->join('LEFT', '#__users AS us on us.id = t.created_by');

      // Join over the categories to get parent category titles
      $query->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias')
	    ->join('LEFT', '#__categories as parent ON parent.id = ca.parent_id');

      //Join over the step table to get the date type.
      $query->select('s.date_type');
      $query->join('LEFT', '#__odyssey_step AS s ON s.id=t.dpt_step_id');

      // Filter by language
      if($this->getState('filter.language')) {
	$query->where('t.language in ('.$db->quote(JFactory::getLanguage()->getTag()).','.$db->quote('*').')');
      }

      if((!$user->authorise('core.edit.state', 'com_odyssey')) && (!$user->authorise('core.edit', 'com_odyssey'))) {
	// Filter by start and end dates.
	$nullDate = $db->quote($db->getNullDate());
	$query->where('(t.publish_up = '.$nullDate.' OR t.publish_up <= '.$db->quote($nowDate).')')
	      ->where('(t.publish_down = '.$nullDate.' OR t.publish_down >= '.$db->quote($nowDate).')');
      }

      $db->setQuery($query);
      $data = $db->loadObject();

      if(is_null($data)) {
	return JError::raiseError(404, JText::_('COM_ODYSSEY_ERROR_TRAVEL_NOT_FOUND'));
      }

      // Convert parameter fields to objects.
      $registry = new JRegistry;
      $registry->loadString($data->params);

      $data->params = clone $this->getState('params');
      $data->params->merge($registry);

      $user = JFactory::getUser();
      // Technically guest could edit an article, but lets not check that to improve performance a little.
      if(!$user->get('guest')) {
	$userId = $user->get('id');
	$asset = 'com_odyssey.travel.'.$data->id;

	// Check general edit permission first.
	if($user->authorise('core.edit', $asset)) {
	  $data->params->set('access-edit', true);
	}

	// Now check if edit.own is available.
	elseif(!empty($userId) && $user->authorise('core.edit.own', $asset)) {
	  // Check for a valid user and that they are the owner.
	  if($userId == $data->created_by) {
	    $data->params->set('access-edit', true);
	  }
	}
      }

      // Get the tags
      $data->tags = new JHelperTags;
      $data->tags->getItemTags('com_odyssey.travel', $data->id);

      //Get the names and descriptions of the addons linked to this travel.
      $data->addons = TravelHelper::getAddons($data->dpt_step_id, $data->departure_number, array('excursion','hosting'));

      $this->_item[$pk] = $data;
    }

    return $this->_item[$pk];
  }


  //Collect all the needed data to display the travel properly.
  public function getTravelData($dptStepId)
  {
    $travelId = (int)$this->getState($this->getName().'.id');

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $bookingDate = $this->getState('booking_date');

    //Get a travel price row for each departure of the step sequence multiplied with the number of passengers.
    $query->select('d.dpt_id, d.date_time, d.date_time_2, d.max_passengers, d.allotment, s.date_type, c.name AS city_name,'.
	           'c.id AS city_id, c.lang_var, p.psgr_nb,p.price')
	  ->from('#__odyssey_departure_step_map AS d')
	  ->join('LEFT', '#__odyssey_travel_price AS p ON p.dpt_id=d.dpt_id AND p.travel_id='.(int)$travelId.' AND p.dpt_step_id='.(int)$dptStepId)
	  ->join('LEFT', '#__odyssey_step AS s ON s.id='.(int)$dptStepId)
	  ->join('LEFT', '#__odyssey_city AS c ON c.id=d.city_id')
	  ->where('d.step_id='.(int)$dptStepId)
	  //Fetch only departures scheduled after the booking limit date.
	  ->where('(d.date_time >= '.$db->quote($bookingDate).' OR d.date_time_2 >= '.$db->quote($bookingDate).')')
	  ->order('d.date_time, p.psgr_nb');
    $db->setQuery($query);
    $results = $db->loadAssocList();

    //Rearrange data for more convenience. Prices per passenger are stored in an array for
    //each departure.
    $travel = UtilityHelper::combinePriceRows($results);

    //The category id of the travel is needed for price rules.
    $query->clear();
    $query->select('catid')
	  ->from('#__odyssey_travel')
	  ->where('id='.(int)$travelId);
    $db->setQuery($query);
    $catid = $db->loadResult();

    //Get the possible price rules linked to this travel.
    $travelPriceRules = PriceruleHelper::getCatalogPriceRules($travel, $travelId, $catid);

    //Get the possible transit cities available for each departure of the travel.
    $query->clear();
    $query->select('ds.dpt_id, tc.city_id, tcn.name AS transitcity_name, ds.max_passengers, tc.time_offset, tcp.psgr_nb, tcp.price')
	  ->from('#__odyssey_step_transit_city_map AS tc')
	  ->join('LEFT', '#__odyssey_departure_step_map AS ds ON ds.step_id='.(int)$dptStepId)
	  ->join('LEFT', '#__odyssey_transit_city_price AS tcp ON tcp.travel_id='.$travelId.' AND tcp.dpt_step_id=tc.dpt_step_id'.
			 ' AND tcp.city_id=tc.city_id AND tcp.dpt_id=tc.dpt_id')
	  ->join('LEFT', '#__odyssey_city AS tcn ON tcn.id=tc.city_id')
	  ->where('tc.dpt_id=ds.dpt_id')
	  ->where('tc.dpt_step_id='.(int)$dptStepId)
	  //Fetch only departures scheduled after the booking limit date.
	  ->where('(ds.date_time >= '.$db->quote($bookingDate).' OR ds.date_time_2 >= '.$db->quote($bookingDate).')')
	  //Order the result rows is required to get the combinePriceRows function work properly.
	  ->order('tc.dpt_step_id, tc.city_id, ds.date_time, tcp.psgr_nb');
	  //echo $query;
    $db->setQuery($query);
    $results = $db->loadAssocList();

    //Same data rearrangement type with transit cities.
    $transitCities = UtilityHelper::combinePriceRows($results);

    //In order to use Javascript to display prices dynamicaly (according to the drop down
    //lists selections) we have to prepare data so that it is retrieved through js functions.

    $dptIds = $departures = $periods = $nbPsgr = $pricePerPsgr = $dptCities = $transCityIds = array();
    foreach($travel as $key => $data) {
      //Don't display the departure if allotment is empty.
      if($data['allotment'] == 0) {
        continue;
      }

      //Get all the departure ids.
      $dptIds[] = $data['dpt_id'];
      //Set a mapping array to get the max_passengers value for each departure.
      $nbPsgr[$data['dpt_id']] = $data['max_passengers'];

      //If allotment is lower than max passengers we use allotment value as max passengers.
      if($data['allotment'] < $data['max_passengers']) {
	$nbPsgr[$data['dpt_id']] = $data['allotment'];
      }

      //Store departures.
      if($data['date_type'] == 'period') {
	//Extract year month day from the starting date time (time value is not used with period date type).
	preg_match('#^([0-9]{4})-([0-9]{2})-([0-9]{2}).*$#', $data['date_time'], $matches);
	$fromYear = $matches[1];
	$fromMonth = $matches[2];
	$fromDay = $matches[3];

	$dateTime = $fromYear.'-'.$fromMonth.'-'.$fromDay;

	//To prevent users to pick dates previous to the current date we set the date
	//variables to the current date.
	$currentDate = date('Y-m-d');
	if($dateTime < $currentDate) {
	  preg_match('#^([0-9]{4})-([0-9]{2})-([0-9]{2}).*$#', $currentDate, $matches);
	  $fromYear = $matches[1];
	  $fromMonth = $matches[2];
	  $fromDay = $matches[3];
	}

	//Remove padding zero (if any) from month and day.
	$fromMonth = ($fromMonth[0] == '0') ? $fromMonth[1] : $fromMonth;
	$fromDay = ($fromDay[0] == '0') ? $fromDay[1] : $fromDay;

	//Month value is zero based (January = 0, February = 1 etc..).
	$fromMonth = $fromMonth - 1;

	//Idem for the ending date time.
	preg_match('#^([0-9]{4})-([0-9]{2})-([0-9]{2}) [0-9]{2}:[0-9]{2}:[0-9]{2}$#', $data['date_time_2'], $matches);
	$toYear = $matches[1];
	$toMonth = $matches[2];
	$toDay = $matches[3];

	$dateTime2 = $toYear.'-'.$toMonth.'-'.$toDay;

	$toMonth = ($toMonth[0] == '0') ? $toMonth[1] : $toMonth;
	$toDay = ($toDay[0] == '0') ? $toDay[1] : $toDay;

	$toMonth = $toMonth - 1;

	$periods[$data['dpt_id']] = array($fromYear, $fromMonth, $fromDay, $toYear, $toMonth, $toDay);

	$departures[] = array($data['dpt_id'],
			      JText::_('COM_ODYSSEY_PERIOD_FROM').JHTML::_('date', $dateTime, JText::_('DATE_FORMAT_LC3')),
			      JText::_('COM_ODYSSEY_PERIOD_TO').JHTML::_('date', $dateTime2, JText::_('DATE_FORMAT_LC3')));
      }
      else { //Standard date type.
	$departures[] = array($data['dpt_id'], JHTML::_('date', $data['date_time'], JText::_('DATE_FORMAT_LC3')));
      }

      //Set a mapping array to get the prices per passenger (as an array) for each departure.
      $pricePerPsgr[$data['dpt_id']]['price_per_psgr'] = $data['price_per_psgr'];
      //Set the main departure city as the first departure city.
      $dptCities[$data['dpt_id']] = array(array($data['city_id'], $data['city_name']));

      //Add possible transit cities as departure cities.
      foreach($transitCities as $transitCity) {
	//Get all the transit city ids.
	if(!in_array($transitCity['city_id'], $transCityIds)) {
	  $transCityIds[] = $transitCity['city_id'];
	}

	//Set a mapping array to get the transit cities (as an array) for each departure.
        if($transitCity['dpt_id'] == $data['dpt_id']) {
	  $dptCities[$data['dpt_id']][] = array($transitCity['city_id'], $transitCity['transitcity_name']);
	}
      }
    }

    //Create js functions which return data.
    $js = array();
    $js = 'var odyssey = { '."\n";
    $js .= 'getDptIds : function() {'."\n";
    $js .= ' return '.json_encode($dptIds).';'."\n";
    $js .= '},'."\n";
    $js .= 'getTransCityIds : function() {'."\n";
    $js .= ' return '.json_encode($transCityIds).';'."\n";
    $js .= '},'."\n";
    $js .= 'getDepartures : function() {'."\n";
    $js .= ' return '.json_encode($departures).';'."\n";
    $js .= '},'."\n";
    $js .= 'getNbPsgr : function() {'."\n";
    $js .= ' return '.json_encode($nbPsgr).';'."\n";
    $js .= '},'."\n";
    $js .= 'getDptCities : function() {'."\n";
    $js .= ' return '.json_encode($dptCities).';'."\n";
    $js .= '},'."\n";
    $js .= 'getPeriods: function() {'."\n";
    $js .= ' return '.json_encode($periods).';'."\n";
    $js .= '}'."\n";
    $js .= '};'."\n\n";

    //Place the Javascript code into the html page header.
    $doc = JFactory::getDocument();
    $doc->addScriptDeclaration($js);

    //Store all the needed data as an array.
    $travelData = array();
    $travelData['travel'] = $travel;
    $travelData['travel_pricerules'] = $travelPriceRules;
    $travelData['transit_cities'] = $transitCities;

    return $travelData;
  }


  public function getSelectedTravel()
  {
    //Get the booking form set by the customer.
    $post = JFactory::getApplication()->input->post->getArray();
    //Sanitize variables got from the booking form.
    $travelId = (int)$post['travel_id'];
    $dptStepId = (int)$post['dpt_step_id'];
    $dptId = (int)$post['departures'];
    $psgrNb = (int)$post['nb_psgr'];
    $dptCityId = (int)$post['dpt_cities'];

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    //Get all the needed data in relation with the user's booking.
    $query->select('t.id AS travel_id, t.catid, t.dpt_step_id, t.name, t.alias, t.intro_text, t.travel_code, ds.dpt_id,'.
	           'tp.price AS travel_price, IFNULL(tcp.price, 0) AS transit_price, ds.date_time, ds.date_time_2, ds.max_passengers,ds.nb_days,'.
		   'ds.nb_nights, ds.allotment, s.date_type, tp.psgr_nb AS nb_psgr, stc.time_offset, tx.rate AS tax_rate, c.name AS dpt_city_name')
	  ->from('#__odyssey_travel AS t')
	  ->join('INNER', '#__odyssey_step AS s ON s.id=t.dpt_step_id')
	  ->join('INNER', '#__odyssey_departure_step_map AS ds ON ds.step_id=s.id AND ds.dpt_id='.$dptId)
	  ->join('LEFT', '#__odyssey_travel_price AS tp ON tp.travel_id=t.id AND tp.dpt_step_id=s.id'.
	                 ' AND tp.dpt_id=ds.dpt_id AND tp.psgr_nb='.$psgrNb)
	  ->join('LEFT', '#__odyssey_transit_city_price AS tcp ON tcp.travel_id=t.id AND tcp.dpt_step_id=s.id'.
	                 ' AND tcp.dpt_id=ds.dpt_id AND tcp.city_id='.$dptCityId.' AND tcp.psgr_nb='.$psgrNb)
	  ->join('LEFT', '#__odyssey_tax AS tx ON tx.id=t.tax_id')
	  ->join('LEFT', '#__odyssey_city AS c ON c.id='.$dptCityId)
	  ->join('LEFT', '#__odyssey_step_transit_city_map AS stc ON stc.dpt_step_id=s.id AND stc.dpt_id=ds.dpt_id AND stc.city_id='.$dptCityId)
	  ->where('t.id='.$travelId.' AND t.dpt_step_id='.$dptStepId);
    $db->setQuery($query);
    $travel = $db->loadAssoc();

    //Get the date picked by the customer.
    if($travel['date_type'] == 'period') {
      $travel['date_picker'] = $post['date_picker'];
    }

    //Check for possible matching price rules.
    $pruleIds = array();
    foreach($post as $key => $value) {
      //Search price rules matching the selected travel.
      if(preg_match('#^prule_'.$travel['dpt_id'].'_'.$travel['nb_psgr'].'_([0-9]+)$#', $key, $matches)) {
	$pruleIds[] = $value;
      }
    }

    if(!empty($pruleIds)) {
      //Add the price rule data to the travel.
      $travel = PriceruleHelper::getMatchingTravelPriceRules($pruleIds, $travel);
    }

    //TODO: Check data against the booking form variables.
    return $travel;
  }


  //Collect all the needed data to display the addons linked to the travel properly.
  public function getAddonData()
  {
    //Grab the user session.
    $session = JFactory::getSession();

    //A previously logged in user has just logged out (or a very odd error has occured).
    if(!$session->has('travel', 'odyssey')) {
      JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_users&view=login'));
      return false;
    }

    $travel = $session->get('travel', array(), 'odyssey'); 

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    //Get the step sequence ids.
    $query->select('step_id')
	  ->from('#__odyssey_step')
	  ->join('INNER', '#__odyssey_timegap_step_map ON step_id=id AND dpt_id='.(int)$travel['dpt_id'])
	  ->where('dpt_step_id='.(int)$travel['dpt_step_id'].' AND published=1')
	  ->group('id')
	  ->order('time_gap');
    $db->setQuery($query);
    $stepIds = $db->loadColumn();

    //Add the departure step at the beginning of the sequence.
    array_unshift($stepIds, $travel['dpt_step_id']);

    //Get the addons set in the steps of the sequence.
    //Note: The addon prices are based on the number of passengers selected by the user.
    $query->clear();
    $query->select('sa.step_id, s.name AS step_name, sa.addon_id, IFNULL(ts.time_gap, "000:00:00") AS time_gap, a.name, a.addon_type, a.group_nb,'.
	           'a.option_type, a.global, a.description, IFNULL(ap.price, 0) AS price, sa.ordering, h.nb_persons')
	  ->from('#__odyssey_step_addon_map AS sa')
	  ->join('INNER', '#__odyssey_addon AS a ON a.id=sa.addon_id')
	  ->join('LEFT', '#__odyssey_addon_hosting AS h ON h.addon_id=a.id')
	  ->join('LEFT', '#__odyssey_timegap_step_map AS ts ON ts.step_id=sa.step_id')
	  ->join('LEFT', '#__odyssey_addon_price AS ap ON ap.travel_id='.(int)$travel['travel_id'].' AND ap.step_id=sa.step_id'.
	                 ' AND ap.addon_id=sa.addon_id AND ap.dpt_id=sa.dpt_id AND ap.psgr_nb='.(int)$travel['nb_psgr'])
	  ->join('LEFT', '#__odyssey_step AS s ON s.id=sa.step_id')
	  ->where('sa.step_id IN('.implode(',', $stepIds).') AND sa.dpt_id='.(int)$travel['dpt_id'].' AND a.published=1')
	  //For hosting type addons we check that the number of passengers/persons is matching.
	  //Zero means no limit and IS NULL is to get the other addon types. 
	  ->where('(h.nb_persons='.(int)$travel['nb_psgr'].' OR h.nb_persons=0 OR h.nb_persons IS NULL)')
	  ->group('sa.step_id, sa.addon_id')
	  ->order('a.global DESC, ts.time_gap, sa.ordering');
    $db->setQuery($query);
    $addons = $db->loadAssocList();

    $addonIds = $addonOptions = array();

    foreach($addons as $key => $addon) {
      //Collect addon ids.
      if(!in_array($addon['addon_id'], $addonIds)) {
	$addonIds[] = $addon['addon_id'];
      }

      //Rename the step name of the global addons as they'll be separated from the
      //addons of the departure step.
      if($addon['global']) {
	$addons[$key]['step_name'] = JText::_('COM_ODYSSEY_GLOBAL_ADDONS_TITLE');
      }
    }

    if(!empty($addonIds)) {
      //Get the addon options linked to the addons previously retrieved.
      //Note: The addon option prices are based on the number of passengers selected by the user.
      $query->clear();
      $query->select('sa.step_id, ao.addon_id, ao.id AS addon_option_id, ao.name, ao.ordering, IFNULL(ap.price, 0) AS price')
	    ->from('#__odyssey_addon_option AS ao')
	    ->join('INNER', '#__odyssey_step_addon_map AS sa ON sa.addon_id=ao.addon_id')
	    ->join('LEFT', '#__odyssey_addon_option_price AS ap ON ap.travel_id='.(int)$travel['travel_id'].' AND ap.step_id=sa.step_id'.
			   ' AND ap.addon_id=ao.addon_id AND ap.addon_option_id=ao.id AND ap.dpt_id=sa.dpt_id'.
			   ' AND ap.psgr_nb='.(int)$travel['nb_psgr'])
	    ->where('ao.addon_id IN('.implode(',', $addonIds).') AND sa.step_id IN('.implode(',', $stepIds).')')
	    ->where('sa.dpt_id='.(int)$travel['dpt_id'].' AND ao.published=1')
	    ->group('sa.step_id, ao.addon_id, ao.id')
	    ->order('sa.step_id, ao.ordering');
//file_put_contents('debog_options.txt', print_r($query->__toString(), true));
      $db->setQuery($query);

      $addonOptions = $db->loadAssocList();
    }

    //Get the possible addon price rules linked to this travel.
    $addonPrules = PriceruleHelper::getAddonCatalogPriceRules($travel);

    //Prepare data.
    $data = array();
    $data['addons'] = $addons;
    $data['addon_options'] = $addonOptions;
    $data['addon_prules'] = $addonPrules['addons'];
    $data['addon_option_prules'] = $addonPrules['addon_options'];

    return $data;
  }


  //Get the data of the selected addons from the database.
  public function getSelectedAddons($selAddonIds, $selAddonOptionIds)
  {
    //No selected addons.
    if(empty($selAddonIds)) {
      return array();
    }

    //Grab the user session.
    $session = JFactory::getSession();

    //A previously logged in user has just logged out (or a very odd error has occured).
    if(!$session->has('travel', 'odyssey')) {
      JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_users&view=login'));
      return false;
    }

    $travel = $session->get('travel', array(), 'odyssey'); 

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    //Get the selected addons (or addons by default).
    $query->select('sa.step_id, IFNULL(ts.time_gap, "000:00:00") AS time_gap, sa.ordering, a.name, a.description,'.
	           'a.id AS addon_id, IFNULL(ap.price, 0) AS price')
	  ->from('#__odyssey_step_addon_map AS sa')
	  ->join('INNER', '#__odyssey_addon AS a ON a.id=sa.addon_id')
	  ->join('LEFT', '#__odyssey_timegap_step_map AS ts ON ts.step_id=sa.step_id')
	  ->join('LEFT', '#__odyssey_addon_price AS ap ON ap.travel_id='.(int)$travel['travel_id'].' AND ap.step_id=sa.step_id'.
		 ' AND ap.addon_id=sa.addon_id AND ap.dpt_id='.(int)$travel['dpt_id'].' AND ap.psgr_nb='.(int)$travel['nb_psgr']);

    //Get the data of the addons selected in each step.
    $where = '';
    foreach($selAddonIds as $stepId => $addonIds) {
      $where .= '(sa.step_id='.(int)$stepId.' AND sa.addon_id IN('.implode(',', $addonIds).')) OR ';
    }
    //Remove OR plus spaces from the end of the string.
    $where = substr($where, 0, -4);

    $query->where($where)
	  ->group('sa.step_id, sa.addon_id')
	  ->order('ts.time_gap, sa.ordering');
    $db->setQuery($query);
    $addons = $db->loadAssocList();

    if(!empty($selAddonOptionIds)) {
      //Get the selected addon options linked to the selected addons.
      $query->clear();
      $query->select('sa.step_id, ao.name, sa.addon_id, ao.id AS addon_option_id, ao.ordering, IFNULL(ap.price, 0) AS price')
	    ->from('#__odyssey_addon_option AS ao')
	    ->join('INNER', '#__odyssey_step_addon_map AS sa ON sa.addon_id=ao.addon_id')
	    ->join('LEFT', '#__odyssey_addon_option_price AS ap ON ap.travel_id='.(int)$travel['travel_id'].' AND ap.step_id=sa.step_id'.
			   ' AND ap.addon_id=sa.addon_id AND ap.dpt_id='.(int)$travel['dpt_id'].
			   ' AND ap.addon_option_id=ao.id AND ap.psgr_nb='.(int)$travel['nb_psgr']);

      //Get the data of the addon options selected in each addon (selected in each step).
      $where = '';
      foreach($selAddonOptionIds as $stepId => $addonIds) {
	foreach($addonIds as $addonId => $addonOptionIds) {
	  if(!empty($addonOptionIds)) {
	    $where .= '(sa.step_id='.(int)$stepId.' AND ao.addon_id='.(int)$addonId.
		      ' AND ao.id IN('.implode(',', $addonOptionIds).')) OR ';
	  }
	}
      }
      //Remove OR plus spaces from the end of the string.
      $where = substr($where, 0, -4);

      $query->where($where)
	    ->group('sa.step_id, sa.addon_id, ao.id')
	    ->order('sa.step_id, ao.ordering');
      $db->setQuery($query);
      $addonOptions = $db->loadAssocList();
    }
    else {
      $addonOptions = array();
    }

    //Insert the addon options into their corresponding addon.
    foreach($addons as $key => $addon) {
      //Create an options attribute.
      $addons[$key]['options'] = array();
      foreach($addonOptions as $addonOption) {
	if($addonOption['step_id'] == $addon['step_id'] && $addonOption['addon_id'] == $addon['addon_id']) {
	  $option = array();
	  //Keep only the relevant data.
	  $option['addon_option_id'] = $addonOption['addon_option_id'];
	  $option['name'] = $addonOption['name'];
	  $option['price'] = $addonOption['price'];

	  $addons[$key]['options'][] = $option;
	}
      }
    }

    $addons = PriceruleHelper::getMatchingAddonPriceRules($addons, $travel);

    return $addons;
  }


  /**
   * Increment the hit counter for the travel.
   *
   * @param   integer  $pk  Optional primary key of the travel to increment.
   *
   * @return  boolean  True if successful; false otherwise and internal error set.
   */
  public function hit($pk = 0)
  {
    $input = JFactory::getApplication()->input;
    $hitcount = $input->getInt('hitcount', 1);

    if($hitcount) {
      $pk = (!empty($pk)) ? $pk : (int) $this->getState('travel.id');

      $table = JTable::getInstance('Travel', 'OdysseyTable');
      $table->load($pk);
      $table->hit($pk);
    }

    return true;
  }
}

