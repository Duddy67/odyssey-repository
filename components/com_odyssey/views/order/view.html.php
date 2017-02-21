<?php
/**
 * @package Odyssey
 * @copyright Copyright (c)2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; // No direct access
 
jimport( 'joomla.application.component.view');
//require_once JPATH_ADMINISTRATOR.'/components/com_odyssey/models/order.php';
require_once JPATH_ADMINISTRATOR.'/components/com_odyssey/helpers/javascript.php';
require_once JPATH_ADMINISTRATOR.'/components/com_odyssey/helpers/utility.php';
require_once JPATH_ADMINISTRATOR.'/components/com_odyssey/helpers/odyssey.php';
require_once JPATH_COMPONENT_SITE.'/helpers/travel.php';
 

class OdysseyViewOrder extends JViewLegacy
{
  /**
   * Display the view
   *
   * @return	mixed	False on error, null otherwise.
   */

  protected $user;
  protected $item;
  protected $form;
  protected $state;
  protected $transactions;
  protected $passengers;
  protected $psgrForm;
  protected $preloadPsgr;
  protected $customerData;
  protected $remainingPayment;
  protected $outOfDate;

  function display($tpl = null)
  {
    $this->user = JFactory::getUser();
    // Initialise variables
    $this->item = $this->get('Item');
    $this->form = $this->get('Form');
    $this->state = $this->get('State');
    $this->transactions = $this->getModel()->getTransactions($this->item->id);
    $this->passengers = $this->getModel()->getPassengers($this->item->id, $this->item->nb_psgr);
    // create new JForm object
    $this->psgrForm = new JForm('PsgrForm');
    // Load any form .xml file you want (like registration.xml)
    $this->psgrForm->loadFile(OdysseyHelper::getOverridedFile(JPATH_ROOT.'/administrator/components/com_odyssey/models/forms/passenger.xml'));
    //var_dump($this->item);
    //Run the required functions in order to use the preload passengers feature.
    $this->preloadPsgr = JavascriptHelper::getPassengers($this->item->customer_id, false);
    JavascriptHelper::loadFunctions(array('passengers', 'passengerAttributes'), array($this->item->customer_id));

    // Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseWarning(500, implode("\n", $errors));
      return false;
    }

    $this->remainingPayment = false;
    $this->outOfDate = false;
    $nowDate = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);
    if($this->item->departure_date < $nowDate) {
      $this->outOfDate = true;
    }

    $this->customerData = TravelHelper::getCustomerData();
    //Ensure the customer has no remaining session.
    TravelHelper::clearSession();
    //Check if there is any amount left to pay.
    if(!$this->outOfDate && ($this->item->outstanding_balance > 0 &&
			     ($this->item->payment_status == 'pending' ||
			      $this->item->payment_status == 'error' ||
			      $this->item->payment_status == 'deposit'))) {
      $this->remainingPayment = true;
      //Must be called only once.
      TravelHelper::initializeSession();

      //Load the javascript code to hide submit buttons.
      TravelHelper::javascriptUtilities();

      //Grab the user session.
      $session = JFactory::getSession();
      //Get then store travel and addon data in the session.
      $travel = $this->get('Travel');
      $travel['outstanding_balance'] = $this->item->outstanding_balance;
      $travel['final_amount'] = $this->item->final_amount;
      $addons = $this->get('Addons');
      $session->set('travel', $travel, 'odyssey'); 
      $session->set('addons', $addons, 'odyssey'); 
    }

    $this->setDocument();

    //Check for extra language file.
    $language = JFactory::getLanguage();
    OdysseyHelper::addExtraLanguage($language);

    parent::display($tpl);
  }


  protected function setDocument() 
  {
    //Include the css file.
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_odyssey/css/odyssey.css');
  }
}


