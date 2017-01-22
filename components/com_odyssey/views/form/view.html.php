<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');


class OdysseyViewForm extends JViewLegacy
{
  protected $form = null;
  protected $state = null;
  protected $item = null;
  protected $return_page = null;
  protected $isNew = 0;
  protected $location = null;

  function display($tpl = null)
  {
    $user = JFactory::getUser();

    //Redirect unregistered users to the login page.
    if($user->guest) {
      $app =& JFactory::getApplication();
      $app->redirect('index.php?option=com_users&view=login'); 
      return true;
    }

    // Initialise variables
    $this->form = $this->get('Form');
    $this->state = $this->get('State');
    $this->item = $this->get('Item');
    $this->return_page	= $this->get('ReturnPage');

    //Check if the user is allowed to create a new document.
    if(empty($this->item->id)) {
      $authorised = $user->authorise('core.create', 'com_odyssey') || (count($user->getAuthorisedCategories('com_odyssey', 'core.create')));
      $this->isNew = 1;
    }
    else { //Check if the user is allowed to edit this document. 
      $authorised = $this->item->params->get('access-edit');
    }

    if($authorised !== true) {
      JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));
      return false;
    }

    // Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseWarning(500, implode("\n", $errors));
      return false;
    }

    // Create a shortcut to the parameters.
    $params = &$this->state->params;
    //Get the possible extra class name.
    $this->pageclass_sfx = htmlspecialchars($params->get('pageclass_sfx'));

    $this->params = $params;

    // Override global params with document specific params
    $this->params->merge($this->item->params);
    $this->user = $user;

    if($params->get('enable_category') == 1) {
      $this->form->setFieldAttribute('catid', 'default', $params->get('catid', 1));
      $this->form->setFieldAttribute('catid', 'readonly', 'true');
    }

    //$this->setDocument();

    parent::display($tpl);
  }


  protected function setDocument() 
  {
    //Include css file (if needed).
    //$doc = JFactory::getDocument();
    //$doc->addStyleSheet(JURI::base().'components/com_odyssey/css/odyssey.css');
  }
}
