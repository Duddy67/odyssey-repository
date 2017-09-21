<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.


class UtilityHelper
{
  public static function combinePriceRows($priceRows)
  {
    //price per departure multiplied with the number of passenger
    //Reformat the price rows data array when prices have been defined 
    //in order to be used with the js functionalities.
    $tmp = $pricePerPsgr = array();
    $total = count($priceRows);
    foreach($priceRows as $key => $priceRow) {
      //If a language variable is set we use it.
      if(!empty($priceRow['lang_var'])) {
	$priceRow['city'] = JText::_($priceRow['lang_var']);
      }

      if(!empty($priceRow['price'])) {
	//Store each price per passenger in an array.
	//Note: Use the passenger number as array index.
	$pricePerPsgr[$priceRow['psgr_nb']] = UtilityHelper::formatNumber($priceRow['price']);

	//It's the first price row for this departure.
	if($priceRow['psgr_nb'] == 1) {
	  //Store the departure data.
	  $tmp[] = $priceRow;
	  //Remove useless variable.
	  $currentId = count($tmp) - 1;
	  unset($tmp[$currentId]['price']);
	  unset($tmp[$currentId]['psgr_nb']);
	}

	//It's the last price row for this departure.
	if($priceRow['psgr_nb'] == $priceRow['max_passengers']) {
	  //Add the price array for all the passenger in the current row.
	  $tmp[count($tmp) - 1]['price_per_psgr'] = $pricePerPsgr;
	  //Reset the price array for the next departure.
	  $pricePerPsgr = array();
	}

	//Handle the case where the max_passengers value has been increased in the
	//departure step but no price rows for this passenger numbers has been stored yet.
	if(($key + 1 == $total || $priceRows[$key + 1]['psgr_nb'] < $priceRow['psgr_nb']) && $priceRow['psgr_nb'] < $priceRow['max_passengers']) {
//file_put_contents('debog_test.txt', print_r($priceRow, true),FILE_APPEND);
	  $psgrNbToadd = $priceRow['max_passengers'] - $priceRow['psgr_nb'];
	  //Add the missing passenger number prices and set them to zero.
	  for($i = 0; $i < $psgrNbToadd; $i++) {
	    $psgrNb = $priceRow['psgr_nb'] + $i + 1;
	    $pricePerPsgr[$psgrNb] = '0.00';
	  }

	  //Add the price array for all the passenger in the current row.
	  $tmp[count($tmp) - 1]['price_per_psgr'] = $pricePerPsgr;
	  //Reset the price array for the next departure.
	  $pricePerPsgr = array();
	}
      }
      else { //No prices has been set yet.
	//Store the departure data.
	$tmp[] = $priceRow;
	//Remove useless variable.
	$currentId = count($tmp) - 1;
	unset($tmp[$currentId]['price']);
	unset($tmp[$currentId]['psgr_nb']);

	//Create a zero price for each passenger number.
	for($i = 0; $i < $priceRow['max_passengers']; $i++) {
	  $psgrNb = $i + 1;
	  $pricePerPsgr[$psgrNb] = '0.00';
	}

	//Add the price array to the price row.
	$tmp[$currentId]['price_per_psgr'] = $pricePerPsgr;
	//Add the price array for all the passenger in the current row.
	$tmp[count($tmp) - 1]['price_per_psgr'] = $pricePerPsgr;
	//Reset the price array for the next departure.
	$pricePerPsgr = array();
      }
    }

    if(!empty($tmp)) {
      //Rename the array with the combined data.
      $priceRows = $tmp;
    }

    return $priceRows;
  }


  public static function getPriceWithTaxes($price, $taxRate)
  {
    if($price == 0 || $taxRate == 0) {
      return $price;
    }

    $taxValue = $price * ($taxRate / 100);
    $priceWithTaxes = $price + $taxValue;

    return $priceWithTaxes;
  }


  //Extract a given tax rate from a price.
  //In order to achieve this we use the following formula:
  //Example 1: For a given tax rate of 19.6 % an a price with taxes of 15 €
  //           Price without taxes: 15/1.196 = 12.54 €
  //
  //Example 2: For a given tax rate of 5.5 % an a price with taxes of 15 €
  //           Price without taxes: 15/1.055 = 14.22 €
  //
  //Source: http://vosdroits.service-public.fr/professionnels-entreprises/F24271.xhtml
  public static function getPriceWithoutTaxes($price, $taxRate)
  {
    if($price == 0 || $taxRate == 0) {
      return $price;
    }

    $dotPosition = strpos($taxRate, '.');
    $dotlessNb = preg_replace('#\.#', '', $taxRate);

    if($dotPosition == 1) {
      $divisor = '1.0'.$dotlessNb;
    }
    else { //$dotPosition == 2
      $divisor = '1.'.$dotlessNb;
    }

    //Retrieve product price without taxes.
    $priceWithoutTaxes = $price / $divisor;

    return $priceWithoutTaxes;
  }


