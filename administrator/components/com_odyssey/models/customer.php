<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modeladmin');


class OdysseyModelCustomer extends JModelAdmin
{
  //Prefix used with the controller messages.
  protected $text_prefix = 'COM_ODYSSEY';

  public function getTable($type = 'Customer', $prefix = 'OdysseyTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  //Overrided function.
  public function getItem($pk = null)
  {
    // Initialise variables.
    $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName().'.id');
    $table = $this->getTable();

    if($pk > 0) {
      // Attempt to load the row.
      $return = $table->load($pk);

      // Check for a table object error.
      if($return === false && $table->getError()) {
	$this->setError($table->getError());
	return false;
      }
    }

    // Convert to the JObject before adding other data.
    $properties = $table->getProperties(1);
    $item = JArrayHelper::toObject($properties, 'JObject');

    if(property_exists($item, 'params')) {
      $registry = new JRegistry;
      $registry->loadString($item->params);
      $item->params = $registry->toArray();
    }

    //Override:
    //We need to add several data to customer item. Those data comes from
    //different tables.  
    $db = $this->getDbo();
    $query = $db->getQuery(true);
    //Get user data.
    $query->select('u.name, u.username, u.email, u.registerDate, u.lastvisitDate');
    $query->from('#__users AS u');
    $query->join('INNER','#__odyssey_customer AS c ON c.id='.$pk);
    $query->where('u.id = c.id');
    $db->setQuery($query);
    $userData = $db->loadAssoc();

    //Add data to customer item.
    foreach($userData as $key => $value) {
      $item->$key = $value;
    }

    // Get the dispatcher and load the profile plugins.
    $dispatcher = JEventDispatcher::getInstance();
    // Load the user plugins for backward compatibility (v3.3.3 and earlier).
    JPluginHelper::importPlugin('user');
    // Trigger the data preparation event.
    $dispatcher->trigger('onContentPrepareData', array('com_odyssey.customer', $item));
    //End override.

    return $item;
  }


  public function getForm($data = array(), $loadData = true) 
  {
    $form = $this->loadForm('com_odyssey.customer', 'customer', array('control' => 'jform', 'load_data' => $loadData));

    if(empty($form)) {
      return false;
    }

    return $form;
  }


  protected function loadFormData() 
  {
    // Check the session for previously entered form data.
    $data = JFactory::getApplication()->getUserState('com_odyssey.edit.customer.data', array());

    if(empty($data)) {
      $data = $this->getItem();
    }

    return $data;
  }


  public function getOrders() 
  {
    //Get the selected customer.
    $customer = $this->getItem();

    //Get the customer form.
    $form = $this->getForm();
    //Get the default limit_item value.
    $defaultLimitItem = $form->getFieldAttribute('limit_item', 'default');
    //Set the default limit_item value. 
    $limitItem = JFactory::getApplication()->input->post->get('limit_item', $defaultLimitItem, 'int');

    //Note: Default value set to zero means to get all of the items.
    $limit = '';
    if($limitItem) {
      $limit = 'LIMIT '.$limitItem;
    }

    $db = $this->getDbo();
    $query = $db->getQuery(true);

    $query->select('o.id,o.name,o.final_cart_amount,o.cart_status,o.order_status,'.
	           'o.currency_code,o.created,d.final_shipping_cost,'.
		   'd.status AS shipping_status,t.status AS payment_status');
    $query->from('#__jooshop_order AS o');
    $query->join('LEFT', '#__jooshop_delivery AS d ON d.order_id=o.id');
    $query->join('LEFT', '#__jooshop_transaction AS t ON t.order_id=o.id');
    $query->where('o.user_id='.(int)$customer->user_id);
    $query->order('o.created DESC '.$limit);
    $db->setQuery($query);
    $orders = $db->loadObjectList();
    //Compute the total amount of each customer's order.
    foreach($orders as $order) {
      $order->total = $order->final_cart_amount + $order->final_shipping_cost;
    }

    // Return the result
    return $orders;
  }
}

