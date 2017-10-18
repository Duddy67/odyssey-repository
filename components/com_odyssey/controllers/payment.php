<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

require_once JPATH_COMPONENT.'/helpers/order.php';
require_once JPATH_COMPONENT.'/helpers/travel.php';
require_once JPATH_COMPONENT_ADMINISTRATOR.'/helpers/utility.php';

/**
 * @package     Joomla.Site
 * @subpackage  com_odyssey
 */
class OdysseyControllerPayment extends JControllerForm
{
  //Create indexes which are going to be used by the plugin. 
  private $utility = array('payment_mode' => '',
			   'payment_result' => true,
			   'reply_and_exit' => '',     //In case of data remotely returned by the bank platform.
			   'payment_details' => '',
			   'transaction_data' => '',
			   'redirect_url' => '',
			   'plugin_output' => '',     //Html code to display in the payment view.
			   'offline_id' => 0          //Only used with Odyssey offline plugin.
			   );


  public function setBooking()
  {
    //Safety check.
    TravelHelper::checkBookingProcess();

    $user = JFactory::getUser();
    $post = $this->input->post->getArray();

    $session = JFactory::getSession();

    $travel = $session->get('travel', array(), 'odyssey'); 
    $addons = $session->get('addons', array(), 'odyssey'); 
    $passengers = $session->get('passengers', array(), 'odyssey'); 
    $settings = $session->get('settings', array(), 'odyssey'); 
    $submit = $session->get('submit', 0, 'odyssey'); 

    //Check for possible API plugin.
    //Note: Ensure no order id exists yet  which mean we're dealing with a brand new booking.
    if(!isset($travel['order_id']) && $settings['api_connector'] && $settings['api_plugin']) {
      //Trigger the plugin event.
      $event = 'onOdysseyApiConnectorFunction';
      JPluginHelper::importPlugin('odysseyapiconnector');
      $dispatcher = JDispatcher::getInstance();
      $results = $dispatcher->trigger($event, array('isDepartureAvailable', array($travel)));
      if(!$results[0]) {
	$this->setMessage(JText::_('COM_ODYSSEY_DEPARTURE_NOT_AVAILABLE'));
	$this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=travel&id='.$travel['travel_id'].'&catid='.$travel['catid'], false));
	return true;
      }
    }

    if(!$submit) {
      //Set immediately submit flag to 1 to prevent the multiple clicks syndrome.
      $session->set('submit', 1, 'odyssey'); 

      $travel['booking_option'] = $post['booking_options'];
      //It's the first booking for this travel, (ie: the customer might pay in several times so
      //he will come back later). 
      if(!isset($travel['final_amount'])) {
	//Compute the final amount for this travel.
	$travel['final_amount'] = TravelHelper::getFinalAmount($travel, $addons, $settings['digits_precision']);
      }

      if($travel['booking_option'] == 'deposit') {
	$travel['deposit_amount'] = $travel['final_amount'] * ((int)$settings['deposit_rate'] / 100);
	$travel['deposit_amount'] = UtilityHelper::formatNumber($travel['deposit_amount'], $settings['digits_precision']);
      }

      //It's the first booking for this travel so the order must be stored in database.
      if(!isset($travel['order_id'])) {
	//Call the required functions to get the order stored.
	$orderId = OrderHelper::storeOrder($travel, $addons, $settings);
	OrderHelper::storeTravel($travel, $orderId);
	OrderHelper::storePriceRules($travel, $orderId, 'travel');
	OrderHelper::storeAddons($addons, $orderId);
	OrderHelper::storePriceRules($addons, $orderId, 'addon');
	OrderHelper::setPassengers($passengers, $orderId);

	//Store the id of the newly created order.
	$travel['order_id'] = $orderId;

	//Create the order number.
	$Ymd = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->format('Y-m-d');
	$orderNb = $orderId.'-'.$Ymd;

	$db = JFactory::getDbo();
	$query = $db->getQuery(true);
	$query->update('#__odyssey_order')
	      ->set('order_nb='.$db->Quote($orderNb))
	      ->where('id='.(int)$orderId);
	$db->setQuery($query);
	$db->execute();
      }

      $session->set('travel', $travel, 'odyssey');

      //From now on the customer is no longer allowed to modify his booking.
      $session->set('locked', 1, 'odyssey');

      if($travel['booking_option'] == 'take_option') {
	//Go directly to the end of the process.
	$this->setRedirect('index.php?option='.$this->option.'&task=end.confirmOption');
      }
      else {
	//Just in case a previous temporary data for this order is still remaining.
	OrderHelper::deleteTemporaryData($travel['order_id']);
	//Move on to the payment part.
	$this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=payment', false));
      }

      return true;
    }
  }


