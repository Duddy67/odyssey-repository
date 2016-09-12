<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
require_once JPATH_COMPONENT.'/helpers/travel.php';
require_once JPATH_ROOT.'/administrator/components/com_odyssey/helpers/utility.php';


/**
 * @package     Joomla.Site
 * @subpackage  com_odyssey
 */
class OdysseyControllerEnd extends JControllerForm
{
  public function confirmPayment()
  {
    $session = JFactory::getSession();
    //Note: Better set default value to 1 instead of 0 as the session variables will be 
    //destroyed at the end of the function. 
    $endBooking = $session->get('end_booking', 1, 'odyssey');

    if(!$endBooking) {
      //Set immediately end_booking flag to 1 to prevent the multiple clicks syndrome.
      $session->set('end_booking', 1, 'odyssey'); 

      $settings = $session->get('settings', array(), 'odyssey'); 
      $utility = $session->get('utility', array(), 'odyssey'); 
      $travel = $session->get('travel', array(), 'odyssey'); 
      $addons = $session->get('addons', array(), 'odyssey'); 

      $db = JFactory::getDbo();
      $query = $db->getQuery(true);

      //Create the order number.
      $Ymd = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->format('Y-m-d');
      $orderNb = $travel['order_id'].'-'.$Ymd;

      $fields = array('order_nb="'.$orderNb.'"', 'admin_locked=0','published=1');
      $emailType = 'payment_error';

      //Set order statusses and the outstanding balance according to the payment result.
      if($utility['plugin_result']) {
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
      }

      $query->update('#__odyssey_order')
	    ->set($fields)
	    ->where('id='.(int)$travel['order_id']);
      $db->setQuery($query);
      $db->execute();

      $this->createTransaction($travel, $utility, $settings); 

      $this->setAllotment($travel);

      $userId = JFactory::getUser()->get('id');
      TravelHelper::sendEmail($emailType, $userId); 

      if($travel['booking_option'] == 'deposit' && $settings['run_at_command']) {
	$this->schedulingTasks($travel, $settings, 'deposit');
      }

      TravelHelper::clearSession();
      //Redirect the customer in his customer area.
      $this->setRedirect(JRoute::_('index.php?option=com_odyssey&view=order&layout=edit&o_id='.(int)$travel['order_id'], false));

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

//file_put_contents('debog_limitdate.txt', print_r($limitDate, true)); 
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Just make the order available in the backend.
    $fields = array('order_nb='.$db->quote($travel['order_id']), 'admin_locked=0', 'published=1', 'limit_date='.$db->quote($limitDate));

    $query->update('#__odyssey_order')
	  ->set($fields)
	  ->where('id='.(int)$travel['order_id']);
    $db->setQuery($query);
    $db->execute();

    $this->setAllotment($travel);

    $userId = JFactory::getUser()->get('id');
    TravelHelper::sendEmail('take_option', $userId); 

    if($settings['run_at_command']) {
      $this->schedulingTasks($travel, $settings, 'take_option');
    }


    TravelHelper::clearSession();
    //Redirect the customer in his customer area.
    $this->setRedirect(JRoute::_('index.php?option=com_odyssey&view=order&layout=edit&o_id='.(int)$travel['order_id'], false));

    return true;
  }


  protected function createTransaction($travel, $utility, $settings)
  {
    //Set the amount value which has been paid.
    $amount = $travel['final_amount'];
    if(isset($travel['deposit_amount'])) {
      $amount = $travel['deposit_amount'];
    }

    if($travel['booking_option'] == 'remaining') {
      $amount = $travel['outstanding_balance'];
    }

    //Set the result of the transaction.
    $result = 'success';
    $detail = $utility['payment_details'];
    if(!$utility['plugin_result']) {
      $result = 'error';
      $detail = $utility['error'];
    }

    //Create the transaction.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    $nowDate = $db->quote(JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true));
    $columns = array('order_id','payment_mode','amount_type','amount','result','detail','created');
    $values = (int)$travel['order_id'].','.$db->quote($utility['payment_mode']).','.$db->quote($travel['booking_option']).','.  
              (float)$amount.','.$db->quote($result).','.$db->quote($detail).','.$nowDate;

    $query->insert('#__odyssey_order_transaction')
	  ->columns($columns)
	  ->values($values);
    try {
      $db->setQuery($query);
      $db->execute();
    }
    catch(RuntimeException $e) {
      JFactory::getApplication()->enqueueMessage(JText::_($e->getMessage()), 'error');
      return false;
    }

    return true;
  }


  protected function setAllotment($travel)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Collect needed data regarding allotment.
    $query->select('checked_out, allotment, altm_subtract')
          ->from('#__odyssey_step')
          ->join('INNER', '#__odyssey_departure_step_map ON step_id=id')
          ->where('id='.(int)$travel['dpt_step_id'])
	  ->where('dpt_id='.(int)$travel['dpt_id'])
          ->group('checked_out');
    $db->setQuery($query);
    $result = $db->loadObject();

    //Ensure first that the passenger number of the new order have to be subtract from
    //the allotment. 
    if((int)$result->altm_subtract) {
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
    }

    return;
  }


  protected function schedulingTasks($travel, $settings, $subject)
  {
    $task1 = 'option_reminder';
    $task2 = 'cancelling_option';

    if($subject == 'take_option') {
      $reminderDate = UtilityHelper::getLimitDate($settings['option_reminder'], '', true, 'at');
      $limitDate = UtilityHelper::getLimitDate($settings['option_validity_period'], '', true, 'at');
    }
    elseif($subject == 'deposit') {
      //Set the proper departure date according to the date type.
      $departureDate = $travel['date_time'];
      if($travel['date_type'] == 'period') {
	$departureDate = $travel['date_picker'];
      }

      $reminderDate = UtilityHelper::getLimitDate($settings['deposit_reminder'], $departureDate, false, 'at');
      $limitDate = UtilityHelper::getLimitDate($settings['finalize_time_limit'], $departureDate, false, 'at');
      $task1 = 'deposit_reminder';
      $task2 = 'warning_payment';
    }

      $orderId = $travel['order_id'];
      $uri = JUri::getInstance();
      //heredoc syntax "<<" is used to set the at command.
      //Note: The final 'EOF' (The LimitString) should not have any whitespace in
      //front of the word or it will not be recognized.
      //Lines between LimitStrings must not be indented (tabulations).
      //IMPORTANT: The files using shell_exec MUST be in UNIX format and not in DOS format.
      shell_exec('/usr/bin/at '.$reminderDate.' <<EOF
/usr/bin/wget -O - -q -t 1 "'.$uri->root().'components/com_odyssey/helpers/tasks.php?order_id='.$orderId.'&task='.$task1.'" >/dev/null 2>&1
EOF'
);

      shell_exec('/usr/bin/at '.$limitDate.' <<EOF
/usr/bin/wget -O - -q -t 1 "'.$uri->root().'components/com_odyssey/helpers/tasks.php?order_id='.$orderId.'&task='.$task2.'" >/dev/null 2>&1
EOF'
);

    return;
  }
}

