<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
 
// import Joomla table library
jimport('joomla.database.table');
 
/**
 * Pricerule table class
 */
class OdysseyTablePricerule extends JTable
{
  /**
   * Constructor
   *
   * @param object Database connector object
   */
  function __construct(&$db) 
  {
    parent::__construct('#__odyssey_pricerule', 'id', $db);
  }


  /**
   * Overrides JTable::store to set modified data and user id.
   *
   * @param   boolean  $updateNulls  True to update fields even if they are null.
   *
   * @return  boolean  True on success.
   *
   * @since   11.1
   */
  public function store($updateNulls = false)
  {
    $post = JFactory::getApplication()->input->post->getArray();

    //Important: When price rule items are reordering in the price rules list view, all
    //the tests below must not be performed.
    if(isset($post['order']) && isset($post['cid'])) {
      return parent::store($updateNulls);
    }

    //Check that data has been properly set according to the price rule type selected.

    $recipientExists = $targetExists = $conditionExists = false;
    $travelIds = array();

    foreach($post as $key => $value) {
      if(preg_match('#^recipient_id_([0-9]+)$#', $key) && !empty($value)) {
	//Confirm that at least one recipient has been selected.
	$recipientExists = true;
      }

      if(preg_match('#^target_id_([0-9]+)$#', $key) && !empty($value)) {
	//Check for duplicate travels in the selection.
	if($this->prule_type == 'catalog' && $this->target == 'travel') {
	  if(!in_array($value, $travelIds)) {
	    $travelIds[] = $value;
	  }
	  else {
	    $this->setError(JText::_('COM_ODYSSEY_ERROR_TRAVEL_DUPLICATE_ENTRY'));
	    return false;
	  }
	}

	//Confirm that at least one target has been selected.
	$targetExists = true;
      }

      if(preg_match('#^condition_id_([0-9]+)$#', $key, $matches) && !empty($value)) {
	$idNb = $matches[1];
	//Check that the value field has been properly set as quantity or amount value.
	if($this->condition != 'travel_cat_amount') {
	  if(!ctype_digit($post['condition_item_qty_'.$idNb]) || $post['condition_item_qty_'.$idNb] == 0) {
	    $this->setError(JText::_('COM_ODYSSEY_ERROR_INVALID_QUANTITY_VALUE'));
	    return false;
	  }
	}
	else {
	  $amount = $post['condition_item_amount_'.$idNb];
	  if(empty($amount) || !preg_match('#^[0-9]+(\.)?(?(1)[0-9]+)$#', $amount) || $amount == 0) {
	    $this->setError(JText::_('COM_ODYSSEY_ERROR_INVALID_AMOUNT_VALUE'));
	    return false;
	  }
	}

	//Confirm that at least one condition has been selected and the value field is
	//properly set.
	$conditionExists = true;
      }

      if(preg_match('#^psgr_nbs_([0-9]+)$#', $key) && !preg_match('#^[1-9][0-9]*(,[1-9][0-9]*)*$#', $value) && !preg_match('#^0$#', $value)) {
	$this->setError(JText::_('COM_ODYSSEY_ERROR_NUMBER_LIST_NOT_VALID'));
	return false;
      }

      if(preg_match('#^travel_ids_([0-9]+)$#', $key) && !preg_match('#^[1-9][0-9]*(,[1-9][0-9]*)*$#', $value) && !preg_match('#^0$#', $value)) {
	$this->setError(JText::_('COM_ODYSSEY_ERROR_NUMBER_LIST_NOT_VALID'));
	return false;
      }

      if(preg_match('#^dpt_nbs_([0-9]+)$#', $key) && !preg_match('#^[1-9][0-9]*(,[1-9][0-9]*)*$#', $value) && !preg_match('#^0$#', $value)) {
	$this->setError(JText::_('COM_ODYSSEY_ERROR_NUMBER_LIST_NOT_VALID'));
	return false;
      }

      if(preg_match('#^step_ids_([0-9]+)$#', $key) && !preg_match('#^[1-9][0-9]*(,[1-9][0-9]*)*$#', $value) && !preg_match('#^0$#', $value)) {
	$this->setError(JText::_('COM_ODYSSEY_ERROR_NUMBER_LIST_NOT_VALID'));
	return false;
      }

      //Check that price rule value is a valid integer or float number.
      if($this->target == 'travel' && preg_match('#^value_psgr_[0-9]+_[0-9]+_[0-9]+$#', $key) 
	 && !preg_match('#^[0-9]+(\.)?(?(1)[0-9]+)$#', $value)) {
	$this->setError(JText::_('COM_ODYSSEY_ERROR_NUMBER_NOT_VALID'));
	return false;
      }
    }

    if(!$recipientExists) {
      $this->setError(JText::_('COM_ODYSSEY_ERROR_NO_RECIPIENT_SELECTED'));
      return false;
    }

    if($this->target != 'travel') {
      if($this->prule_type == 'catalog' && !$targetExists) {
	$this->setError(JText::_('COM_ODYSSEY_ERROR_NO_TARGET_SELECTED'));
	return false;
      }

      if(empty($this->value) || !preg_match('#^[0-9]+(\.)?(?(1)[0-9]+)$#', $this->value) || $this->value == 0) {
	$this->setError(JText::_('COM_ODYSSEY_ERROR_OPERATION_VALUE_NOT_VALID'));
	return false;
      }
    }

    if($this->prule_type == 'cart') {
      if(!empty($this->since_date) && !preg_match('#^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}(:[0-9]{2})?$#', $this->since_date)) {
	$this->setError(JText::_('COM_ODYSSEY_ERROR_INVALID_DATETIME_VALUE'));
	return false;
      }

      if(!$conditionExists) {
	$this->setError(JText::_('COM_ODYSSEY_ERROR_NO_CONDITION_SELECTED'));
	return false;
      }
    }

    return parent::store($updateNulls);
  }
}


