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


  public function getCustomerData()
  {
    $user = JFactory::getUser();

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('u.name AS lastname, p.id, c.firstname, c.customer_title, a.street, a.city, a.postcode, a.phone,'.
	           'a.country_code, co.name AS country_name, co.lang_var AS country_lang_var, r.lang_var AS region_lang_var')
	  ->from('#__users AS u')
	  ->join('LEFT', '#__odyssey_customer AS c ON c.id=u.id')
	  ->join('LEFT', '#__odyssey_passenger AS p ON p.customer_id=u.id AND customer=1')
	  ->join('LEFT', '#__odyssey_address AS a ON a.item_id=u.id AND a.item_type="customer"')
	  ->join('LEFT', '#__odyssey_country AS co ON co.alpha_2=a.country_code')
	  ->join('LEFT', '#__odyssey_region AS r ON r.id_code=a.region_code')
	  ->where('u.id='.(int)$user->id);
    $db->setQuery($query);
    $customerData = $db->loadAssoc();

    return $customerData;
  }
}

