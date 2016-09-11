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
 * Addon table class
 */
class OdysseyTableAddon extends JTable
{
  /**
   * Constructor
   *
   * @param object Database connector object
   */
  function __construct(&$db) 
  {
    parent::__construct('#__odyssey_addon', 'id', $db);
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
    if($this->id != 0) {
      //Check the addon type hasn't been modified (in case js failed and the addon type drop
      //down list is usable again).
      $table = JTable::getInstance('Addon', 'OdysseyTable', array('dbo', $this->getDbo()));
      if(!$table->load(array('id' => $this->id, 'addon_type' => $this->addon_type))) {
	$message = JText::_('COM_ODYSSEY_ERROR_MODIFIED_ITEM_TYPE');
	//Redirect to the addon list instead of the step edit form.
	JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_odyssey&view=addons', false), $message, 'error');
	return false;
      }
    }

    //Check that the group number is no yet used with another addon type.
    if($this->addon_type != 'addon_option' && $this->group_nb != 'none') {
      $query = $this->_db->getQuery(true)
		    ->select('COUNT(*)')
		    ->from($this->_db->quoteName('#__odyssey_addon'))
		    ->where('group_nb='.$this->_db->quote($this->group_nb))
		    ->where('addon_type!='.$this->_db->quote('addon_option'))
		    ->where('addon_type!='.$this->_db->quote($this->addon_type));
      $this->_db->setQuery($query);

      if($this->_db->loadResult()) {
	$this->setError(JText::_('COM_ODYSSEY_ERROR_GROUP_NB_ALREADY_USED'));
	return false;
      }

      $post = JFactory::getApplication()->input->post->getArray();
      $optionIds = array();

      foreach($post as $key => $value) {
	//Check for option type.
	if(preg_match('#^addon_id_([0-9]+)$#', $key, $matches) && !empty($value) && $this->option_type == '') {
	  $this->setError(JText::_('COM_ODYSSEY_ERROR_NO_OPTION_TYPE_SELECTED'));
	  return false;
	}

	//Check for possible duplicate entry of option.
	if(preg_match('#^addon_id_([0-9]+)$#', $key)) {
	  if(in_array($value, $optionIds)) {
	    $this->setError(JText::_('COM_ODYSSEY_ERROR_OPTION_DUPLICATE_ENTRY'));
	    return false;
	  }
	  else {
	    $optionIds[] = $value;
	  }
	}
      }
    }

    return parent::store($updateNulls);
  }
}


