<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modeladmin');


class OdysseyModelPassengers extends JModelAdmin
{
  public function getForm($data = array(), $loadData = true) 
  {
    $form = $this->loadForm('com_odyssey.passenger', 'passenger', array('control' => 'jform', 'load_data' => $loadData));

    if(empty($form)) {
      return false;
    }

    return $form;
  }
}

