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
class OdysseyControllerBooking extends JControllerForm
{
  public function checkPassengers()
  {
    TravelHelper::checkBookingProcess();

    //$user = JFactory::getUser();
    $post = $this->input->post->getArray();

    $passengers = TravelHelper::checkInPassengers($post);

    //Grab the user session.
    $session = JFactory::getSession();
    //Store the passenger data.
    $session->set('passengers', $passengers, 'odyssey'); 

    $this->setRedirect(JRoute::_('index.php?option='.$this->option.'&view=booking', false));

    return true;
  }
}

