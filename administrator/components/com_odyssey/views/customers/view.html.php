<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access
 
jimport('joomla.application.component.view');
//Import the users helper to get the getGroups and getRangeOptions functions.  
require_once JPATH_ADMINISTRATOR.'/components/com_users/helpers/users.php';
require_once JPATH_COMPONENT.'/helpers/odyssey.php';
 

class OdysseyViewCustomers extends JViewLegacy
{
  protected $items;
  protected $state;
  protected $pagination;

  //Display the view.
  public function display($tpl = null)
  {
    $this->items = $this->get('Items');
    $this->state = $this->get('State');
    $this->pagination = $this->get('Pagination');
    $this->filterForm = $this->get('FilterForm');
    $this->activeFilters = $this->get('ActiveFilters');

    //Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseError(500, implode('<br />', $errors));
      return false;
    }

    //Display the tool bar.
    $this->addToolBar();

    //$this->setDocument();
    $this->sidebar = JHtmlSidebar::render();

    //Display the template.
    parent::display($tpl);
  }


  //Build the toolbar.
  protected function addToolBar() 
  {
    //Display the view title and the icon.
    JToolBarHelper::title(JText::_('COM_ODYSSEY_CUSTOMERS_TITLE'), 'users');

    //Get the allowed actions list
    $canDo = OdysseyHelper::getActions();
    $user = JFactory::getUser();

    if($canDo->get('core.edit') || $canDo->get('core.edit.own') || 
       (count($user->getAuthorisedCategories('com_odyssey', 'core.edit'))) > 0 || 
       (count($user->getAuthorisedCategories('com_odyssey', 'core.edit.own'))) > 0) 
    {
      JToolBarHelper::editList('customer.edit', 'JTOOLBAR_EDIT');
    }

    if($canDo->get('core.admin')) {
      JToolBarHelper::divider();
      JToolBarHelper::preferences('com_odyssey', 550);
    }
  }


  protected function setDocument() 
  {
    //Include css file.
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_odyssey/odyssey.css');
  }
}


