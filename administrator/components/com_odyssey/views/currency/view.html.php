<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access

jimport( 'joomla.application.component.view');
require_once JPATH_COMPONENT.'/helpers/odyssey.php';


class OdysseyViewCurrency extends JViewLegacy
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

    //Display the toolbar.
    $this->addToolBar();

    //Display the template.
    parent::display($tpl);

    $this->setDocument();
  }


  protected function addToolBar() 
  {
    //Make main menu inactive.
    JFactory::getApplication()->input->set('hidemainmenu', true);

    $user = JFactory::getUser();
    $userId = $user->get('id');

    //Get the allowed actions list
    $canDo = OdysseyHelper::getActions();
    $isNew = $this->item->id == 0;
    $checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);

    //Display the view title (according to the user action) and the icon.
    JToolBarHelper::title($isNew ? JText::_('COM_ODYSSEY_NEW_CURRENCY') : JText::_('COM_ODYSSEY_EDIT_CURRENCY'), 'pencil-2');

    if($isNew) {
      //Check the "create" permission for the new records.
      if($canDo->get('core.create')) {
	JToolBarHelper::apply('currency.apply', 'JTOOLBAR_APPLY');
	JToolBarHelper::save('currency.save', 'JTOOLBAR_SAVE');
	JToolBarHelper::custom('currency.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
      }
    }
    else {
      // Can't save the record if it's checked out.
      if(!$checkedOut) {
	// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
	if($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId)) {
	  // We can save the new record
	  JToolBarHelper::apply('currency.apply', 'JTOOLBAR_APPLY');
	  JToolBarHelper::save('currency.save', 'JTOOLBAR_SAVE');

	  // We can save this record, but check the create permission to see if we can return to make a new one.
	  if($canDo->get('core.create')) {
	    JToolBarHelper::custom('currency.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
	  }
	}
      }

      // If checked out, we can still save
      if($canDo->get('core.create')) {
	JToolBarHelper::save2copy('currency.save2copy');
      }
    }

    JToolBarHelper::cancel('currency.cancel', 'JTOOLBAR_CANCEL');
  }


  protected function setDocument() 
  {
    //Include Javascript file.
    $doc = JFactory::getDocument();
    $doc->addScript(JURI::base().'components/com_odyssey/js/check.js');
  }
}