  public function setPayment()
  {
    //Grab the user session and get the needed session variables.
    $session = JFactory::getSession();
    $travel = $session->get('travel', array(), 'odyssey'); 
    $addons = $session->get('addons', array(), 'odyssey'); 
    $settings = $session->get('settings', array(), 'odyssey'); 
    //Reset the safety variable, (previously used to store the order), to zero. 
    $session->set('submit', 0, 'odyssey'); 

    //Plugins are going to need some persistent way to keep the different steps
    //of the payment as well as few extra variables (eg: token) during the payment
    //process.
    //So we create a utility array in which plugins are able to store
    //or create if necessary any needed variable.
    if(is_null(OrderHelper::getTemporaryData($travel['order_id']))) {
      $this->createTemporaryData();
    }

    //Get all of the POST data.
    $post = $this->input->post->getArray();
    
    //Get the name of the payment/plugin chosen by the user.
    $paymentMode = $post['payment'];

    $offlineId = 0; //Only used for offline method payments. 

    //If an offline payment has been chosen we extract its id which is passed at
    //the end of the payment name (separated with an underscore).
    if(preg_match('#^offline_([0-9]+)$#', $paymentMode, $matches)) {
      $paymentMode = 'offline';
      $offlineId = $matches[1];
    }

    //Get (then set) the utility temporary data.
    $utility = OrderHelper::getTemporaryData($travel['order_id'], true);
    //Store the needed data for the payment process.
    $utility['payment_mode'] = $paymentMode; 
    $utility['offline_id'] = (int)$offlineId; 
    $this->updateUtility($travel['order_id'], $utility);

    //Build the name of the event to trigger according to the payment name of
    //the plugin.
    //Note: The first letter of the payment mode is uppercased.
    $event = 'onOdysseyPayment'.ucfirst($paymentMode);
    JPluginHelper::importPlugin('odysseypayment');
    $dispatcher = JDispatcher::getInstance();

    //Trigger the event.
    //Note: Parameters are not passed by reference (using an &) cause we don't
    //allow plugins to modify directly the temporary data, except for the utility array.
    //Warning: Plugins MUST NOT use the session variables to get or modify data. 
    $results = $dispatcher->trigger($event, array($travel, $addons, $settings, &$utility));

    //Store the utility array modified by the plugin.
    $this->updateUtility($travel['order_id'], $results[0]);

    if(!$results[0]['payment_result']) { //An error has occured.
      //Retrieve and display the error message set by the plugin.
      $message = $results[0]['payment_details'];
      //Reset the temporary data.
      $this->updateUtility($travel['order_id'], $this->utility);
      $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=payment', false), $message, 'error');
      return false;
    }

    //Plugin needs to redirect the user.
    if(!empty($results[0]['redirect_url'])) {
      $this->setRedirect($results[0]['redirect_url']);
      return true;
    }

    //Display plugin result in the payment view or display available payment 
    //plugins if output is empty.
    $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=payment', false));
    return true;
  }


