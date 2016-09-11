<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access
 
jimport( 'joomla.application.component.view');
require_once JPATH_COMPONENT.'/helpers/odyssey.php';
 

class OdysseyViewTravels extends JViewLegacy
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

    $this->setDocument();
    $this->sidebar = JHtmlSidebar::render();

    //Display the template.
    parent::display($tpl);
  }


  //Build the toolbar.
  protected function addToolBar() 
  {
    //Display the view title and the icon.
    JToolBarHelper::title(JText::_('COM_ODYSSEY_TRAVELS_TITLE'), 'airplane');

    //Get the allowed actions list
    $canDo = OdysseyHelper::getActions();
    $user = JFactory::getUser();

    //The user is allowed to create or is able to create in one of the component categories.
    if($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_travel', 'core.create'))) > 0) {
      JToolBarHelper::addNew('travel.add', 'JTOOLBAR_NEW');
    }

    if($canDo->get('core.edit') || $canDo->get('core.edit.own') || 
       (count($user->getAuthorisedCategories('com_travel', 'core.edit'))) > 0 || 
       (count($user->getAuthorisedCategories('com_travel', 'core.edit.own'))) > 0) {
      JToolBarHelper::editList('travel.edit', 'JTOOLBAR_EDIT');
    }

    if($canDo->get('core.edit.state')) {
      JToolBarHelper::divider();
      JToolBarHelper::custom('travels.publish', 'publish.png', 'publish_f2.png','JTOOLBAR_PUBLISH', true);
      JToolBarHelper::custom('travels.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
      JToolBarHelper::divider();
      JToolBarHelper::archiveList('travels.archive','JTOOLBAR_ARCHIVE');
      JToolBarHelper::custom('travels.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
      JToolBarHelper::trash('travels.trash','JTOOLBAR_TRASH');
    }

    //Check for delete permission.
    if($canDo->get('core.delete') || count($user->getAuthorisedCategories('com_travel', 'core.delete'))) {
      JToolBarHelper::divider();
      JToolBarHelper::deleteList('', 'travels.delete', 'JTOOLBAR_DELETE');
    }

    if($canDo->get('core.admin')) {
      JToolBarHelper::divider();
      JToolBarHelper::preferences('com_odyssey', 550);
    }
  }


  protected function setDocument() 
  {
    //Include css file (if needed).
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_odyssey/odyssey.css');
  }
}


