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
 
use Joomla\Registry\Registry;

/**
 * Travel table class
 */
class OdysseyTableTravel extends JTable
{
  /**
   * Constructor
   *
   * @param object Database connector object
   */
  function __construct(&$db) 
  {
    parent::__construct('#__odyssey_travel', 'id', $db);
    //Needed to use the Joomla tagging system with the travel items.
    JTableObserverTags::createObserver($this, array('typeAlias' => 'com_odyssey.travel'));
  }


  /**
   * Overloaded bind function to pre-process the params.
   *
   * @param   mixed  $array   An associative array or object to bind to the JTable instance.
   * @param   mixed  $ignore  An optional array or space separated list of properties to ignore while binding.
   *
   * @return  boolean  True on success.
   *
   * @see     JTable:bind
   * @since   1.5
   */
  public function bind($array, $ignore = '')
  {
    if(isset($array['params']) && is_array($array['params'])) {
      // Convert the params field to a string.
      $registry = new JRegistry;
      $registry->loadArray($array['params']);
      $array['params'] = (string) $registry;
    }

    if(isset($array['metadata']) && is_array($array['metadata'])) {
      $registry = new JRegistry;
      $registry->loadArray($array['metadata']);
      $array['metadata'] = (string) $registry;
    }

    // Search for the {readmore} tag and split the text up accordingly.
    if(isset($array['traveltext'])) {
      $pattern = '#<hr\s+id=("|\')system-readmore("|\')\s*\/*>#i';
      $tagPos = preg_match($pattern, $array['traveltext']);

      if($tagPos == 0) {
	$this->intro_text = $array['traveltext'];
	$this->full_text = '';
      }
      else {
	//Split traveltext field data in 2 parts with the "readmore" tag as a separator.
	//Note: The "readmore" tag is not included in either part.
	list($this->intro_text, $this->full_text) = preg_split($pattern, $array['traveltext'], 2);
      }
    }

    // Bind the rules. 
    if(isset($array['rules']) && is_array($array['rules'])) {
      $rules = new JAccessRules($array['rules']);
      $this->setRules($rules);
    }

    return parent::bind($array, $ignore);
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

    //Important: When travel items are reordering in the travels list view, all
    //the tests below must not be performed.
    if(isset($post['order']) && isset($post['cid'])) {
      return parent::store($updateNulls);
    }

    //Get current date and time (equal to NOW() in SQL).
    $now = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);
    $user = JFactory::getUser();

    if($this->id) { // Existing item
      $this->modified = $now;
      $this->modified_by = $user->get('id');
    }
    else {
      // New travel. A travel created and created_by field can be set by the user,
      // so we don't touch either of these if they are set.
      if(!(int)$this->created) {
	$this->created = $now;
      }

      if(empty($this->created_by)) {
	$this->created_by = $user->get('id');
      }
    }

    //Set the alias of the travel.
    
    //Create a sanitized alias, (see stringURLSafe function for details).
    $this->alias = JFilterOutput::stringURLSafe($this->alias);
    //In case no alias has been defined, create a sanitized alias from the name field.
    if(empty($this->alias)) {
      $this->alias = JFilterOutput::stringURLSafe($this->name);
    }

    // Verify that the alias is unique
    $table = JTable::getInstance('Travel', 'OdysseyTable', array('dbo', $this->getDbo()));

    if($table->load(array('alias' => $this->alias, 'catid' => $this->catid)) && ($table->id != $this->id || $this->id == 0)) {
      $this->setError(JText::_('COM_ODYSSEY_DATABASE_ERROR_TRAVEL_UNIQUE_ALIAS'));
      return false;
    }

    //Check again some values in case Javascript has failed.
    if(empty($this->dpt_step_id)) {
      $this->setError(JText::_('COM_ODYSSEY_ERROR_NO_DEPARTURE_STEP_SELECTED'));
      return false;
    }

    if(empty($this->tax_id)) {
      $this->setError(JText::_('COM_ODYSSEY_ERROR_NO_TAX_SELECTED'));
      return false;
    }

    //Check again the value of the prices per passenger (in case Javascript has failed).
    foreach($post as $key => $value) {
      //Check that price is a valid integer or float number.
      if(preg_match('#^price_psgr_[0-9]+_[0-9]+$#', $key) && !preg_match('#^[0-9]+(\.)?(?(1)[0-9]+)$#', $value)) {
	$this->setError(JText::_('COM_ODYSSEY_ERROR_NUMBER_NOT_VALID'));
	return false;
      }

      if(preg_match('#^price_psgr_[0-9]+_[0-9]+$#', $key) && $value == 0) {
	$this->setError(JText::_('COM_ODYSSEY_ERROR_PRICE_CANNOT_BE_ZERO'));
	return false;
      }
    }

    return parent::store($updateNulls);
  }


  /**
   * Method to return the name to use for the asset table.
   *
   * @return  string
   *
   * @since   11.1
   */
  protected function _getAssetTitle()
  {
    return $this->name;
  }


  /**
   * Method to compute the default name of the asset.
   * The default name is in the form table_name.id
   * where id is the value of the primary key of the table.
   *
   * @return  string
   *
   * @since   11.1
   */
  protected function _getAssetName()
  {
    $k = $this->_tbl_key;
    return 'com_odyssey.travel.'.(int) $this->$k;
  }


  /**
   * We provide our global ACL as parent
   * @see JTable::_getAssetParentId()
   */

  //Note: The component categories ACL override the items ACL, (whenever the ACL of a
  //      category is modified, changes are spread into the items ACL).
  //      This is the default com_content behavior. see: libraries/legacy/table/content.php
  protected function _getAssetParentId(JTable $table = null, $id = null)
  {
    $assetId = null;

    // This is a travel under a category.
    if($this->catid) {
      // Build the query to get the asset id for the parent category.
      $query = $this->_db->getQuery(true)
              ->select($this->_db->quoteName('asset_id'))
              ->from($this->_db->quoteName('#__categories'))
              ->where($this->_db->quoteName('id').' = '.(int) $this->catid);

      // Get the asset id from the database.
      $this->_db->setQuery($query);

      if($result = $this->_db->loadResult()) {
        $assetId = (int) $result;
      }
    }

    // Return the asset id.
    if($assetId) {
      return $assetId;
    }
    else {
      return parent::_getAssetParentId($table, $id);
    }
  }
}


