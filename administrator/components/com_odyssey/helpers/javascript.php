<?php
/**
 * @package Odyssey component
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
require_once JPATH_ROOT.'/administrator/components/com_odyssey/helpers/odyssey.php';



class JavascriptHelper
{
  /**
   * Functions which define all required language variables in order to be
   * used in Javascript throught the Joomla.JText._() method. 
   */

  /**
   * Loads language variables (related to button text) to be used with Javascript.
   *
   * @return void
   */
  public static function getButtonText() 
  {
    JText::script('COM_ODYSSEY_BUTTON_ADD_LABEL'); 
    JText::script('COM_ODYSSEY_BUTTON_SELECT_LABEL'); 
    JText::script('COM_ODYSSEY_BUTTON_REMOVE_LABEL'); 

    return;
  }


  /**
   * Loads common language variables to be used with Javascript.
   *
   * @return void
   */
  public static function getCommonText() 
  {
    JText::script('COM_ODYSSEY_ITEM_NAME_LABEL'); 
    JText::script('COM_ODYSSEY_ITEM_NAME_TITLE'); 
    JText::script('COM_ODYSSEY_ITEM_CODE_LABEL'); 
    JText::script('COM_ODYSSEY_ITEM_CODE_TITLE'); 
    JText::script('COM_ODYSSEY_PUBLISHED_LABEL'); 
    JText::script('COM_ODYSSEY_PUBLISHED_TITLE'); 
    JText::script('COM_ODYSSEY_CITY_LABEL'); 
    JText::script('COM_ODYSSEY_CITY_TITLE'); 
    JText::script('COM_ODYSSEY_ITEM_QUANTITY_LABEL'); 
    JText::script('COM_ODYSSEY_ITEM_QUANTITY_TITLE'); 
    JText::script('COM_ODYSSEY_ITEM_AMOUNT_LABEL'); 
    JText::script('COM_ODYSSEY_ITEM_AMOUNT_TITLE'); 
    JText::script('COM_ODYSSEY_OPTION_SELECT'); 
    JText::script('COM_ODYSSEY_ERROR_EMPTY_VALUE'); 
    JText::script('COM_ODYSSEY_ERROR_INCORRECT_OR_EMPTY_VALUE'); 
    JText::script('COM_ODYSSEY_ERROR_INCORRECT_VALUE_TYPE'); 
    JText::script('COM_ODYSSEY_EXPECTED_VALUE_TYPE'); 
    JText::script('COM_ODYSSEY_YESNO_0'); 
    JText::script('COM_ODYSSEY_YESNO_1'); 
    JText::script('COM_ODYSSEY_ORDERING_LABEL'); 
    JText::script('COM_ODYSSEY_ORDERING_TITLE'); 
    JText::script('COM_ODYSSEY_ERROR_EMPTY_VALUE'); 
    JText::script('COM_ODYSSEY_REORDER_TITLE'); 
    JText::script('COM_ODYSSEY_ERROR_NUMBER_NOT_VALID'); 
    JText::script('COM_ODYSSEY_ERROR_NO_TAX_SELECTED'); 
    JText::script('COM_ODYSSEY_HEADING_DATETIME');
    JText::script('COM_ODYSSEY_HEADING_DEPARTURE_DATE');
    JText::script('COM_ODYSSEY_HEADING_CITY');
    JText::script('COM_ODYSSEY_HEADING_DEPARTURE_CITY');
    JText::script('COM_ODYSSEY_OPTION_SELECT_CITY');
    JText::script('JYES');
    JText::script('JNO');

    return;
  }


  /**
   * Loads language variables (related to travel item) to be used with Javascript.
   *
   * @return void
   */
  public static function getTravelText() 
  {
    JText::script('COM_ODYSSEY_ERROR_NO_STEP_SEQUENCE_SELECTED'); 
    JText::script('COM_ODYSSEY_DATABASE_ERROR_TRAVEL_UNIQUE_ALIAS'); 
    JText::script('COM_ODYSSEY_HEADING_MAX_PASSENGERS');
    JText::script('COM_ODYSSEY_HEADING_PRICE_PER_PASSENGER');
    JText::script('COM_ODYSSEY_OPTION');
    JText::script('COM_ODYSSEY_STEP_LABEL');
    JText::script('COM_ODYSSEY_ADDON_LABEL');
    JText::script('COM_ODYSSEY_TRANSIT_CITY_LABEL');
    JText::script('COM_ODYSSEY_NO_ADDON_FOUND');
    JText::script('COM_ODYSSEY_NO_TRANSIT_CITY_FOUND');
    JText::script('COM_ODYSSEY_INFO_GLOBAL_ADDON'); 
    JText::script('COM_ODYSSEY_IMAGE_ALT_LABEL'); 
    JText::script('COM_ODYSSEY_IMAGE_ALT_TITLE'); 
    JText::script('COM_ODYSSEY_IMAGE_ORDERING_LABEL'); 
    JText::script('COM_ODYSSEY_IMAGE_ORDERING_TITLE'); 

    return;
  }


  /**
   * Loads language variables (related to step item) to be used with Javascript.
   *
   * @return void
   */
  public static function getStepText() 
  {
    JText::script('COM_ODYSSEY_OPTION_JAN'); 
    JText::script('COM_ODYSSEY_OPTION_FEB'); 
    JText::script('COM_ODYSSEY_OPTION_MAR'); 
    JText::script('COM_ODYSSEY_OPTION_APR'); 
    JText::script('COM_ODYSSEY_OPTION_MAY'); 
    JText::script('COM_ODYSSEY_OPTION_JUN'); 
    JText::script('COM_ODYSSEY_OPTION_JUL'); 
    JText::script('COM_ODYSSEY_OPTION_AUG'); 
    JText::script('COM_ODYSSEY_OPTION_SEP'); 
    JText::script('COM_ODYSSEY_OPTION_OCT'); 
    JText::script('COM_ODYSSEY_OPTION_NOV'); 
    JText::script('COM_ODYSSEY_OPTION_DEC'); 
    JText::script('COM_ODYSSEY_OPTION_MON'); 
    JText::script('COM_ODYSSEY_OPTION_TUE'); 
    JText::script('COM_ODYSSEY_OPTION_WED'); 
    JText::script('COM_ODYSSEY_OPTION_THU'); 
    JText::script('COM_ODYSSEY_OPTION_FRI'); 
    JText::script('COM_ODYSSEY_OPTION_SAT'); 
    JText::script('COM_ODYSSEY_OPTION_SUN'); 
    JText::script('COM_ODYSSEY_FIELD_DAYS_LABEL'); 
    JText::script('COM_ODYSSEY_FIELD_TIME_LABEL'); 
    JText::script('COM_ODYSSEY_FIELD_HOURS_MINUTES_LABEL'); 
    JText::script('COM_ODYSSEY_STEP_ALIAS_LABEL'); 
    JText::script('COM_ODYSSEY_STEP_ALIAS_TITLE'); 
    JText::script('COM_ODYSSEY_ERROR_NO_CITY_SELECTED'); 
    JText::script('COM_ODYSSEY_ERROR_INVALID_DATETIME_VALUE'); 
    JText::script('COM_ODYSSEY_ERROR_DEPARTURE_STEP_MISSING');
    JText::script('COM_ODYSSEY_ERROR_INVALID_DAYS_VALUE');
    JText::script('COM_ODYSSEY_ERROR_INVALID_TIME_VALUE');
    JText::script('COM_ODYSSEY_ERROR_INVALID_TIME_GAP');
    JText::script('COM_ODYSSEY_ERROR_DEPARTURE_UNIQUE_GROUP_ALIAS');
    JText::script('COM_ODYSSEY_ERROR_INVALID_MAX_PASSENGERS_VALUE');
    JText::script('COM_ODYSSEY_ERROR_INVALID_ALLOTMENT_VALUE');
    JText::script('COM_ODYSSEY_ERROR_NO_DEPARTURE_DEFINED');
    JText::script('COM_ODYSSEY_HEADING_TIME_GAP');
    JText::script('COM_ODYSSEY_HEADING_STEP_ALIAS');
    JText::script('COM_ODYSSEY_HEADING_GROUPED');
    JText::script('COM_ODYSSEY_MAX_PASSENGERS_LABEL');
    JText::script('COM_ODYSSEY_MAX_PASSENGERS_TITLE');
    JText::script('COM_ODYSSEY_DEPARTURES_LABEL');
    JText::script('COM_ODYSSEY_DEPARTURES_TITLE');
    JText::script('COM_ODYSSEY_HOURS_MINUTES_MINUS_TITLE'); 
    JText::script('COM_ODYSSEY_HOURS_MINUTES_MINUS_LABEL'); 
    JText::script('COM_ODYSSEY_ALLOTMENT_LABEL'); 
    JText::script('COM_ODYSSEY_ALLOTMENT_TITLE'); 
    JText::script('COM_ODYSSEY_NB_DAYS_LABEL'); 
    JText::script('COM_ODYSSEY_NB_DAYS_TITLE'); 
    JText::script('COM_ODYSSEY_NB_NIGHTS_LABEL'); 
    JText::script('COM_ODYSSEY_NB_NIGHTS_TITLE'); 
    JText::script('COM_ODYSSEY_CODE_LABEL'); 
    JText::script('COM_ODYSSEY_CODE_TITLE'); 
    JText::script('COM_ODYSSEY_SUBTRACT_LABEL'); 
    JText::script('COM_ODYSSEY_SUBTRACT_TITLE'); 
    JText::script('COM_ODYSSEY_STATUS_TITLE'); 
    JText::script('COM_ODYSSEY_ERROR_NO_DEPARTURE_STEP_SELECTED'); 
    JText::script('COM_ODYSSEY_ERROR_NEW_DEPARTURE_STEP_NOT_SAVED'); 
    JText::script('COM_ODYSSEY_ERROR_INVALID_TIME_PERIOD'); 
    JText::script('COM_ODYSSEY_WARNING_DEPARTURE_STEP_USED_IN_TRAVEL'); 
    JText::script('COM_ODYSSEY_INFO_GLOBAL_ADDON'); 
    JText::script('COM_ODYSSEY_DATABASE_ERROR_TRAVEL_UNIQUE_CODE'); 

    return;
  }


  /**
   * Loads language variables (related to price rule item) to be used with Javascript.
   *
   * @return void
   */
  public static function getPriceruleText() 
  {
    JText::script('COM_ODYSSEY_HEADING_MAX_PASSENGERS');
    JText::script('COM_ODYSSEY_HEADING_PRICERULE_PER_PASSENGER');
    JText::script('COM_ODYSSEY_PASSENGER_NB_LABEL'); 
    JText::script('COM_ODYSSEY_OPTION_SELECT_DEPARTURE'); 
    JText::script('COM_ODYSSEY_OPTION_TRAVEL'); 
    JText::script('COM_ODYSSEY_OPTION_TRAVEL_CAT'); 
    JText::script('COM_ODYSSEY_OPTION_ADDON'); 
    JText::script('COM_ODYSSEY_OPTION_ADDON_OPTION'); 
    JText::script('COM_ODYSSEY_OPTION_CART_AMOUNT'); 
    JText::script('COM_ODYSSEY_PASSENGER_NUMBERS_LABEL'); 
    JText::script('COM_ODYSSEY_TRAVEL_IDS_LABEL'); 
    JText::script('COM_ODYSSEY_DEPARTURE_NUMBERS_LABEL'); 
    JText::script('COM_ODYSSEY_NUMBER_LIST_TITLE'); 
    JText::script('COM_ODYSSEY_STEP_IDS_LABEL'); 
    JText::script('COM_ODYSSEY_ITEM_QUANTITY_LABEL'); 
    JText::script('COM_ODYSSEY_ITEM_QUANTITY_TITLE'); 
    JText::script('COM_ODYSSEY_ITEM_AMOUNT_LABEL'); 
    JText::script('COM_ODYSSEY_ITEM_AMOUNT_TITLE'); 
    JText::script('COM_ODYSSEY_COMPARISON_OPERATOR_LABEL'); 
    JText::script('COM_ODYSSEY_COMPARISON_OPERATOR_TITLE'); 
    JText::script('COM_ODYSSEY_ERROR_NO_RECIPIENT_SELECTED'); 
    JText::script('COM_ODYSSEY_ERROR_NO_TARGET_SELECTED'); 
    JText::script('COM_ODYSSEY_ERROR_NO_CONDITION_SELECTED'); 
    JText::script('COM_ODYSSEY_ERROR_INCORRECT_OR_EMPTY_VALUE'); 
    JText::script('COM_ODYSSEY_ERROR_TRAVEL_DUPLICATE_ENTRY'); 

    return;
  }


  /**
   * Build and load Javascript functions which return different kind of data,
   * generaly as a JSON array.
   *
   * @param array Array containing the names of the functions to build and load.
   * @param array Array of possible arguments to pass to the PHP functions.
   * @param string Data returned as a string by the getData JS function.
   *
   * @return void
   */
  public static function loadFunctions($names, $args = array(), $data = '')
  {
    $js = array();
    //Create a name space in order put functions into it.
    $js = 'var odyssey = { '."\n";

    //Include the required functions.

    //Returns region names and codes used to build option tags.
    if(in_array('region', $names)) {
      $regions = JavascriptHelper::getRegions();
      $js .= 'getRegions: function() {'."\n";
      $js .= ' return '.$regions.';'."\n";
      $js .= '},'."\n";
    }

    //Returns country names and codes used to build option tags.
    if(in_array('country', $names)) {
      $countries = JavascriptHelper::getCountries();
      $js .= 'getCountries: function() {'."\n";
      $js .= ' return '.$countries.';'."\n";
      $js .= '},'."\n";
    }

    //Returns city names and ids used to build option tags.
    if(in_array('city', $names)) {
      $cities = JavascriptHelper::getCities();
      $js .= 'getCities: function() {'."\n";
      $js .= ' return '.$cities.';'."\n";
      $js .= '},'."\n";
    }

    //Returns the id of the current user.
    if(in_array('user', $names)) {
      $user = JFactory::getUser();
      $js .= 'getUserId: function() {'."\n";
      $js .= ' return '.$user->id.';'."\n";
      $js .= '},'."\n";
    }

    //Functions used to access an item directly from an other item.
    if(in_array('shortcut', $names)) {
      $js .= 'shortcut: function(itemId, task) {'."\n";
	       //Set the id of the item to edit.
      $js .= ' var shortcutId = document.getElementById("jform_shortcut_id");'."\n";
	       //This id will be retrieved in the overrided functions of the controller
	       //(ie: checkin and cancel functions).
      $js .= ' shortcutId.value = itemId;'."\n";
      $js .= ' Joomla.submitbutton(task);'."\n";
      $js .= '},'."\n";
    }

    //Return the departures of a given link or departure step 
    //in order to be used with an addon item. 
    if(in_array('addonDepartures', $names)) {
      $addonDepartures = JavascriptHelper::getAddonDepartures($args);
      $js .= 'getAddonDepartures: function() {'."\n";
      $js .= ' return '.$addonDepartures.';'."\n";
      $js .= '},'."\n";
    }

    //Return the passengers linked to a customer.
    if(in_array('passengers', $names)) {
      $passengers = JavascriptHelper::getPassengers($args[0]);
      $js .= 'getPassengers: function() {'."\n";
      $js .= ' return '.$passengers.';'."\n";
      $js .= '},'."\n";
    }

    //Return the required attribute names for the passengers.
    if(in_array('passengerAttributes', $names)) {
      $attributes = JavascriptHelper::getPassengerAttributes();
      $js .= 'getPassengerAttributes: function() {'."\n";
      $js .= ' return '.$attributes.';'."\n";
      $js .= '},'."\n";
    }

    //Build a generic Javascript function which return any data as a string.
    if(in_array('data', $names)) {
      $js .= 'getData: function() {'."\n";
      $js .= ' return "'.$data.'";'."\n";
      $js .= '},'."\n";
    }

    //Remove coma from the end of the string, (-2 due to the carriage return "\n").
    $js = substr($js, 0, -2); 

    $js .= '};'."\n\n";

    //Place the Javascript code into the html page header.
    $doc = JFactory::getDocument();
    $doc->addScriptDeclaration($js);

    return;
  }


  /**
   * Returns region codes and names as a JSON array.
   *
   * @return JSON array
   */
  public static function getRegions()
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Get all the regions from the region list.
    $query->select('r.country_code, r.id_code, r.lang_var')
	  ->from('#__odyssey_region AS r')
	  //Get only regions which country they're linked with is published (to minimized
	  //the number of regions to load).
	  ->join('LEFT', '#__odyssey_country AS c ON r.country_code=c.alpha_2')
	  ->where('c.published=1');
    $db->setQuery($query);
    $results = $db->loadObjectList();

    //Build the regions array.
    $regions = array();
    //Set text value in the proper language.
    foreach($results as $result) {
      //Add the country code to the region name to get an easier search.
      $regions[] = array('code' => $result->id_code, 'text' => $result->country_code.' - '.JText::_($result->lang_var));
    }

    return json_encode($regions);
  }


  /**
   * Returns country ids and names as a JSON array.
   *
   * @return JSON array
   */
  public static function getCountries()
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Get all the countries from the country list.
    $query->select('alpha_2 AS code, lang_var AS text')
	  ->from('#__odyssey_country')
	  ->where('published=1');
    $db->setQuery($query);
    $countries = $db->loadAssocList();

    //Set text value in the proper language.
    foreach($countries as $key => $country) {
      $countries[$key]['text'] = JText::_($country['text']);
    }

    return json_encode($countries);
  }


  /**
   * Returns city ids and names as a JSON array.
   *
   * @return JSON array
   */
  public static function getCities()
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Get all the published cities from the table.
    $query->select('id,name,lang_var,country_code,region_code')
	  ->from('#__odyssey_city')
	  ->where('published=1');
    $db->setQuery($query);
    $results = $db->loadObjectList();

    $cities = array();
    foreach($results as $result) {
      $text = $result->name;
      //Check for language variable.
      if(!empty($result->lang_var)) {
	//Set text value in the proper language.
	$text = JText::_($result->lang_var);
      }

      $codes = ' ('.$result->country_code.' - '.$result->region_code.')';
      $city = array('id' => $result->id, 'text' => $text.$codes);

      $cities[] = $city;
    }

    return json_encode($cities);
  }


  /**
   * Return the departures of a given link or departure step 
   * in order to be used with an addon item. 
   *
   * @param array Array of arguments.
   *
   * @return JSON array
   */
  public static function getAddonDepartures($args)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    $stepId = $args[0];
    $dptStepId = $args[1];
    $stepType = $args[2];

    $query->select('d.dpt_id')
	  ->from('#__odyssey_departure_step_map AS d');

    if($stepType == 'link') {
      $query->select('IFNULL(tg.dpt_id, "") AS active')
	    ->join('LEFT', '#__odyssey_timegap_step_map AS tg ON tg.dpt_id=d.dpt_id AND tg.step_id='.(int)$stepId);
    }

    $query->where('d.step_id='.(int)$dptStepId)
	  ->order('d.date_time');
    $db->setQuery($query);
    $addonDepartures = $db->loadAssocList();

    foreach($addonDepartures as $key => $addonDeparture) {
      $addonDepartures[$key]['selected'] = ''; 
      if($stepType == 'departure') {
	$addonDepartures[$key]['active'] = 1; 
      }
    }

    return json_encode($addonDepartures);
  }


  /**
   * Returns continent ids and names as a JSON array.
   *
   * @return JSON array
   */
  public static function getContinents()
  {
    //Since continents are few in number we dont need to spend a db table for them. 
    //We simply store their data into an array.
    $continents = array();
    $continents[] = array('code'=>'AF','text'=>'COM_ODYSSEY_LANG_CONTINENT_AF');
    $continents[] = array('code'=>'AN','text'=>'COM_ODYSSEY_LANG_CONTINENT_AN');
    $continents[] = array('code'=>'AS','text'=>'COM_ODYSSEY_LANG_CONTINENT_AS');
    $continents[] = array('code'=>'EU','text'=>'COM_ODYSSEY_LANG_CONTINENT_EU');
    $continents[] = array('code'=>'OC','text'=>'COM_ODYSSEY_LANG_CONTINENT_OC');
    $continents[] = array('code'=>'NA','text'=>'COM_ODYSSEY_LANG_CONTINENT_NA');
    $continents[] = array('code'=>'SA','text'=>'COM_ODYSSEY_LANG_CONTINENT_SA');

    //Set text value in the proper language.
    foreach($continents as &$continent) {
      $continent['text'] = JText::_($continent['text']);
    }

    return json_encode($continents);
  }


  /**
   * Get the passengers added by a given customer. 
   *
   * @param integer The id of the customer.
   * @param boolean Flag which specifies if the returned array must be encoded in JSON.
   *
   * @return mixed A JSON array or a regular array.
   */
  public static function getPassengers($customerId, $json = true)
  {
    $attributes = JavascriptHelper::getPassengerAttributes(false);
    $select = '';

    //Add a prefix to the attributes.
    foreach($attributes['attributes'] as $attribute) {
      $select .= 'p.'.$attribute.',';
    }

    //Check if an address is linked to passengers. 
    foreach($attributes['address'] as $value) {
      $select .= 'a.'.$value.',';
    }
    //Remove comma from the end of the string.
    $select = substr($select, 0, -1);

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Get the passengers linked to the customer id.
    $query->select($select)
	  ->from('#__odyssey_passenger AS p');

    //Join over the address table.
    if(!empty($attributes['address'])) {
      $query->join('LEFT', '#__odyssey_address AS a ON a.item_id=p.id AND a.item_type="passenger"');
    }

    $query->where('p.customer_id='.(int)$customerId.' AND customer=0');
    $db->setQuery($query);
    $passengers = $db->loadAssocList();

    if(!$json) {
      return $passengers;
    }

    return json_encode($passengers);
  }


  /**
   * Return the required attribute names for the passengers.
   *
   * @param boolean Flag which specifies if the returned array must be encoded in JSON.
   *
   * @return mixed A JSON array or a regular array.
   */
  public static function getPassengerAttributes($json = true)
  {
    //Get the passenger ini file in which some settings are defined.
    $psgrIni = parse_ini_file(OdysseyHelper::getOverridedFile(JPATH_ROOT.'/administrator/components/com_odyssey/models/forms/passenger.ini'));
    $attributes = $psgrIni['attributes'];
    $address = $result = array();

    //The set of attributes must be slighly modified to be used with the calling functions.
    foreach($attributes as $key => $attribute) {
      if($attribute == 'customer_id') {
	//Change the attribute name.
	$attributes[$key] = 'id';
      }
      elseif($attribute == 'customer') {
	//The customer attribute is unwanted.
	unset($attributes[$key]);
      }
    }

    $result['attributes'] = $attributes;

    if($psgrIni['is_address']) {
      $address = $psgrIni['address'];
      $attributes = array_merge($attributes, $address);
    }

    $result['address'] = $address;

    if(!$json) {
      return $result;
    }

    return json_encode($attributes);
  }

}


