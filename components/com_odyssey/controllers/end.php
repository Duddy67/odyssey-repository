<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
require_once JPATH_COMPONENT.'/helpers/travel.php';
require_once JPATH_COMPONENT.'/helpers/order.php';
require_once JPATH_ROOT.'/administrator/components/com_odyssey/helpers/utility.php';


/**
 * @package     Joomla.Site
 * @subpackage  com_odyssey
 */
class OdysseyControllerEnd extends JControllerForm
{
  public function recapOrder()
  {
    $session = JFactory::getSession();
    //Note: Better set default value to 1 instead of 0 as the session variables will be 
    //destroyed at the end of the function. 
    $endBooking = $session->get('end_booking', 1, 'odyssey');

    if(!$endBooking) {
      //Set immediately end_booking flag to 1 to prevent the multiple clicks syndrome.
      $session->set('end_booking', 1, 'odyssey'); 

      $settings = $session->get('settings', array(), 'odyssey'); 
      $travel = $session->get('travel', array(), 'odyssey'); 
      $addons = $session->get('addons', array(), 'odyssey'); 
      $userId = JFactory::getUser()->get('id');

      $utility = OrderHelper::getTemporaryData($travel['order_id'], true);

      OrderHelper::deleteTemporaryData($travel['order_id']);

      $db = JFactory::getDbo();
      $query = $db->getQuery(true);

      $fields = array('admin_locked=0','published=1');

      //Set order statusses and the outstanding balance according to the payment result.
      if($utility['payment_result']) {
	if($travel['booking_option'] == 'deposit') {
	  $fields[] = 'payment_status="deposit"';
	  //Compute the outstanding balance.
	  $outstandingBalance = $travel['final_amount'] - $travel['deposit_amount'];
	  $fields[] = 'outstanding_balance='.(float)$outstandingBalance;

	  //Compute the limit date to finalize payment.

	  //Set the proper departure date according to the date type.
	  $departureDate = $travel['date_time'];
	  if($travel['date_type'] == 'period') {
	    $departureDate = $travel['date_picker'];
	  }

	  $limitDate = UtilityHelper::getLimitDate($settings['finalize_time_limit'], $departureDate, false);
	  $fields[] = 'limit_date='.$db->quote($limitDate);
	  $emailType = 'deposit';
	}
	else { //whole_price or remaining
	  $fields[] = 'payment_status="completed"';
	  $fields[] = 'outstanding_balance=0';
	  //Reset limit date in case of remaining payment.
	  $fields[] = 'limit_date='.$db->quote('0000-00-00 00:00:00');
	  $emailType = $travel['booking_option'];
	}

	//Check for possible API plugin.
	if($settings['api_connector'] && $settings['api_plugin']) {
	  //Trigger the plugin event.
	  $event = 'onOdysseyApiConnectorFunction';
	  JPluginHelper::importPlugin('odysseyapiconnector');
	  $dispatcher = JDispatcher::getInstance();
	  $results = $dispatcher->trigger($event, array('setDepartureAvailability', array($travel, $addons, $settings, $userId)));
	}
	else {
	  $this->setAllotment($travel);
	}
      }
      else { //The payment has failed.
	$fields[] = 'payment_status="error"';
	$emailType = $travel['booking_option'].'_payment_error';
      }

      $query->update('#__odyssey_order')
	    ->set($fields)
	    ->where('id='.(int)$travel['order_id']);
      $db->setQuery($query);
      $db->execute();

      TravelHelper::sendEmail($emailType, $userId); 
      //Send email to the administrator as well.
      TravelHelper::sendEmail($emailType, 0, 0, true); 

      if($utility['payment_result'] && $travel['booking_option'] == 'deposit' && $settings['run_at_command']) {
	$this->schedulingTasks($travel, $settings, 'deposit');
      }

      if(!$utility['payment_result']) {
	JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_PAYMENT_ERROR', 'warning'));
      }

      TravelHelper::clearSession();
      //Redirect the customer in his order customer area.
      $this->setRedirect(JRoute::_('index.php?option=com_odyssey&task=order.edit&o_id='.(int)$travel['order_id'], false));

      return true;
    }
  }