  public static function roundNumber($float, $roundingRule = 'down', $digitPrecision = 2)
  {
    //In case variable passed in argument is undefined.
    if($float == '') {
      return 0;
    }

    switch($roundingRule) {
      case 'up':
	return round($float, $digitPrecision, PHP_ROUND_HALF_UP);

      case 'down':
	return round($float, $digitPrecision, PHP_ROUND_HALF_DOWN);

     default: //Unknown value.
	return $float;
    }
  }


  public static function formatNumber($float, $digits = 2)
  {
    //In case variable passed in argument is undefined.
    if($float == '') {
      return 0;
    }

    if(preg_match('#^-?[0-9]+\.[0-9]{'.$digits.'}#', $float, $matches)) {
      $formatedNumber = $matches[0]; 
    }
    else { //In case float number is truncated (for instance: 18.5 or 18).
      $dot = $padding = '';
      //Dot is added if there's only the left part of the float. 
      if(!preg_match('#\.#', $float)) {
	$missingDigits = $digits;
	$dot = '.';
      }

      //Compute how many digits are missing.
      if(preg_match('#^-?[0-9]+\.([0-9]+)#', $float, $matches)) {
	$missingDigits =  $digits - strlen($matches[1]);
      }

      //Replace missing digits with zeros. 
      for($i = 0; $i < $missingDigits; $i++) {
	$padding .= '0';
      }

      $formatedNumber = $float.$dot.$padding;
    }

    return $formatedNumber;
  }


  //Return the requested currency or the currency set by default for 
  //the application if the alpha argument is not defined.
  public static function getCurrency($alpha = '') 
  {
    $parameters = JComponentHelper::getParams('com_odyssey');
    $display = $parameters->get('currency_display');

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('alpha,symbol')
	  ->from('#__odyssey_currency');

    if($alpha) { //Get the required currency.
      $query->where('alpha='.$db->Quote($alpha));
    }
    else { //Get the default currency.
      $query->where('alpha='.$db->Quote($parameters->get('currency_code')));
    }

    $db->setQuery($query);
    $currency = $db->loadObject();

    //Return currency in the correct display.
    return $currency->$display;
  }


  public static function formatPriceRule($operation, $value, $alpha = '')
  {
    //Price rule operation is expressed as a percentage (-% or +%).
    if(preg_match('#(-|\+)%$#', $operation, $matches)) {
      //Return the price rule operation well formatted, (eg: -10 %)
      return $matches[1][0].UtilityHelper::formatNumber($value).' %';
    }

    //Price rule operation is expressed as an absolute value.

    //Get the currency and return the price rule operation well
    //formatted, (eg: -30 USD)
    $currency = UtilityHelper::getCurrency($alpha);

    return $operation.UtilityHelper::formatNumber($value).' '.$currency;
  }


  public static function getRemainingDays($endDate, $startDate = '')
  {
    if(empty($startDate)) {
      //Use now date.
      $startDate = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);
    }

    $date1 = new DateTime($startDate);
    $date2 = new DateTime($endDate);

    //Up to date.
    if($date1 > $date2) {
      return false;
    }

    return $date1->diff($date2)->days;
  }


  public static function getLimitDate($nbDays, $from = '', $add = true, $format = '')
  {
    if(empty($from)) {
      //Use now date.
      $from = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);
    }

    $date = new DateTime($from);

    if($add) {
      $date->add(new DateInterval('P'.$nbDays.'D'));
    }
    else {
      $date->sub(new DateInterval('P'.$nbDays.'D'));
    }

    if(empty($format)) {
      $format = 'Y-m-d H:i';
    }

    return $date->format($format);
  }


  public static function getFactoryFilePath()
  {
    //Path to the factory.php file before the 3.8.0 Joomla's version.
    $factoryFilePath = 'libraries/joomla/factory.php';

    $jversion = new JVersion();
    //Check Joomla's version.
    if($jversion->getShortVersion() >= '3.8.0') {
      //Set to the file new location.
      $factoryFilePath = 'libraries/src/Factory.php';
    }

    return $factoryFilePath;
  }
}

