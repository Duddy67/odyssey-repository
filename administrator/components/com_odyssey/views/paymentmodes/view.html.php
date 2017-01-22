<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access
 
jimport( 'joomla.application.component.view');
require_once JPATH_COMPONENT.'/helpers/odyssey.php';
 

class OdysseyViewPaymentmodes extends JViewLegacy
{
  protected $items;
  protected $state;
  protected $pagination;
  protected $missingPlugins;

  //Display the view.
  public function display($tpl = null)
  {
    $this->items = $this->get('Items');
    $this->state = $this->get('State');
    $this->pagination = $this->get('Pagination');
    $this->filterForm = $this->get('FilterForm');
    $this->activeFilters = $this->get('ActiveFilters');
    $this->missingPlugins = $this->get('MissingPlugins');

    //Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseError(500, implode('<br />', $errors));
      return false;
    }

    //If one or more plugins are missing we display a warning message.
    if(count($this->missingPlugins)) {
      //Get the name of the missing plugin(s).
      foreach($this->missingPlugins as $missingPlugin) {
	$pluginNames .= $missingPlugin.', ';
      }

      //Remove the comma from the end of the string.
      $pluginNames = substr($pluginNames, 0,-2);

      //Display warning message.
      JError::raiseNotice(500, JText::sprintf('COM_ODYSSEY_MISSING_PLUGINS_WARNING', $pluginNames));
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
    JToolBarHelper::title(JText::_('COM_ODYSSEY_PAYMENT_MODES_TITLE'), 'credit-card');


    //Get the allowed actions list
    $canDo = OdysseyHelper::getActions();

    //Note: We check the user permissions only against the component since 
    //the paymentmode items have no categories.
    if($canDo->get('core.create')) {
      JToolBarHelper::addNew('paymentmode.add', 'JTOOLBAR_NEW');
    }

    //Notes: The Edit icon might not be displayed since it's not (yet ?) possible 
    //to edit several items at a time.
    if($canDo->get('core.edit') || $canDo->get('core.edit.own')) {
      JToolBarHelper::editList('paymentmode.edit', 'JTOOLBAR_EDIT');
    }

    if($canDo->get('core.edit.state')) {
      JToolBarHelper::divider();
      JToolBarHelper::custom('paymentmodes.publish', 'publish.png', 'publish_f2.png','JTOOLBAR_PUBLISH', true);
      JToolBarHelper::custom('paymentmodes.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
      JToolBarHelper::divider();
      JToolBarHelper::archiveList('paymentmodes.archive','JTOOLBAR_ARCHIVE');

      if($canDo->get('core.edit.state')) { 
	JToolBarHelper::custom('paymentmodes.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
      }

      JToolBarHelper::trash('paymentmodes.trash','JTOOLBAR_TRASH');
    }

    if($canDo->get('core.delete')) {
      JToolBarHelper::divider();
      JToolBarHelper::deleteList('', 'paymentmodes.delete', 'JTOOLBAR_DELETE');
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


