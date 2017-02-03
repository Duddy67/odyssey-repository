<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');
require_once JPATH_COMPONENT_SITE.'/helpers/travel.php';


class OdysseyViewPayment extends JViewLegacy
{

  function display($tpl = null)
  {
    //Load the javascript code to hide buttons.
    TravelHelper::javascriptUtilities();
    $this->setDocument();

    parent::display($tpl);
  }


  protected function setDocument() 
  {
    //Include css and Javascript files.
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_odyssey/css/odyssey.css');
  }
}

