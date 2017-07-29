<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

//Since this file is called directly and it doesn't belong to any component, 
//module or plugin, we need first to initialize the Joomla framework in order to use 
//the Joomla API methods.
 
//Initialize the Joomla framework
define('_JEXEC', 1);
//First we get the number of letters we want to substract from the path.
$length = strlen('/components/com_odyssey');
//Turn the length number in a negative value.
$length = $length - ($length * 2);
//
define('JPATH_BASE', substr(dirname(__DIR__), 0, $length));

//Get the required files
require_once (JPATH_BASE.'/includes/defines.php');
require_once (JPATH_BASE.'/includes/framework.php');
//We need to use Joomla's database class 
require_once (JPATH_BASE.'/libraries/joomla/factory.php');
require_once (JPATH_BASE.'/components/com_odyssey/helpers/travel.php');
require_once (JPATH_BASE.'/administrator/components/com_odyssey/helpers/utility.php');
//Create the application
$mainframe = JFactory::getApplication('site');

//Get the parameters passed through the url.
$jinput = JFactory::getApplication()->input;
$orderId = $jinput->get('order_id', 0, 'uint');
$task = $jinput->get('task', '', 'str');
$langTag = $jinput->get('lang_tag', '', 'str');

//file_put_contents('debog_file_task.txt', print_r($orderId.':'.$task, true)); 

//Get the needed data.
$db = JFactory::getDbo();
$query = $db->getQuery(true);
$query->select('o.customer_id, o.outstanding_balance, o.final_amount, o.nb_psgr, o.order_details,'.
	       'o.currency_code, o.limit_date, o.created, t.name AS travel_name, t.departure_date, c.firstname, u.name AS lastname')
      ->from('#__odyssey_order AS o')
      ->join('LEFT', '#__odyssey_order_travel AS t ON t.order_id=o.id')
      ->join('LEFT', '#__users AS u ON u.id=o.customer_id')
      ->join('LEFT', '#__odyssey_customer AS c ON c.id=u.id')
      ->where('o.id='.(int)$orderId);
$db->setQuery($query);
$result = $db->loadObject();

//In order to work with JText we have to load the language.
//Note: As we load language from an external file the site language cannot be properly
//identified and we end up with the en-GB tag by default.
$lang = JFactory::getLanguage();
//Check the lang tag parameter has been properly retrieved.
if(empty($langTag)) {
    //If not, we'll use english by default.
    $langTag = $lang->getTag();
}
//Load language.
$lang->load('com_odyssey', JPATH_ROOT.'/components/com_odyssey', $langTag, true);

$parameters = JComponentHelper::getParams('com_odyssey');

//Initialise some variables.

//As we're in an external file we have to remove the last part of the path to get the
//website url.
$length = strlen('components/com_odyssey/helpers/');
$length = $length - ($length * 2);
$websiteUrl = substr(JURI::root(), 0, $length);

$finalAmount = UtilityHelper::formatNumber($result->final_amount).' '.$result->currency_code;
$limitDate = JHTML::_('date', $result->limit_date, JText::_('DATE_FORMAT_LC2'));

if($task == 'deposit_reminder' || $task == 'warning_payment') {
  $outstandingBalance = UtilityHelper::formatNumber($result->outstanding_balance).' '.$result->currency_code;
  $remainingPayment = $result->final_amount - $result->outstanding_balance;
  $remainingPayment = UtilityHelper::formatNumber($remainingPayment).' '.$result->currency_code;
}


