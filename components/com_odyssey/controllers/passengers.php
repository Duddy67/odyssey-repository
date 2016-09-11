<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
require_once JPATH_COMPONENT.'/helpers/travel.php';


/**
 * @package     Joomla.Site
 * @subpackage  com_odyssey
 */
class OdysseyControllerPassengers extends JControllerForm
{
  public function checkUser()
  {
    TravelHelper::checkBookingProcess();

    $user = JFactory::getUser();

    //If SEF is enabled we must set the Itemid variable to zero in order to
    //avoid SEF to bind any previous menu item id to the address or registration view.  
    $Itemid = '';
    if(JFactory::getConfig()->get('config.sef', false)) {
      $Itemid = '&Itemid=0';
    }

    //Grab the user session.
    $session = JFactory::getSession();
    $session->set('location', 'passengers', 'odyssey'); 
$addons = $session->get('addons', array(), 'odyssey'); 
echo '<pre>';
var_dump($addons);
echo '</pre>';
//return;

    //If the user is logged we redirect him to the first ordering step.
    if($user->id > 1) {
      $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=passengers'.$Itemid, false));
    }
    else { //The user must login or registrate.
      $this->setRedirect(JRoute::_('index.php?option=com_users&view=login'.$Itemid, false));
    }

    return;
  }
}