  //During the exchanges with the payment plugin the user's session might be unavailable,
  //so the response() method uses only the temporary data.
  public function response()
  {
    //Grab the user session.
    $session = JFactory::getSession();
    $payment = $this->input->get('payment', '', 'string');

    //Check out whether the user's session is available then get the order id accordingly. 
    if(empty($travel = $session->get('travel', array(), 'odyssey'))) {
      $orderId = $this->getOrderIdFromBankData($payment);
    }
    else {
      $orderId = $travel['order_id'];
    }

    //Get the required variables from the temporary data.
    $tmpData = OrderHelper::getTemporaryData($orderId);
    $travel = $tmpData['travel']; 
    $addons = $tmpData['addons']; 
    $settings = $tmpData['settings']; 
    $utility = $tmpData['utility']; 

    $event = 'onOdysseyPayment'.ucfirst($payment).'Response';

    JPluginHelper::importPlugin('odysseypayment');
    $dispatcher = JDispatcher::getInstance();
    $results = $dispatcher->trigger($event, array($travel, $addons, $settings, &$utility));

    //Update the temporary utility data sent back by the plugin.
    $this->updateUtility($orderId, $results[0]);

    //Some bank platforms send a bunch of data to be checked by the component (security
    //token etc..).
    //The response is generaly a boolean value informing the bank platform whether 
    //the sent data is correct or not.
    //As the user is on the bank platform (and not on the website) when the data is sent,
    //there is nothing else to do but exit the program after replying.
    if(!empty($results[0]['reply_and_exit'])) { 
      $reply = $results[0]['reply_and_exit'];
      //Empty the field to prevent the script to exit again on the next triggered event.
      $results[0]['reply_and_exit'] = '';
      $this->updateUtility($orderId, $results[0]);

      echo $reply;
      exit;
    }

    //Plugin needs to redirect the user.
    if(!empty($results[0]['redirect_url'])) {
      $this->setRedirect($results[0]['redirect_url']);
      return true;
    }

    //Display plugin result in the payment view or display available payment 
    //plugins if output is empty.
    $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=payment', false));
    return true;
  }


  public function cancelPayment()
  {
    //Grab the user session.
    $session = JFactory::getSession();
    $travel = $session->get('travel', array(), 'odyssey'); 
    $utility = OrderHelper::getTemporaryData($travel['order_id'], true);

    $payment = $this->input->get->get('payment', '', 'string');
    $event = 'onOdysseyPayment'.ucfirst($payment).'Cancel';
    JPluginHelper::importPlugin('odysseypayment');
    $dispatcher = JDispatcher::getInstance();
    $results = $dispatcher->trigger($event, array($utility));

    //Reset the utility array.
    $this->updateUtility($travel['order_id'], $this->utility);

    //Display the available payment plugins.
    $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=payment', false));

    return true;
  }


  protected function createTemporaryData()
  {
    //Grab the user session.
    $session = JFactory::getSession();
    $travel = $session->get('travel', array(), 'odyssey'); 
    $addons = $session->get('addons', array(), 'odyssey'); 
    $settings = $session->get('settings', array(), 'odyssey'); 

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $now = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);

    $columns = array('order_id', 'travel', 'addons', 'settings', 'utility', 'created');
    $values = (int)$travel['order_id'].','.$db->quote(serialize($travel)).','.$db->quote(serialize($addons)).
              ','.$db->quote(serialize($settings)).','.$db->quote(serialize($this->utility)).','.$db->quote($now);

    $query->insert('#__odyssey_tmp_data')
	  ->columns($columns)
	  ->values($values);
    try {
      $db->setQuery($query);
      $db->execute();
    }
    catch(RuntimeException $e) {
      JFactory::getApplication()->enqueueMessage(JText::_($e->getMessage()), 'error');
      return 0;
    }
  }


  protected function updateUtility($orderId, $utility)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    $query->update('#__odyssey_tmp_data')
	  ->set('utility='.$db->Quote(serialize($utility)))
	  ->where('order_id='.(int)$orderId);
    $db->setQuery($query);
    $db->execute();
  }


  //Almost all of the bank platforms provide a dedicated field to store a specific data.
  //The Odyssey payment plugins use this field to store the id of the current order. The
  //name of this dedicated field is set in the plugin parameters through the
  //order_id_field variable. 
  private function getOrderIdFromBankData($payment)
  {
    //Get data sent by the bank platform through GET or POST.
    $data = $this->input->getArray();
    $orderId = 0;

    //Get the plugin name (remove the possible part after the underscore).
    preg_match('#^([0-9a-z]+)(_[0-9a-z]+)*#', $payment, $matches);
    //Get the plugin params.
    $plugin = JPluginHelper::getPlugin('odysseypayment', $matches[1]);
    $pluginParams = new JRegistry($plugin->params);
    //Get the field name where the order id is stored.
    $fieldName = $pluginParams->get('order_id_field');
    $fieldName = trim($fieldName);

    //Retrieve the order id.
    $orderId = $data[$fieldName];

    return $orderId;
  }
}