//Nothing has been paid or the remaining payment is missing.
if($result->outstanding_balance == $result->final_amount || $result->outstanding_balance > 0) {
  //Set the corresponding subject and body.
  switch($task) {
    case 'option_reminder':
      $subject = JText::sprintf('COM_ODYSSEY_EMAIL_OPTION_REMINDER_SUBJECT', $result->travel_name);
      $body = JText::sprintf('COM_ODYSSEY_EMAIL_OPTION_REMINDER_BODY', $result->firstname, $result->lastname,
								       $result->travel_name, 
								       $limitDate,
								       $result->order_details,
								       $finalAmount, $websiteUrl);
      //Prepare the body to send to the administrator. 
      $adminBody = JText::sprintf('COM_ODYSSEY_EMAIL_OPTION_REMINDER_ADMIN_BODY', $result->firstname, $result->lastname,
										  $result->travel_name, 
										  $limitDate,
										  $result->order_details,
										  $finalAmount, $websiteUrl);
      break;

    case 'cancelling_option':
      $subject = JText::sprintf('COM_ODYSSEY_EMAIL_CANCELLING_OPTION_SUBJECT', $result->travel_name);
      $body = JText::sprintf('COM_ODYSSEY_EMAIL_CANCELLING_OPTION_BODY', $result->firstname, $result->lastname,
									 $result->travel_name, 
									 $limitDate,
									 $result->order_details,
									 $finalAmount, $websiteUrl);
      //Prepare the body to send to the administrator. 
      $adminBody = JText::sprintf('COM_ODYSSEY_EMAIL_CANCELLING_OPTION_ADMIN_BODY', $result->firstname, $result->lastname,
										    $result->travel_name, 
										    $limitDate,
										    $result->order_details,
										    $finalAmount, $websiteUrl);
      break;

    case 'deposit_reminder':
      $subject = JText::sprintf('COM_ODYSSEY_EMAIL_DEPOSIT_REMINDER_SUBJECT', $result->travel_name);
      $body = JText::sprintf('COM_ODYSSEY_EMAIL_DEPOSIT_REMINDER_BODY', $result->firstname, $result->lastname,
	                                                                $outstandingBalance,
								        $result->travel_name, 
								        $remainingPayment,
								        $limitDate,
								        $result->order_details,
								        $finalAmount, $websiteUrl);
      //Prepare the body to send to the administrator. 
      $adminBody = JText::sprintf('COM_ODYSSEY_EMAIL_DEPOSIT_REMINDER_ADMIN_BODY', $result->firstname, $result->lastname,
										   $outstandingBalance,
										   $result->travel_name, 
										   $remainingPayment,
										   $limitDate,
										   $result->order_details,
										   $finalAmount, $websiteUrl);
      break;

    case 'warning_payment':
      $subject = JText::sprintf('COM_ODYSSEY_EMAIL_WARNING_PAYMENT_SUBJECT', $result->travel_name);
      $body = JText::sprintf('COM_ODYSSEY_EMAIL_WARNING_PAYMENT_BODY', $result->firstname, $result->lastname,
	                                                               $outstandingBalance,
								       $result->travel_name, 
								       $remainingPayment,
								       $limitDate,
								       $result->order_details,
								       $finalAmount, $websiteUrl);
      //Prepare the body to send to the administrator. 
      $adminBody = JText::sprintf('COM_ODYSSEY_EMAIL_WARNING_PAYMENT_ADMIN_BODY', $result->firstname, $result->lastname,
										  $outstandingBalance,
										  $result->travel_name, 
										  $remainingPayment,
										  $limitDate,
										  $result->order_details,
										  $finalAmount, $websiteUrl);
      break;
  }

  $message = array('subject' => $subject, 'body' => $body);
  //Send the appropriate email to the customer.
  TravelHelper::sendEmail($task, $result->customer_id, $orderId, false, $message);

  $message = array('subject' => $subject, 'body' => $adminBody);
  //Send the appropriate email to the administrator.
  TravelHelper::sendEmail($task, 0, $orderId, true, $message);

  if($task == 'cancelling_option') {
    //Cancel statusses.
    $fields = array('order_status="cancelled"','payment_status="cancelled"');
    $query->clear();
    $query->update('#__odyssey_order')
	  ->set($fields)
	  ->where('id='.(int)$orderId);
    $db->setQuery($query);
    $db->execute();
  }
}
else {
  $query->clear();
  $query->update('#__odyssey_order')
	->set('limit_date="0000-00-00 00:00:00"')
	->where('id='.(int)$orderId);
  $db->setQuery($query);
  $db->execute();
}

/*
//For testing purpose.
$fp = fopen('at-test.txt', 'w');
fwrite($fp, $orderId.' '.$msg);
fclose($fp);*/


