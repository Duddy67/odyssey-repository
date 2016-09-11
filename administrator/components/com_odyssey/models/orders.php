<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modellist');



class OdysseyModelOrders extends JModelList
{
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array(
	      'id', 'o.id',
	      'order_nb', 'o.order_nb',
	      'travel_name', 't.name',
	      'created', 'o.created',
	      'order_status', 'o.order_status',
	      'payment_status', 'o.payment_status',
	      'customer_id', 'o.customer_id',
	      'published', 'o.published',
	      'departure_date', 'o.departure_date',
	      'lastname',
      );
    }

    parent::__construct($config);
  }


  protected function populateState($ordering = null, $direction = null)
  {
    // Initialise variables.
    $app = JFactory::getApplication();
    $session = JFactory::getSession();

    // Adjust the context to support modal layouts.
    if($layout = JFactory::getApplication()->input->get('layout')) {
      $this->context .= '.'.$layout;
    }

    //Get the state values set by the user.
    $search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
    $this->setState('filter.search', $search);

    $customerId = $app->getUserStateFromRequest($this->context.'.filter.customer_id', 'filter_customer_id');
    $this->setState('filter.customer_id', $customerId);

    $orderStatus = $app->getUserStateFromRequest($this->context.'.filter.order_status', 'filter_order_status');
    $this->setState('filter.order_status', $orderStatus);

    $paymentStatus = $app->getUserStateFromRequest($this->context.'.filter.payment_status', 'filter_payment_status');
    $this->setState('filter.payment_status', $paymentStatus);

    $published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
    $this->setState('filter.published', $published);

    // List state information.
    parent::populateState('o.order_nb', 'asc');
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.published');
    $id .= ':'.$this->getState('filter.customer_id');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Get the default currency display.
    $parameters = JComponentHelper::getParams('com_odyssey');
    $display = $parameters->get('currency_display');

    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 'o.id,o.order_nb,o.created,o.customer_id,o.outstanding_balance,'.
	                           'o.digits_precision,o.limit_date,o.final_amount,o.payment_status,o.order_status,'.
				   'o.departure_date,o.nb_psgr,o.published,o.checked_out,o.checked_out_time'));

    $query->from('#__odyssey_order AS o');

    //Get the customer name.
    $query->select('u.name AS lastname');
    $query->join('LEFT', '#__users AS u ON u.id = o.customer_id');

    $query->select('cu.'.$display.' AS currency');
    $query->join('LEFT', '#__odyssey_currency AS cu ON cu.alpha=o.currency_code');

    $query->select('c.firstname');
    $query->join('LEFT', '#__odyssey_customer AS c ON c.id = o.customer_id');

    $query->select('t.name AS travel_name');
    $query->join('LEFT', '#__odyssey_order_travel AS t ON t.order_id = o.id');

    //Filter by title search.
    $search = $this->getState('filter.search');
    if(!empty($search)) {
      if(stripos($search, 'id:') === 0) {
	$query->where('o.id = '.(int) substr($search, 3));
      }
      else {
	$search = $db->Quote('%'.$db->escape($search, true).'%');
	$query->where('(o.order_nb LIKE '.$search.')');
      }
    }

    //Filter by publication state.
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      $query->where('o.published='.(int)$published);
    }
    elseif($published === '') {
      $query->where('(o.published IN (0, 1))');
    }

    // Join over the users for the checked out user.
    $query->select('uc.name AS editor');
    $query->join('LEFT', '#__users AS uc ON uc.id=o.checked_out');

    //Filter by customer.
    $customerId = $this->getState('filter.customer_id');
    if(is_numeric($customerId)) {
      $type = $this->getState('filter.customer_id.include', true) ? '= ' : '<>';
      $query->where('o.customer_id'.$type.(int) $customerId);
    }

    //Filter by order status.
    $orderStatus = $this->getState('filter.order_status');
    if(!empty($orderStatus)) {
      $query->where('o.order_status='.$db->Quote($orderStatus));
    }

    //Filter by payment status.
    $paymentStatus = $this->getState('filter.payment_status');
    if(!empty($paymentStatus)) {
      $query->where('o.payment_status='.$db->Quote($paymentStatus));
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 'o.order_nb');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }


  /**
   * Method to get an array of data items.
   *
   * @return  mixed  An array of data items on success, false on failure.
   *
   * @since   11.1
   */
  public function getItems()
  {
    $items = parent::getItems();

    return $items;
  }
}


