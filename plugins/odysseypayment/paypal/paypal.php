<?php
/**
 * @package Odyssey Paypal Payment
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access
defined('_JEXEC') or die('Restricted access');
// Import the JPlugin class
jimport('joomla.plugin.plugin');
require_once JPATH_ROOT.'/components/com_odyssey/helpers/order.php';



class plgOdysseypaymentPaypal extends JPlugin
{

  //Grab the event triggered by the payment controller.
  public function onOdysseyPaymentPaypal($travel, $addons, $settings, $utility)
  {
    //Get the first part of the query where all the basic parameters are set.
    $paypalQuery = $this->getPaypalQuery();
    //Get the SetExpressCheckout query.
    $setExpressCheckout = $this->setExpressCheckout($travel, $addons, $settings);
    //Concatenate the 2 parts to get the complete query.
    $paypalQuery = $paypalQuery.$setExpressCheckout;

    //Execute the query and get the result.
    $curl = $this->cURLSession($paypalQuery);

    //Load Paypal plugin language.
    $lang = JFactory::getLanguage();
    $lang->load('plg_odysseypayment_paypal', dirname(__FILE__));

    if(!$curl[0]) { //curl failed
      //Display an error message.
      $utility['payment_details'] = JText::sprintf('PLG_ODYSSEY_PAYMENT_PAYPAL_ERROR_CURL', $curl[1]);
      $utility['payment_result'] = false;
      return $utility;
    }
    else { //curl succeeded.
      //Retrieve all the paypal result into an array.
      $paypalParamsArray = $this->buildPaypalParamsArray($curl[1]); 

      //Paypal query has succeeded
      if($paypalParamsArray['ACK'] === 'Success') {
	//Add the token value sent back by Paypal to the odyssey session before redirect the 
	//user on the Paypal web site. 
	$utility['paypal_token'] = $paypalParamsArray['TOKEN'];
	//Before redirect the user on the Paypal web site we must set the name 
	//of the step we are now taking. In this way, the
	//onOdysseyPaymentPaypalResponse function will be able to know what
	//is the next operation.  
	//Note: Utility data is set in the session by the setPayment controller function.
	$utility['paypal_step'] = 'setExpressCheckout';

	//Get the Paypal server url from the plugin parameters.
	$paypalServer = $this->params->get('server');

	//Remove slash from the end of the string if any.
	if(preg_match('#\/$#', $paypalServer)) {
	  $paypalServer = substr($paypalServer, 0, -1);
	}

	//Redirect the user on the Paypal web site (add the token into url).
	//Note: Redirection is perform by the setPayment controller function.
	$utility['redirect_url'] = $paypalServer.'/webscr&cmd=_express-checkout&token='.$paypalParamsArray['TOKEN'];

	return $utility;
      }
      else { //Paypal query has failed.
	//Display the Paypal error message.
	$utility['payment_details'] = JText::sprintf('PLG_ODYSSEY_PAYMENT_PAYPAL_ERROR_PAYPAL', 
	                     $paypalParamsArray['L_SHORTMESSAGE0'], $paypalParamsArray['L_LONGMESSAGE0']);
	$utility['payment_result'] = false;

	return $utility;
      }		
    }
  }


  public function onOdysseyPaymentPaypalResponse($travel, $addons, $settings, $utility)
  {
    //Carry on with the Paypal payment procedure according to the current step.

    //Load Paypal plugin language.
    $lang = JFactory::getLanguage();
    $lang->load('plg_odysseypayment_paypal', dirname(__FILE__));

    if($utility['paypal_step'] === 'setExpressCheckout') {
      //Empty the redirect_url variable to prevent payment controller to
      //redirect the user.
      $utility['redirect_url'] = '';

      //Paypal server has redirected the user on our site and sent us back the
      //token previously created and the payer id.
      $token = JRequest::getVar('token', '', 'GET', 'str');

      //Check the token previously created against the one just passed by Paypal.
      if($token !== $utility['paypal_token']) {
	//Display the Paypal error message.
	$utility['payment_details'] = JText::_('PLG_ODYSSEY_PAYMENT_PAYPAL_ERROR_TOKEN'); 
	$utility['payment_result'] = false;
	return $utility;
      }

      //Get the first part of the query where all the basic parameters are set.
      $paypalQuery = $this->getPaypalQuery();
      //Get the GetExpressCheckoutDetails query.
      $getExpressCheckoutDetails = $this->getExpressCheckoutDetails();
      //Concatenate the 2 parts to get the complete query.
      $paypalQuery = $paypalQuery.$getExpressCheckoutDetails;

      //Execute the query and get the result.
      $curl = $this->cURLSession($paypalQuery);

      if(!$curl[0]) { //curl failed
	//Display an error message.
	$utility['payment_details'] = JText::sprintf('PLG_ODYSSEY_PAYMENT_PAYPAL_ERROR_CURL', $curl[1]);
	$utility['payment_result'] = false;
	return $utility;
      }
      else { //curl succeeded.
	//Retrieve all the Paypal result into an array.
	$paypalParamsArray = $this->buildPaypalParamsArray($curl[1]); 

	//Paypal query has succeeded
	if($paypalParamsArray['ACK'] === 'Success') {
	  //Store the paypal params array as we gonna use it later (for payerID
	  //variable).
	  $utility['transaction_data'] = $paypalParamsArray;
	}
	else { //Paypal query has failed.
	  //Display the Paypal error message.
	  $utility['payment_details'] = JText::sprintf('PLG_ODYSSEY_PAYMENT_PAYPAL_ERROR_PAYPAL', 
			       $paypalParamsArray['L_SHORTMESSAGE0'], $paypalParamsArray['L_LONGMESSAGE0']);
	  $utility['payment_result'] = false;
	  return $utility;
	}		
      }

      //So far all the Paypal payment steps have been successfull, the only step
      //left is the final transaction details performed by the
      //DoExpressCheckoutPayment method.

      $db = JFactory::getDbo();
      $query = $db->getQuery(true);

      $query->select('pm.name,pm.information')
	    ->from('#__odyssey_payment_mode AS pm')
	    ->where('pm.plugin_element="paypal"');
      $db->setQuery($query);
      $paypalPayment = $db->loadObject();
    
      //Set the name of the step we are now taking.
      $utility['paypal_step'] = 'getExpressCheckoutDetails';

      $amountToPay = $travel['final_amount'];
      if($travel['booking_option'] == 'deposit') {
	$amountToPay = $travel['deposit_amount'];
      }
      elseif($travel['booking_option'] == 'remaining') {
	$amountToPay = $travel['outstanding_balance'];
      }

      //Now we ask the user to proceed with the final transaction by pressing the
      //form button, (Note: payment can still be cancelled).
      $output = '<div class="payment-message">'.JText::sprintf('PLG_ODYSSEY_PAYMENT_PAYPAL_VALIDATION_MESSAGE', UtilityHelper::formatNumber($amountToPay), $settings['currency']).'</div>';
      $output .= '<form action="index.php?option=com_odyssey&view=payment&task=payment.response&payment=paypal" '.
		 'method="post" id="payment_modes">';
      $output .= '<div class="paypal-payment">';
      $output .= '<h1>'.$paypalPayment->name.'</h1>';
      $output .= $paypalPayment->information;
      $output .= '<div id="action-buttons">';
      $output .= '<span class="button">'.
		 '<a href="index.php?option=com_odyssey&view=payment&task=payment.cancelPayment&payment=paypal" onclick="hideButton(\'action-buttons\')">'.JText::_('PLG_ODYSSEY_PAYMENT_PAYPAL_CANCEL').'</a></span>';
      $output .= '<span class="button-separation">&nbsp;</span>';
      $output .= '<input id="submit-button" class="btn btn-success" type="submit" onclick="hideButton(\'action-buttons\')" value="'
	          .JText::_('PLG_ODYSSEY_PAYMENT_PAYPAL_VALIDATE').'" />';
      $output .= '</div>';
      $output .= '</div>';
      $output .= '</form>';

      //Store the output into the utility array in order to be displayed
      //in the payment view.
      $utility['plugin_output'] = $output;

      return $utility;
    }
    elseif($utility['paypal_step'] === 'getExpressCheckoutDetails') {
      //The user has confirmed the payment. We can proceed with the final
      //transaction with the DoExpressCheckoutPayment method which is the 
      //last step of the Paypal payment procedure. 

      //Get the first part of the query where all the basic parameters are set.
      $paypalQuery = $this->getPaypalQuery();
      //Get the DoExpressCheckoutPayment query.
      $doExpressCheckoutPayment = $this->doExpressCheckoutPayment($travel, $addons, $settings);
      //Concatenate the 2 parts to get the complete query.
      $paypalQuery = $paypalQuery.$doExpressCheckoutPayment;

      //Execute the query and get the result.
      $curl = $this->cURLSession($paypalQuery);

      if(!$curl[0]) { //curl failed
	//Display an error message.
	$utility['payment_details'] = JText::sprintf('PLG_ODYSSEY_PAYMENT_PAYPAL_ERROR_CURL', $curl[1]);
	$utility['payment_result'] = false;
	return $utility;
      }
      else { //curl succeeded.
	//Retrieve all the Paypal result into an array.
	$paypalParamsArray = $this->buildPaypalParamsArray($curl[1]); 
	//Serialize the Paypal data to store it into database.
	$utility['transaction_data'] = serialize($paypalParamsArray);

	//Paypal query has succeeded
	if($paypalParamsArray['ACK'] === 'Success') {
	  //Paypal payment is now complete. We can redirect the user on the
	  //finalize page where order and transaction are gonna be stored into
	  //database.

	  //Notify that payment has succeded
	  $utility['redirect_url'] = JRoute::_('index.php?option=com_odyssey&task=end.confirmPayment', false);
	  $utility['payment_details'] = $paypalParamsArray['ACK'];

	  OrderHelper::createTransaction($travel, $utility, $settings); 
	}
	else { //Paypal query has failed.
	  //Before going further we check the Paypal error code. 
	  //11607 is Paypal error code for "Duplicate Request" which means that
	  //we're dealing with the double click effect.
	  //Since Paypal transaction went ok (Long message: A successful transaction has already 
	  //been completed for this token.), we can confirm the purchase. 
          if($paypalParamsArray['L_ERRORCODE0'] == 11607) {
	    //Notify that payment has succeded
	    $utility['redirect_url'] = JRoute::_('index.php?option=com_odyssey&task=end.confirmPayment', false);
	  }

	  //Display the Paypal error message.
	  $utility['payment_details'] = JText::sprintf('PLG_ODYSSEY_PAYMENT_PAYPAL_ERROR_PAYPAL', 
			       $paypalParamsArray['L_SHORTMESSAGE0'], $paypalParamsArray['L_LONGMESSAGE0']);
	  $utility['payment_result'] = false;

	  OrderHelper::createTransaction($travel, $utility, $settings); 
	}		

	return $utility;
      }
    }
    else { //Something odd happened.
      //Display an error message.
      $utility['payment_details'] = JText::_('PLG_ODYSSEY_PAYMENT_PAYPAL_ERROR_NO_STEP');
      $utility['payment_result'] = false;
      return $utility;
    }
  }


  public function onOdysseyPaymentPaypalCancel($utility)
  {
    //Remove the specific variables
    unset($utility['paypal_token']);
    unset($utility['paypal_step']);
    //then empty the generic variables.
    $utility['redirect_url'] = '';
    $utility['plugin_output'] = '';
    $utility['payment_details'] = '';
    $utility['transaction_data'] = '';

    return $utility;
  }


  //Build the beginning of the Paypal query with the basic 
  //parameters such as api, password, signature etc...
  protected function getPaypalQuery()
  {
    //Get the component parameters set into the plugin panel.
    $paypalApi = $this->params->get('api');
    $version = $this->params->get('api_version');
    $user = $this->params->get('user');
    $password = $this->params->get('password');
    $signature = $this->params->get('signature');

    //Remove slash from the end of the string if any.
    if(preg_match('#\/$#', $paypalApi)) {
      $paypalApi = substr($paypalApi, 0, -1);
    }

    $query = $paypalApi.'/nvp?VERSION='.$version.'&USER='.$user.
                               '&PWD='.$password.'&SIGNATURE='.$signature;

    return $query;
  }


  //Create a cURL session and execute the query passed in argument.
  //Return an array where:
  //id 0 = boolean (true: succeeded, false: failed).
  //id 1 = string (result of the query if succeed or error message).
  protected function cURLSession($paypalQuery)
  {
    //Initialize the cURL session.
    $ch = curl_init($paypalQuery);
    //Ignore the verification of the SSL certificat.
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    //Return transfert into string format.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //Execute the url query.
    $paypalResult = curl_exec($ch);

    $result = array();
    //Store result.
    if(!$paypalResult) { //curl failed
      $result[] = false;
      $result[] = curl_error($ch);
    }
    else {
      $result[] = true;
      $result[] = $paypalResult;
    }

    //Close the cURL session.
    curl_close($ch);

    return $result;
  }


  protected function setExpressCheckout($travel, $addons, $settings)
  {
    //Initialize some variables.
    $currencyCode = $settings['currency_code'];
    $countryCode = $settings['country_code'];
    //Load Paypal plugin language.
    $lang = JFactory::getLanguage();
    $lang->load('plg_odysseypayment_paypal', dirname(__FILE__));

    //We can add custom parameter to the query, but we need 
    //GetExpressCheckoutDetails to recover it.
    $query = '&METHOD=SetExpressCheckout'.
	     '&CANCELURL='.urlencode(JUri::base().'index.php?option=com_odyssey&view=payment&task=payment.cancel&payment=paypal').
	     '&RETURNURL='.urlencode(JUri::base().'index.php?option=com_odyssey&view=payment&task=payment.response&payment=paypal');

    //Get the query for the detail order.
    $query .= $this->buildPaypalDetailOrder($travel, $addons, $settings);

    $query .= '&PAYMENTREQUEST_0_CURRENCYCODE='.$currencyCode.
	      '&PAYMENTREQUEST_0_DESC='.urlencode(JText::_('PLG_ODYSSEY_PAYMENT_PAYPAL_SHOP_DESC')).
	      '&NOSHIPPING=1'.
	      '&LOCALECODE='.$countryCode.
	      '&PAYMENTREQUEST_0_PAYMENTACTION=Sale'.
	      '&PAYMENTREQUEST_0_CUSTOM=123456789'; //Add custom parameter.
//file_put_contents('debog_paypal.txt', print_r($query, true));
    return $query;
  }


  //Once Paypal query has succeeded, we might want more details about the 
  //transaction. We can get them with the GetExpressCheckoutDetails method.
  protected function getExpressCheckoutDetails()
  {
    //Get some needed data from the utility session array.
    $session = JFactory::getSession();
    $utility = $session->get('utility', array(), 'odyssey'); 

    //Build the query.
    $query = '&METHOD=GetExpressCheckoutDetails'.
	     '&TOKEN='.$utility['paypal_token'];

    return $query;
  }


  //This is the call to Paypal for payment confirmation. We send a query with required 
  //parameters plus the optional parameters we need. If DoExpressCheckoutPayment method 
  //has succeeded, Paypal return a list of parameters value we can use during 
  //our transaction. 
  protected function doExpressCheckoutPayment($travel, $addons, $settings)
  {
    $currencyCode = $settings['currency_code'];
    //Get some needed data from the utility session array.
    $session = JFactory::getSession();
    $utility = $session->get('utility', array(), 'odyssey'); 

    $query = '&METHOD=DoExpressCheckoutPayment'.
	     '&TOKEN='.$utility['paypal_token']. //Add the token sent back by Paypal.

    //Get the query for the detail order.
    $query .= $this->buildPaypalDetailOrder($travel, $addons, $settings);

    $query .= '&PAYMENTREQUEST_0_CURRENCYCODE='.$currencyCode.
	      '&PayerID='.$utility['transaction_data']['PAYERID']. //Add payment id sent back by Paypal.
	      '&PAYMENTREQUEST_0_PAYMENTACTION=Sale'; //Indicate a final sale.

    return $query;
  }


  //Return the detail order which is include into a Paypal query.
  protected function buildPaypalDetailOrder($travel, $addons, $settings)
  {
    //initialize some variables.
    $rounding = $settings['rounding_rule'];
    $digits = $settings['digits_precision'];
    $travelPruleAmount = 0;
    $detailOrder = '';
    //Load Paypal plugin language.
    $lang = JFactory::getLanguage();
    $lang->load('plg_odysseypayment_paypal', dirname(__FILE__));

    //First at all set the travel price.

    $travelPrice = $travel['travel_price'];
    //If we have travel price rules we display the normal price. The modified price will
    //be computed by Paypal according to sum of the price rules.
    if(isset($travel['normal_price'])) {
      $travelPrice = $travel['normal_price'];
    }

    $detailOrder .= '&L_PAYMENTREQUEST_0_NAME0='.urlencode($travel['name']).
		    '&L_PAYMENTREQUEST_0_QTY0=1'. 
		    '&L_PAYMENTREQUEST_0_AMT0='.UtilityHelper::formatNumber($travelPrice);
		    //'&L_PAYMENTREQUEST_0_DESC0='.urlencode(JText::sprintf('PLG_ODYSSEY_PAYMENT_PAYPAL_INCL_TAX', $travel['tax_rate']));

    //Now move to the addons.
    $id = 0;
    foreach($addons as $addon) {
      $id++;
      $detailOrder .= '&L_PAYMENTREQUEST_0_NAME'.$id.'='.urlencode($addon['name']).
	              '&L_PAYMENTREQUEST_0_QTY'.$id.'=1'. 
		      '&L_PAYMENTREQUEST_0_AMT'.$id.'='.UtilityHelper::formatNumber($addon['price']);

      foreach($addon['options'] as $option) {
	$id = $id + 1;
	$detailOrder .= '&L_PAYMENTREQUEST_0_NAME'.$id.'='.urlencode($option['name']).
			'&L_PAYMENTREQUEST_0_QTY'.$id.'=1'. 
			'&L_PAYMENTREQUEST_0_AMT'.$id.'='.UtilityHelper::formatNumber($option['price']);
      }
    }

    //Check if some travel price rules have been applied on the travel price.
    if(isset($travel['normal_price']) && $travel['travel_price'] < $travel['normal_price']) {
      $travelPruleAmount = $travel['normal_price'] - $travel['travel_price'];
      //Convert positive value into negative.
      //$travelPruleAmount = $travelPruleAmount - ($travelPruleAmount * 2);
      $travelPruleAmount = '-'.(string)$travelPruleAmount;
    }
    elseif(isset($travel['normal_price']) && $travel['travel_price'] > $travel['normal_price']) { //Check for raise.
      $travelPruleAmount = $travel['travel_price'] - $travel['normal_price'];
    }

    //Add the sum of the price rules applied to the travel as an item.
    //Paypal will substract or add this value.
    if($travelPruleAmount) {
      $id = $id + 1;
      $detailOrder .= '&L_PAYMENTREQUEST_0_NAME'.$id.'='.urlencode(JText::_('PLG_ODYSSEY_PAYMENT_PAYPAL_PRICERULES')).
	              '&L_PAYMENTREQUEST_0_QTY'.$id.'=1'. 
	              '&L_PAYMENTREQUEST_0_AMT'.$id.'='.UtilityHelper::formatNumber($travelPruleAmount);
	              //'&L_PAYMENTREQUEST_0_DESC'.$id.'='.urlencode(JText::_('PLG_ODYSSEY_PAYMENT_PAYPAL_PRICERULES_DESC'));
    }

    //Add the transit city extra cost if any.
    if($travel['transit_price'] > 0) {
      $id = $id + 1;
      $detailOrder .= '&L_PAYMENTREQUEST_0_NAME'.$id.'='.
	               urlencode(JText::sprintf('PLG_ODYSSEY_PAYMENT_PAYPAL_EXTRA_COST_TRANSIT_CITY', $travel['dpt_city_name'])).
	              '&L_PAYMENTREQUEST_0_QTY'.$id.'=1'. 
	              '&L_PAYMENTREQUEST_0_AMT'.$id.'='.UtilityHelper::formatNumber($travel['transit_price']);
    }

    //The whole price of the travel.
    $finalAmount = $travel['final_amount'];

    $id = $id + 1;
    //The client has chosen to pay a deposit.
    if($travel['booking_option'] == 'deposit') {
      $finalAmount = $travel['deposit_amount'];
      $sumToSubtract = $travel['final_amount'] - $travel['deposit_amount'];
      //Convert positive value into negative.
      //$sumToSubtract = $sumToSubtract - ($sumToSubtract * 2);
      $sumToSubtract = '-'.(string)$sumToSubtract;

      $detailOrder .= '&L_PAYMENTREQUEST_0_NAME'.$id.'='.urlencode(JText::_('PLG_ODYSSEY_PAYMENT_PAYPAL_SUBTRACTED_AMOUNT')).
	              '&L_PAYMENTREQUEST_0_QTY'.$id.'=1'. 
	              '&L_PAYMENTREQUEST_0_AMT'.$id.'='.UtilityHelper::formatNumber($sumToSubtract);
    }
    //The client pay the remaining amount of the travel.
    elseif($travel['booking_option'] == 'remaining') {
      $finalAmount = $travel['outstanding_balance'];
      $sumToSubtract = $travel['final_amount'] - $travel['outstanding_balance'];
      //Convert positive value into negative.
      //$sumToSubtract = $sumToSubtract - ($sumToSubtract * 2);
      $sumToSubtract = '-'.(string)$sumToSubtract;

      $detailOrder .= '&L_PAYMENTREQUEST_0_NAME'.$id.'='.urlencode(JText::_('PLG_ODYSSEY_PAYMENT_PAYPAL_DEPOSIT_ALREADY_PAID')).
	              '&L_PAYMENTREQUEST_0_QTY'.$id.'=1'. 
	              '&L_PAYMENTREQUEST_0_AMT'.$id.'='.UtilityHelper::formatNumber($sumToSubtract);
    }

    //Display the item amount.
    //Note: Item amount is equal to final amount as there is no extra amount such as
    //shipping cost.
    $detailOrder .= '&PAYMENTREQUEST_0_ITEMAMT='.UtilityHelper::formatNumber($finalAmount);

    //Display the final amount.
    $detailOrder .= '&PAYMENTREQUEST_0_AMT='.UtilityHelper::formatNumber($finalAmount);

//file_put_contents('debog_paypal.txt', print_r($detailOrder, true));
    return $detailOrder;
  }


  //Retrieve the Paypal result parameters then turn it into an array for more convenience.
  protected function buildPaypalParamsArray($paypalResult)
  {
    //Create an array of parameters.
    $parametersList = explode("&",$paypalResult);

    //Separate name and value of each parameter.
    foreach($parametersList as $paypalParam) {
      list($name, $value) = explode("=", $paypalParam);
      $paypalParamArray[$name]=urldecode($value); //Create final array.
    }

    return $paypalParamArray; //Return the array.
  }
}

