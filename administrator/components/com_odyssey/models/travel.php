<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
jimport('joomla.application.component.modeladmin');
require_once JPATH_ADMINISTRATOR.'/components/com_odyssey/helpers/odyssey.php';
require_once JPATH_ADMINISTRATOR.'/components/com_odyssey/helpers/utility.php';


class OdysseyModelTravel extends JModelAdmin
{
  //Prefix used with the controller messages.
  protected $text_prefix = 'COM_ODYSSEY';

  //Returns a Table object, always creating it.
  //Table can be defined/overrided in tables/itemname.php file.
  public function getTable($type = 'Travel', $prefix = 'OdysseyTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  public function getForm($data = array(), $loadData = true) 
  {
    //Check first for possible form override.
    $path = OdysseyHelper::getOverridedFile(JPATH_ROOT.'/administrator/components/com_odyssey/models/forms/travel.xml');
    $form = $this->loadForm('com_odyssey.travel', $path, array('control' => 'jform', 'load_data' => $loadData));

    if(empty($form)) {
      return false;
    }

    return $form;
  }


  protected function loadFormData() 
  {
    // Check the session for previously entered form data.
    $data = JFactory::getApplication()->getUserState('com_odyssey.edit.travel.data', array());

    if(empty($data)) {
      $data = $this->getItem();
    }

    return $data;
  }


  /**
   * Method to get a single record.
   *
   * @param   integer  $pk  The id of the primary key.
   *
   * @return  mixed  Object on success, false on failure.
   */
  public function getItem($pk = null)
  {
    if($item = parent::getItem($pk)) {
      //Get both intro_text and full_text together as traveltext
      $item->traveltext = trim($item->full_text) != '' ? $item->intro_text."<hr id=\"system-readmore\" />".$item->full_text : $item->intro_text;

      if(!empty($item->id)) {
	//Get tags for this item.
	$item->tags = new JHelperTags;
	$item->tags->getTagIds($item->id, 'com_odyssey.travel');

	//Get possible travel code from the departure step.
	$db = $this->getDbo();
	$query = $db->getQuery(true);
	$query->select('code')
	      ->from('#__odyssey_step')
	      ->where('id='.(int)$item->dpt_step_id);
	$db->setQuery($query);
	$item->code = $db->loadResult();

	if(empty($item->code)) {
	  $item->code = JText::_('COM_ODYSSEY_NO_CODE_AVAILABLE');
	}
      }

      if(!empty($item->extra_fields)) {
	//Gets the extrafield array back.
	$item->extra_fields = json_decode($item->extra_fields, true);
      }
    }

    return $item;
  }


  /**
   * Saves the manually set order of records.
   *
   * @param   array    $pks    An array of primary key ids.
   * @param   integer  $order  +1 or -1
   *
   * @return  mixed
   *
   * @since   12.2
   */
  public function saveorder($pks = null, $order = null)
  {
    //First ensure only the tag filter has been selected.
    if(OdysseyHelper::checkSelectedFilter('tag', true)) {

      if(empty($pks)) {
	return JError::raiseWarning(500, JText::_($this->text_prefix.'_ERROR_NO_ITEMS_SELECTED'));
      }

      //Get the id of the selected tag and the limitstart value.
      $post = JFactory::getApplication()->input->post->getArray();
      $tagId = $post['filter']['tag'];
      $limitStart = $post['limitstart'];

      //Set the mapping table ordering.
      OdysseyHelper::mappingTableOrder($pks, $tagId, $limitStart);

      return true;
    }

    //Hand over to the parent function.
    return parent::saveorder($pks, $order);
  }
}

