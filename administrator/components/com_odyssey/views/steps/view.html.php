<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access
 
jimport( 'joomla.application.component.view');
require_once JPATH_COMPONENT.'/helpers/odyssey.php';
require_once JPATH_COMPONENT.'/helpers/step.php';
 

class OdysseyViewSteps extends JViewLegacy
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
    JToolBarHelper::title(JText::_('COM_ODYSSEY_STEPS_TITLE'), 'road');

    //Get the allowed actions list
    $canDo = OdysseyHelper::getActions();

    //Note: We check the user permissions only against the component since 
    //the step items have no categories.
    if($canDo->get('core.create')) {
      JToolBarHelper::addNew('step.add', 'JTOOLBAR_NEW');
    }

    //Notes: The Edit icon might not be displayed since it's not (yet ?) possible 
    //to edit several items at a time.
    if($canDo->get('core.edit') || $canDo->get('core.edit.own')) {
      JToolBarHelper::editList('step.edit', 'JTOOLBAR_EDIT');
    }

    if($canDo->get('core.edit.state')) {
      JToolBarHelper::divider();
      JToolBarHelper::custom('steps.publish', 'publish.png', 'publish_f2.png','JTOOLBAR_PUBLISH', true);
      JToolBarHelper::custom('steps.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
      JToolBarHelper::divider();
      JToolBarHelper::archiveList('steps.archive','JTOOLBAR_ARCHIVE');
      JToolBarHelper::custom('steps.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
      JToolBarHelper::trash('steps.trash','JTOOLBAR_TRASH');
    }

    if($canDo->get('core.delete')) {
      JToolBarHelper::divider();
      JToolBarHelper::deleteList('', 'steps.delete', 'JTOOLBAR_DELETE');
    }

    if($canDo->get('core.admin')) {
      JToolBarHelper::divider();
      JToolBarHelper::preferences('com_odyssey', 550);
    }

    //Check for a possible API connector plugin.
    $parameters = JComponentHelper::getParams('com_odyssey');
    if($parameters->get('api_connector') && !empty($parameters->get('api_plugin')) && $this->state->get('filter.step_type') == 'departure') {
      //Check first that the plugin is enabled.
      if(JPluginHelper::isEnabled('odysseyapiconnector', $parameters->get('plugin_name'))) {
	JToolBarHelper::divider();
	JToolbarHelper::custom('steps.updateAvailabilities', 'loop', '', 'COM_ODYSSEY_API_UPDATE_AVAILABILITIES', false);
      }
      else {
	JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_API_CONNECTOR_PLUGIN_NOT_INSTALLED'), 'warning');
      }
    }
  }


  protected function setDocument() 
  {
    //Include css file.
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_odyssey/odyssey.css');
  }
}


