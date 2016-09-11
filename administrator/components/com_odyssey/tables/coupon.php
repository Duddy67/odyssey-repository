<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access
defined('_JEXEC') or die('Restricted access');
 
// import Joomla table library
jimport('joomla.database.table');
 
/**
 * Coupon table class
 */
class OdysseyTableCoupon extends JTable
{
  /**
   * Constructor
   *
   * @param object Database connector object
   */
  function __construct(&$db) 
  {
    parent::__construct('#__odyssey_coupon', 'id', $db);
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
    //Check again some values in case Javascript has failed.
    if(empty($this->prule_id)) {
      $this->setError(JText::_('COM_ODYSSEY_ERROR_NO_PRICERULE_SELECTED'));
      return false;
    }

    if(!preg_match('#^[a-zA-Z0-9-_]{5,}$#', $this->code)) {
      $this->setError(JText::_('COM_ODYSSEY_ERROR_COUPON_CODE_NOT_VALID'));
      return false;
    }

    //Verify that the code is unique
    $table = JTable::getInstance('Coupon', 'OdysseyTable', array('dbo', $this->getDbo()));

    if($table->load(array('code' => $this->code)) && ($table->id != $this->id || $this->id == 0)) {
      $this->setError(JText::_('COM_ODYSSEY_DATABASE_ERROR_COUPON_UNIQUE_CODE'));
      return false;
    }

    //Verify that the price rule is not used elsewhere.
    if($table->load(array('prule_id' => $this->prule_id)) && ($table->id != $this->id || $this->id == 0)) {
      $this->setError(JText::_('COM_ODYSSEY_DATABASE_ERROR_PRICERULE_ALREADY_USED'));
      return false;
    }

    return parent::store($updateNulls);
  }
}


