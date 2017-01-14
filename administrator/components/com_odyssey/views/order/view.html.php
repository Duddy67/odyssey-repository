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
require_once JPATH_ROOT.'/administrator/components/com_odyssey/helpers/odyssey.php';
require_once JPATH_ROOT.'/components/com_odyssey/helpers/travel.php';
 

class OdysseyViewOrder extends JViewLegacy
{
  protected $item;
  protected $form;
  protected $state;
  protected $transactions;
  protected $passengers;
  protected $psgrForm;
  protected $preloadPsgr;
  protected $customerData;

  //Display the view.
  public function display($tpl = null)
  {
    $this->item = $this->get('Item');
    $this->form = $this->get('Form');
    $this->state = $this->get('State');
    //
    $this->transactions = $this->getModel()->getTransactions($this->item->id);
    $this->passengers = $this->getModel()->getPassengers($this->item->id, $this->item->nb_psgr);
    // create new JForm object
    $this->psgrForm = new JForm('PsgrForm');
    // Load any form .xml file you want (like registration.xml)
    $this->psgrForm->loadFile(OdysseyHelper::getOverridedFile(JPATH_ROOT.'/administrator/components/com_odyssey/models/forms/passenger.xml'));
    //Run the required functions in order to use the preload passengers feature.
    $this->preloadPsgr = JavascriptHelper::getPassengers($this->item->customer_id, false);
    JavascriptHelper::loadFunctions(array('passengers', 'passengerAttributes'), array($this->item->customer_id));
    //Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseError(500, implode('<br />', $errors));
      return false;
    }

    $this->customerData = TravelHelper::getCustomerData($this->item->customer_id);
    //Display the toolbar.
    $this->addToolBar();

    $this->setDocument();

    //Check for extra language file.
    $language = JFactory::getLanguage();
    OdysseyHelper::addExtraLanguage($language);

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
    JToolBarHelper::title($isNew ? JText::_('COM_ODYSSEY_NEW_ORDER') : JText::_('COM_ODYSSEY_EDIT_ORDER'), 'pencil-2');

    if($isNew) {
      //Check the "create" permission for the new records.
      if($canDo->get('core.create')) {
	JToolBarHelper::apply('order.apply', 'JTOOLBAR_APPLY');
	JToolBarHelper::save('order.save', 'JTOOLBAR_SAVE');
	JToolBarHelper::custom('order.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
      }
    }
    else {
      // Can't save the record if it's checked out.
      if(!$checkedOut) {
	// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
	if($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId)) {
	  // We can save the new record
	  JToolBarHelper::apply('order.apply', 'JTOOLBAR_APPLY');
	  JToolBarHelper::save('order.save', 'JTOOLBAR_SAVE');

	  // We can save this record, but check the create permission to see if we can return to make a new one.
	  if($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_odyssey', 'core.create'))) > 0) {
	    //JToolBarHelper::custom('order.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
	  }
	}
      }

      // If checked out, we can still save
      if($canDo->get('core.create')) {
	//JToolBarHelper::save2copy('order.save2copy');
      }
    }

    JToolBarHelper::cancel('order.cancel', 'JTOOLBAR_CANCEL');
  }


  protected function setDocument() 
  {
    //Include the css and Javascript files.
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_odyssey/odyssey.css');
    $doc->addScript(JURI::base().'components/com_odyssey/js/check.js');
  }
}



