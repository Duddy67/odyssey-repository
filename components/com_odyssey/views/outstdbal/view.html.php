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

/**
 * HTML View class for the Odyssey component.
 */
class OdysseyViewOutstdbal extends JViewLegacy
{
  protected $nowDate;
  protected $currency;
  protected $form;
  protected $params;

  public function display($tpl = null)
  {
    // Initialise variables
    $user = JFactory::getUser();

    $app = JFactory::getApplication();
    $this->params = $app->getParams();

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

