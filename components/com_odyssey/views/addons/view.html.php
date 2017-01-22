<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.view');
require_once JPATH_COMPONENT_SITE.'/helpers/route.php';
require_once JPATH_COMPONENT_SITE.'/helpers/travel.php';
require_once JPATH_COMPONENT_ADMINISTRATOR.'/helpers/utility.php';

/**
 * HTML View class for the Odyssey component.
 */
class OdysseyViewAddons extends JViewLegacy
{
  protected $nowDate;
  protected $user;
  protected $currency;
  protected $addonData;

  public function display($tpl = null)
  {
    // Initialise variables
    $this->user = JFactory::getUser();
    //In case the user logged out.
    /*if($this->user->get('guest') == 1) {
      // Redirect to login page.
      JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_users&view=login', false));
      return;
    }*/

    //Call the function from the second model (set in the controller).
    $this->addonData = $this->get('AddonData', 'Travel');

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

    parent::display($tpl);
  }


  protected function setDocument() 
  {
    //Include css files (if needed).
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_odyssey/css/odyssey.css');
  }
}

