<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access
 

jimport( 'joomla.application.component.view');
require_once JPATH_COMPONENT.'/helpers/odyssey.php';
require_once JPATH_COMPONENT.'/helpers/utility.php';
require_once JPATH_COMPONENT.'/helpers/odms.php';


class OdysseyViewCustomer extends JViewLegacy
{
  protected $item;
  protected $orders;
  protected $form;
  protected $state;

  //Display the view.
  public function display($tpl = null)
  {
    $this->item = $this->get('Item');
    //$this->orders = $this->get('Orders');
    $this->form = $this->get('Form');
    $this->state = $this->get('State');

    //Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseError(500, implode('<br />', $errors));
      return false;
    }

    //Gets and sets the file informations.
    $params = JComponentHelper::getParams('com_odyssey');
    $this->item->max_file_size = $params->get('max_file_size');
    $this->item->allowed_extensions = preg_replace('#;#', ', ', $params->get('allowed_extensions'));

    //Display the toolbar.
    $this->addToolBar();

    //Load css and script files.
    $this->setDocument();

    //Display the template.
    parent::display($tpl);
  }


  protected function addToolBar() 
  {
    //Make main menu inactive.
    JRequest::setVar('hidemainmenu', true);

    $user = JFactory::getUser();
    $userId = $user->get('id');

    //Get the allowed actions list
    $canDo = OdysseyHelper::getActions($this->state->get('filter.category_id'));
    $isNew = $this->item->id == 0;

    //Display the view title and the icon.
    JToolBarHelper::title(JText::_('COM_ODYSSEY_EDIT_CUSTOMER'), 'pencil-2');

    if($canDo->get('core.edit') || (count($user->getAuthorisedCategories('com_odyssey', 'core.edit'))) > 0 || $this->item->created_by == $userId) {
      // We can save the new record
      JToolBarHelper::apply('customer.apply', 'JTOOLBAR_APPLY');
      JToolBarHelper::save('customer.save', 'JTOOLBAR_SAVE');
    }

    JToolBarHelper::cancel('customer.cancel', 'JTOOLBAR_CANCEL');
  }


  protected function setDocument() 
  {
    //Include the Javascript file and the css file as well.
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_odyssey/odyssey.css');
  }
}



