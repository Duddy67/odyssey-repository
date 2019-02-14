<?php
/**
 * @package Odyssey 
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; // No direct access.
jimport('joomla.application.component.controller');


class OdysseyController extends JControllerLegacy
{
  public function display($cachable = false, $urlparams = false) 
  {
    require_once JPATH_COMPONENT.'/helpers/odyssey.php';

    //Display the submenu.
    OdysseyHelper::addSubmenu($this->input->get('view', 'travels'));

    //Check if the Odyssey plugin is installed (or enabled).
    //If it doesn't we display a warning note.
    if(!JPluginHelper::isEnabled('content', 'odyssey')) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_ODYSSEY_ODYSSEY_PLUGIN_NOT_INSTALLED'), 'warning');
    }

    //Set the default view.
    $this->input->set('view', $this->input->get('view', 'odyssey'));

    //Display the view.
    parent::display();
  }
}


