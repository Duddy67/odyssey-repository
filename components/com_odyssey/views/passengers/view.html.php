<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.view');
require_once JPATH_COMPONENT_SITE.'/helpers/route.php';
require_once JPATH_COMPONENT_SITE.'/helpers/travel.php';
require_once JPATH_COMPONENT_ADMINISTRATOR.'/helpers/utility.php';
require_once JPATH_COMPONENT_ADMINISTRATOR.'/helpers/javascript.php';
require_once JPATH_COMPONENT_ADMINISTRATOR.'/helpers/odyssey.php';

/**
 * HTML View class for the Odyssey component.
 */
class OdysseyViewPassengers extends JViewLegacy
{
  protected $nowDate;
  protected $currency;
  protected $customerData;
  protected $form;
  protected $preloadPsgr;

  public function display($tpl = null)
  {
    $user = JFactory::getUser();
    //Create new JForm object
    $this->form = new JForm('PsgrForm');
    //Load the passenger form.
    $this->form->loadFile(OdysseyHelper::getOverridedFile(JPATH_ROOT.'/administrator/components/com_odyssey/models/forms/passenger.xml'));
    //var_dump($this->get('Form'));
    //$this->customerData = $this->get('CustomerData');
    $this->customerData = TravelHelper::getCustomerData();

    //Run the required functions in order to use the preload passengers feature.
    $this->preloadPsgr = JavascriptHelper::getPassengers($user->get('id'), false);
    JavascriptHelper::loadFunctions(array('passengers', 'passengerAttributes'), array($user->get('id')));

    // Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseWarning(500, implode("\n", $errors));
      return false;
    }

    //Get the default currency.
    $this->currency = UtilityHelper::getCurrency();
    $this->nowDate = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);

    //Load the javascript code to hide buttons.
    TravelHelper::javascriptUtilities();
    $this->setDocument();

    //Check for extra language file.
    $language = JFactory::getLanguage();
    OdysseyHelper::addExtraLanguage($language);

    parent::display($tpl);
  }


  protected function setDocument() 
  {
    //Include css files (if needed).
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_odyssey/css/odyssey.css');
  }
}

