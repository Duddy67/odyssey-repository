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
require_once JPATH_COMPONENT_ADMINISTRATOR.'/helpers/step.php';

/**
 * HTML View class for the Odyssey component.
 */
class OdysseyViewTravel extends JViewLegacy
{
  protected $state;
  protected $item;
  protected $nowDate;
  protected $user;
  protected $uri;
  protected $travelData;
  protected $steps;

  public function display($tpl = null)
  {
    // Initialise variables
    $this->state = $this->get('State');
    $this->item = $this->get('Item');
    $user = JFactory::getUser();
    //Note: Call the function in a slightly different way in order to pass an argument.
    $this->travelData = $this->getModel()->getTravelData($this->item->dpt_step_id);

    // Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseWarning(500, implode("\n", $errors));
      return false;
    }

    // Compute the category slug.
    $this->item->catslug = $this->item->category_alias ? ($this->item->catid.':'.$this->item->category_alias) : $this->item->catid;
    //Get the possible extra class name.
    $this->pageclass_sfx = htmlspecialchars($this->item->params->get('pageclass_sfx'));

    //Get the user object and the current url, (needed in the travel edit layout).
    $this->user = JFactory::getUser();
    $this->uri = JUri::getInstance();

    //Increment the hits for this travel.
    $model = $this->getModel();
    $model->hit();

    if($this->item->show_steps) {
      $this->steps = StepHelper::getStepSequence($this->item->dpt_step_id, $this->item->departure_number);
    }
//echo '<pre>';
//var_dump($this->steps);
//echo '</pre>';

    //Get the default currency.
    $currency = UtilityHelper::getCurrency();
    $this->item->currency = $currency;
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

