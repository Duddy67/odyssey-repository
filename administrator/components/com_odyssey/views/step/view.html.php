<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access

jimport( 'joomla.application.component.view');
require_once JPATH_COMPONENT.'/helpers/odyssey.php';
require_once JPATH_COMPONENT.'/helpers/javascript.php';
 

class OdysseyViewStep extends JViewLegacy
{
  protected $item;
  protected $form;
  protected $state;

  //Display the view.
  public function display($tpl = null)
  {
    $this->item = $this->get('Item');
    $this->form = $this->get('Form');
    $this->state = $this->get('State');

    //Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseError(500, implode('<br />', $errors));
      return false;
    }

    $names = array('city');
    $args = array();
    if($this->item->id) {
      $names[] = 'addonDepartures';

      if($this->item->step_type == 'link') {
	$args = array($this->item->id, $this->item->dpt_step_id, $this->item->step_type);
      }
      else {
	//In a case of a departure step, the id is also the departure step id.
	$args = array($this->item->id, $this->item->id, $this->item->step_type);
      }
    }

    JavascriptHelper::loadFunctions($names, $args);
    JavascriptHelper::getButtonText();
    JavascriptHelper::getCommonText();
    JavascriptHelper::getStepText();

    //Display the toolbar.
    $this->addToolBar();

    $this->setDocument();

    //Display the template.
    parent::display($tpl);
  }


  protected function addToolBar() 
  {
    //Make main menu inactive.
    JFactory::getApplication()->input->set('hidemainmenu', true);

    $user = JFactory::getUser();
    $userId = $user->get('id');

    //Get the allowed actions list
    $canDo = OdysseyHelper::getActions($this->state->get('filter.category_id'));
    $isNew = $this->item->id == 0;
    $checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);

    //Display the view title (according to the user action) and the icon.
    JToolBarHelper::title($isNew ? JText::_('COM_ODYSSEY_NEW_STEP') : JText::_('COM_ODYSSEY_EDIT_STEP'), 'pencil-2');

    if($isNew) {
      //Check the "create" permission for the new records.
      if($canDo->get('core.create')) {
	JToolBarHelper::apply('step.apply', 'JTOOLBAR_APPLY');
	JToolBarHelper::save('step.save', 'JTOOLBAR_SAVE');
	JToolBarHelper::custom('step.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
      }
    }
    else {
      // Can't save the record if it's checked out.
      if(!$checkedOut) {
	// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
	if($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId)) {
	  // We can save the new record
	  JToolBarHelper::apply('step.apply', 'JTOOLBAR_APPLY');
	  JToolBarHelper::save('step.save', 'JTOOLBAR_SAVE');

	  // We can save this record, but check the create permission to see if we can return to make a new one.
	  if($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_odyssey', 'core.create'))) > 0) {
	    JToolBarHelper::custom('step.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
	  }
	}
      }

      // If checked out, we can still save
      if($canDo->get('core.create')) {
	//JToolBarHelper::save2copy('step.save2copy');
      }
    }

    JToolBarHelper::cancel('step.cancel', 'JTOOLBAR_CANCEL');
  }


  protected function setDocument() 
  {
    //Include the css and Javascript files.
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_odyssey/odyssey.css');
    $doc->addScript(JURI::base().'components/com_odyssey/js/check.js');
  }
}



