<?php
/**
 * @package Odyssey Offline Payment
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access
defined('_JEXEC') or die('Restricted access');
// Import the JPlugin class
jimport('joomla.plugin.plugin');
require_once JPATH_ROOT.'/components/com_odyssey/helpers/order.php';



class plgOdysseypaymentOffline extends JPlugin
{
  //Grab the event triggered by the payment controller.
  public function onOdysseyPaymentOffline ($travel, $addons, $settings, $utility)
  {
    //Get the id of the offline payment chosen.
    $offlineId = $utility['offline_id'];

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    $query->select('pm.name,pm.information')
	  ->from('#__odyssey_payment_mode AS pm')
	  ->where('pm.id='.$offlineId);
    $db->setQuery($query);
    $offlinePayment = $db->loadObject();
    
    //Check for errors.
    if($db->getErrorNum() || is_null($offlinePayment)) {
      if($db->getErrorNum()) {
	$utility['payment_details'] = $db->getErrorMsg();
      }
      else {
	$utility['payment_details'] = JText::_('COM_ODYSSEY_ERROR');
      }

      $utility['payment_result'] = false;

      return $utility;
    }

    //Set the name of the offline payment as details.
    $utility['payment_details'] = $offlinePayment->name;

    //Create the form corresponding to the selected offline payment mode.
    $output = '<form action="index.php?option=com_odyssey&task=payment.response&payment=offline" '.
	       'method="post" id="payment_modes" >';
    $output .= '<div class="offline-payment">';
    $output .= '<h1>'.$offlinePayment->name.'</h1>';
    $output .= $offlinePayment->information;
    $output .= '<div id="action-buttons">';
    $output .= '<span class="btn">'.
               '<a href="index.php?option=com_odyssey&view=payment&task=payment.cancelPayment&payment=offline" onclick="hideButton(\'action-buttons\')">'.JText::_('COM_ODYSSEY_CANCEL').'</a></span>';
    $output .= '<span class="button-separation">&nbsp;</span>';
    $output .= '<input id="submit-button" class="btn btn-success" onclick="hideButton(\'action-buttons\')" type="submit" value="'.JText::_('COM_ODYSSEY_VALIDATE').'" />';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</form>';

    //Store the output into the utility array in order to be displayed
    //in the payment view.
    $utility['plugin_output'] = $output;

    return $utility;
  }


  public function onOdysseyPaymentOfflineResponse($travel, $addons, $settings, $utility)
  {
    //Note: Payment results can only be ok with offline payment method since there's
    //      no web procedure to pass through.

    OrderHelper::createTransaction($travel, $utility, $settings); 

    //Redirect the customer to the ending step.
    $utility['redirect_url'] = JRoute::_('index.php?option=com_odyssey&task=end.confirmPayment', false);

    return $utility;
  }


  public function onOdysseyPaymentOfflineCancel($utility)
  {
    //Grab the user session.
    $session = JFactory::getSession();
    $utility = $session->get('utility', array(), 'odyssey'); 
    //Empty the output variable which make the view to display the payment modes. 
    $utility['plugin_output'] = '';
    $session->set('utility', $utility, 'odyssey');

    return $utility;
  }


}
