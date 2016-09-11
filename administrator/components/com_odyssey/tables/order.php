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
 * Order table class
 */
class OdysseyTableOrder extends JTable
{
  /**
   * Constructor
   *
   * @param object Database connector object
   */
  function __construct(&$db) 
  {
    parent::__construct('#__odyssey_order', 'id', $db);
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
    //Detect from where saving is come from.
    $isSite = JFactory::getApplication()->isSite();
    //Run tests only if saving comes from backend.
    if(!$isSite) {
      //Get current date and time (equal to NOW() in SQL).
      $now = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);
      $this->modified = $now;

      //Check that final amount and outstanding balance are valid integer or float number.
      if(!preg_match('#^[0-9]+(\.)?(?(1)[0-9]+)$#', $this->final_amount) || 
	 !preg_match('#^[0-9]+(\.)?(?(1)[0-9]+)$#', $this->outstanding_balance)) {
	$this->setError(JText::_('COM_ODYSSEY_ERROR_NUMBER_NOT_VALID'));
	return false;
      }

      //Check the number of passengers value.
      if(!ctype_digit($this->nb_psgr) || $this->nb_psgr < 1) {
	$this->setError(JText::_('COM_ODYSSEY_ERROR_NB_PSGR_NOT_VALID'));
	return false;
      }

      //Limit date has been exceeded so the order is cancelled. 
      //Note: For safety reason we also check that nothing has been paid.
      if($this->limit_date != '0000-00-00 00:00:00' && $this->limit_date < $now && $this->outstanding_balance == $this->final_amount) {
	$this->order_status = 'cancelled';
	$this->payment_status = 'cancelled';
      }
    }

    return parent::store($updateNulls);
  }
}