  public function confirmOption()
  {
    $session = JFactory::getSession();
    $travel = $session->get('travel', array(), 'odyssey'); 
    $settings = $session->get('settings', array(), 'odyssey'); 
    //Get the limit date against the validity period of the option.
    $limitDate = UtilityHelper::getLimitDate($settings['option_validity_period']);
    $userId = JFactory::getUser()->get('id');

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Just make the order available in the backend.
    $fields = array('admin_locked=0', 'published=1', 'limit_date='.$db->quote($limitDate));

    $query->update('#__odyssey_order')
	  ->set($fields)
	  ->where('id='.(int)$travel['order_id']);
    $db->setQuery($query);
    $db->execute();

    //Check for possible API plugin.
    if($settings['api_connector'] && $settings['api_plugin']) {
      $addons = $session->get('addons', array(), 'odyssey'); 
      //Trigger the plugin event.
      $event = 'onOdysseyApiConnectorFunction';
      JPluginHelper::importPlugin('odysseyapiconnector');
      $dispatcher = JDispatcher::getInstance();
      $results = $dispatcher->trigger($event, array('setDepartureAvailability', array($travel, $addons, $settings, $userId)));
    }
    else {
      $this->setAllotment($travel);
    }

    TravelHelper::sendEmail('take_option', $userId); 
    //Send email to the administrator as well.
    TravelHelper::sendEmail('take_option', 0, 0, true); 

    if($settings['run_at_command']) {
      $this->schedulingTasks($travel, $settings, 'take_option');
    }


    TravelHelper::clearSession();
    //Redirect the customer in his customer area.
    $this->setRedirect(JRoute::_('index.php?option=com_odyssey&view=order&layout=edit&o_id='.(int)$travel['order_id'], false));

    return true;
  }


  protected function setAllotment($travel)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Collect needed data regarding allotment.
    $query->select('s.checked_out, sm.allotment, sm.altm_subtract, o.already_alloted')
          ->from('#__odyssey_step AS s')
          ->join('INNER', '#__odyssey_departure_step_map AS sm ON sm.step_id=s.id')
          ->join('INNER', '#__odyssey_order AS o ON o.id='.(int)$travel['order_id'])
          ->where('s.id='.(int)$travel['dpt_step_id'])
	  ->where('sm.dpt_id='.(int)$travel['dpt_id'])
          ->group('s.checked_out');
    $db->setQuery($query);
    $result = $db->loadObject();

    //Ensure first that the passenger number of the new order have to be subtract from
    //the allotment and the allotment hasn't already performed. 
    if((int)$result->altm_subtract && !(int)$result->already_alloted) {
      //Compute the new allotment value.
      $newAllotment = $result->allotment - $travel['nb_psgr'];
      //If the result is lower than zero set it to zero or MySQL will cause an error.
      if($newAllotment < 0) {
	$newAllotment = 0;
      }

      $fields = array('allotment='.$newAllotment);
      //Someone (in backend) is editing the step.  
      if((int)$result->checked_out) {
	//Lock the new allotment value to prevent this value to be modified by an admin
	//when saving in backend.
	$fields[] = 'altm_locked=1';
      }

      //Update the allotment value.
      $query->clear();
      $query->update('#__odyssey_departure_step_map')
	    ->set($fields)
	    ->where('step_id='.(int)$travel['dpt_step_id'])
	    ->where('dpt_id='.(int)$travel['dpt_id']);
      $db->setQuery($query);
      $db->execute();

      //Set the flag to prevent to set the allotment again.
      $query->clear();
      $query->update('#__odyssey_order')
	    ->set('already_alloted=1')
	    ->where('id='.(int)$travel['order_id']);
      $db->setQuery($query);
      $db->execute();
    }

    return;
  }


  protected function schedulingTasks($travel, $settings, $subject)
  {
    $task1 = 'option_reminder';
    $task2 = 'cancelling_option';

    if($subject == 'take_option') {
      $reminderDate = UtilityHelper::getLimitDate($settings['option_reminder'], '', true, 'H:i Y-m-d');
      $limitDate = UtilityHelper::getLimitDate($settings['option_validity_period'], '', true, 'H:i Y-m-d');
    }
    elseif($subject == 'deposit') {
      //Set the proper departure date according to the date type.
      $departureDate = $travel['date_time'];
      if($travel['date_type'] == 'period') {
	$departureDate = $travel['date_picker'];
      }

      $reminderDate = UtilityHelper::getLimitDate($settings['deposit_reminder'], $departureDate, false, 'H:i Y-m-d');
      $limitDate = UtilityHelper::getLimitDate($settings['finalize_time_limit'], $departureDate, false, 'H:i Y-m-d');
      $task1 = 'deposit_reminder';
      $task2 = 'warning_payment';
    }

      //For test purposes.
      //$reminderDate = '09:52 2018-02-24';
      //$limitDate = '09:53 2018-02-24';

      $orderId = $travel['order_id'];
      $uri = JUri::getInstance();
      //heredoc syntax "<<" is used to set the at command.
      //Note: The final 'EOF' (The LimitString) should not have any whitespace in
      //front of the word or it will not be recognized.
      //Lines between LimitStrings must not be indented (tabulations).
      //IMPORTANT: The files using shell_exec MUST be in UNIX format and not in DOS format.
      shell_exec('/usr/bin/at '.$reminderDate.' <<EOF
/usr/bin/php '.JPATH_ROOT.'/components/com_odyssey/helpers/tasks.php '.$orderId.' '.$task1.'
EOF'
);

      shell_exec('/usr/bin/at '.$limitDate.' <<EOF
/usr/bin/php '.JPATH_ROOT.'/components/com_odyssey/helpers/tasks.php '.$orderId.' '.$task2.'
EOF'
);

    return;
  }
}


