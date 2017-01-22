<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; // No direct access
 
jimport( 'joomla.application.component.view');
require_once JPATH_ADMINISTRATOR.'/components/com_odyssey/helpers/utility.php';
 

class OdysseyViewOrders extends JViewLegacy
{
  protected $items;
  protected $state;
  protected $pagination;

  public function display($tpl = null)
  {
    $this->items = $this->get('Items');
    $this->state = $this->get('State');
    $this->pagination = $this->get('Pagination');
    $this->filterForm = $this->get('FilterForm');
    $this->activeFilters = $this->get('ActiveFilters');

    // Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseError(500, implode('<br />', $errors));
      return false;
    }

    //Ensure the customer has no remaining session.
    TravelHelper::clearSession();
    //$this->setDocument();

    //Display the template.
    parent::display($tpl);
  }


  protected function setDocument() 
  {
    $doc = JFactory::getDocument();
  }
}


