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
  public function setBooking()
  {
    TravelHelper::checkBookingProcess();

    $user = JFactory::getUser();
    $post = $this->input->post->getArray();

    $session = JFactory::getSession();

    $travel = $session->get('travel', array(), 'odyssey'); 
    $addons = $session->get('addons', array(), 'odyssey'); 
    $passengers = $session->get('passengers', array(), 'odyssey'); 
    $settings = $session->get('settings', array(), 'odyssey'); 
    $submit = $session->get('submit', 0, 'odyssey'); 

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

    //Get all the amounts (cart, shipping etc...).
    //$amounts = $this->getAmounts();

    //Plugins are going to need some persistent way to keep the different steps
    //of the payment as well as few extra variables (eg: token) during the payment
    //process.
    //We also  provide a dedicated variable into which plugins can store their
    //html output in order to display it in the payment view.  
    //So we create a session utility array in which plugins are able to store
    //or create if necessary any needed variable.
    if(!$session->has('utility', 'odyssey')) {
      //Create indexes which are going to use by the controller. 
      $utility = array('payment_mode'=> '',
	               'payment_result'=> true,
		       //The plugin must indicate wether it has created a transaction for this order.
	               'transaction_created'=> false,
	               'payment_details'=> '',
	               'transaction_data'=> '',
	               'redirect_url'=> '',
			//Html code to display in the payment view.
	               'plugin_output'=> '',     
			//Only used with Odyssey offline plugin.
	               'offline_id'=> 0);
      $session->set('utility', $utility, 'odyssey');
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

    //Get the utility session array.
    $utility = $session->get('utility', array(), 'odyssey'); 
    //Store the needed data for the payment process.
    $utility['payment_mode'] = $paymentMode; 
    $utility['offline_id'] = (int)$offlineId; 
    $session->set('utility', $utility, 'odyssey');

    //Build the name of the event to trigger according to the payment name of
    //the plugin.
    //Note: The first letter of the payment mode is uppercased.
    $event = 'onOdysseyPayment'.ucfirst($paymentMode);
    JPluginHelper::importPlugin('odysseypayment');
    $dispatcher = JDispatcher::getInstance();

    //Trigger the event.
    //Note: Parameters are not passed by reference (using an &) cause we don't
    //allow plugins to modify directly session variables, except for the utility array.
    //Warning: Plugins MUST NOT use the session variables to get or modify data. 
    $results = $dispatcher->trigger($event, array($travel, $addons, $settings, &$utility));

    //Store the utility array modified by the plugin in the session.
    $session->set('utility', $results[0], 'odyssey');

    if(!$results[0]['payment_result']) { //An error has occured.
      //Retrieve and display the error message set by the plugin.
      $message = $results[0]['error'];
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


  public function response()
  {
    //Grab the user session.
    $session = JFactory::getSession();
    $travel = $session->get('travel', array(), 'odyssey'); 
    $addons = $session->get('addons', array(), 'odyssey'); 
    $settings = $session->get('settings', array(), 'odyssey'); 
    $utility = $session->get('utility', array(), 'odyssey'); 

    $payment = $this->input->get->get('payment', '', 'string');
    $event = 'onOdysseyPayment'.ucfirst($payment).'Response';

    JPluginHelper::importPlugin('odysseypayment');
    $dispatcher = JDispatcher::getInstance();
    $results = $dispatcher->trigger($event, array($travel, $addons, $settings, &$utility));

    //Store the utility array modified by the plugin in the session.
    $session->set('utility', $results[0], 'odyssey');

    if(!$results[0]['payment_result']) { //An error has occured.
      //Retrieve and display the error message set by the plugin.
      $message = $results[0]['error'];
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


  public function cancelPayment()
  {
    //Grab the user session.
    $session = JFactory::getSession();
    $utility = $session->get('utility', array(), 'odyssey'); 

    $payment = $this->input->get->get('payment', '', 'string');
    $event = 'onOdysseyPayment'.ucfirst($payment).'Cancel';
    JPluginHelper::importPlugin('odysseypayment');
    $dispatcher = JDispatcher::getInstance();
    $results = $dispatcher->trigger($event, array(&$utility));

    //Store the utility array modified by the plugin in the session.
    $session->set('utility', $results[0], 'odyssey');
//file_put_contents('debog_payment.txt', print_r($results, true));

    if(!$results[0]['payment_result']) { //An error has occured.
      //Retrieve and display the error message set by the plugin.
      $message = $results[0]['payment_details'];
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


  protected function getAmounts()
  {
    //Get the cartAmount session variable.
    $session = JFactory::getSession();
    $cartAmount = $session->get('cart_amount', array(), 'odyssey'); 

    //Store the different amounts in an array.
    $amounts = array();
    $amounts['cart_amount'] = $cartAmount['amount'];
    $amounts['crt_amt_incl_tax'] = $cartAmount['amt_incl_tax'];
    $amounts['final_cart_amount'] = $cartAmount['final_amount'];
    $amounts['fnl_crt_amt_incl_tax'] = $cartAmount['fnl_amt_incl_tax'];

    //Check the cart is shippable before searching any selected shipper.
    if(ShopHelper::isShippable()) {
      $shippers = $session->get('shippers', array(), 'odyssey'); 

      foreach($shippers as $shipper) {
	//Get the selected shipper.
	if((bool)$shipper['selected']) {
	  foreach($shipper['shippings'] as $shipping) {
	    //Store the shipping amounts.
	    if((bool)$shipping['selected']) {
	      $amounts['shipping_cost'] = $shipping['cost'];
	      $amounts['final_shipping_cost'] = $shipping['final_cost'];
	      break 2;
	    }
	  }
        }
      }
    }

    return $amounts;
  }
}

