<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controlleradmin');
 

class OdysseyControllerSteps extends JControllerAdmin
{
  /**
   * Proxy for getModel.
   * @since 1.6
  */
  public function getModel($name = 'Step', $prefix = 'OdysseyModel', $config = array('ignore_request' => true))
  {
    $model = parent::getModel($name, $prefix, $config);
    return $model;
  }


  public function updateSteps()
  {
    // Check for request forgeries
    JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));

    // Get items to update from the request.
    $cid = $this->input->get('cid', array(), 'array');

    //Load the plugin language file.
    $lang = JFactory::getLanguage();
    $apiPlugin = JComponentHelper::getParams('com_odyssey')->get('api_plugin');
    $lang->load('plg_odysseyapiconnector_'.$apiPlugin, JPATH_ROOT.'/plugins/odysseyapiconnector/'.$apiPlugin, $lang->getTag());

    //Trigger the plugin event.
    $event = 'onOdysseyApiConnectorFunction';
    JPluginHelper::importPlugin('odysseyapiconnector');
    $dispatcher = JDispatcher::getInstance();
    $results = $dispatcher->trigger($event, array('updateSteps', array($cid)));

    $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view='.$this->view_list, false));

    return true;
  }
}



