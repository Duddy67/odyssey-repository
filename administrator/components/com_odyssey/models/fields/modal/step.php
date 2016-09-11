<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('JPATH_BASE') or die;

/**
 * Supports a modal departure step picker.
 *
 */
class JFormFieldModal_Step extends JFormField
{
  /**
   * The form field type.
   *
   * @var		string
   * @since   1.6
   */
  protected $type = 'Modal_Step';

  /**
   * Method to get the field input markup.
   *
   * @return  string	The field input markup.
   * @since   1.6
   */
  protected function getInput()
  {
    $allowEdit = ((string) $this->element['edit'] == 'true') ? true : false;
    $allowClear	= ((string) $this->element['clear'] != 'false') ? true : false;

    // Load language
    JFactory::getLanguage()->load('com_odyssey', JPATH_ADMINISTRATOR);

    // Load the modal behavior script.
    JHtml::_('behavior.modal', 'a.modal');
    
    //Define the JS function and its global definition to use according to 
    //the form from which the modal window is called.
    $globalFuncDef = 'var createTimeGapItems;';
    $callingFunction = 'createTimeGapItems(id);';
    $tableId = 'time-gaps';
    //Link type steps can only select departure steps.
    $modalOption = 'dpt_only';
    if($this->form->getName() == 'com_odyssey.travel') {
      $globalFuncDef = 'var createPriceTables';
      $callingFunction = 'createPriceTables(id);';
      $tableId = 'travel-prices';
      //Travel items can only select published departure steps.
      $modalOption = 'published_dpt_only';
    }

    // Build the script.
    $script = array();

    // Select button script
			//Global variables. It will be set as a function in step.js or pricerule.js file.
    $script[] = '	'.$globalFuncDef;
    $script[] = '	function jSelectStep_'.$this->id.'(id, title) {';
                                //Remove all possible previous data in the containers.
    $script[] = '		jClearStep(\''.$this->id.'\');';
                                //Set the values of the selected item. 
    $script[] = '		document.getElementById("'.$this->id.'_id").value = id;';
    $script[] = '		document.getElementById("'.$this->id.'_name").value = title;';
                                //Call the js function which create the departure rows. 
    $script[] = '               '.$callingFunction;

    if($allowEdit) {
      $script[] = '		jQuery("#'.$this->id.'_edit").removeClass("hidden");';
    }

    if($allowClear) {
      $script[] = '		jQuery("#'.$this->id.'_clear").removeClass("hidden");';
    }

    $script[] = '		SqueezeBox.close();';
    $script[] = '	}';

    // Clear button script
    static $scriptClear;

    if($allowClear && !$scriptClear) {
	    $scriptClear = true;

	    $script[] = '	function jClearStep(id) {';
	    $script[] = '		document.getElementById(id + "_id").value = "";';
	    $script[] = '		document.getElementById(id + "_name").value = "'.htmlspecialchars(JText::_('COM_ODYSSEY_SELECT_A_DEPARTURE_STEP', true), ENT_COMPAT, 'UTF-8').'";';
	    $script[] = '		jQuery("#"+id + "_clear").addClass("hidden");';
	    $script[] = '		if (document.getElementById(id + "_edit")) {';
	    $script[] = '			jQuery("#"+id + "_edit").addClass("hidden");';
	    $script[] = '		}';

	                                //Remove all rows from the table.
	    $script[] = '		jQuery("#'.$tableId.'").empty();';

	    if($this->form->getName() == 'com_odyssey.travel') {
					  //Remove all price row tables.
	      $script[] = '		jQuery("#addons").empty();';
	      $script[] = '		jQuery("#cities").empty();';
	      $script[] = '		jQuery("#transitcities").empty();';
	    }
	    else { //step
					  //Remove all dynamical items in containers.
	      $script[] = '		jQuery("#addon-container").empty();';
	      $script[] = '		jQuery("#city-container").empty();';
	      $script[] = '		jQuery("#transitcity-container").empty();';
	    }
	    $script[] = '		return false;';
	    $script[] = '	}';
    }

    // Add the script to the document head.
    JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));

    // Setup variables for display.
    $html = array();
    $link = 'index.php?option=com_odyssey&amp;view=steps&amp;layout=modal&amp;tmpl=component&amp;'.
            'modal_option='.$modalOption.'&amp;function=jSelectStep_'.$this->id; 

    if(isset($this->element['language'])) {
      $link .= '&amp;forcedLanguage='.$this->element['language'];
    }

    if((int) $this->value > 0) {
      $db = JFactory::getDbo();
      $query = $db->getQuery(true)
	      ->select($db->quoteName('name'))
	      ->from($db->quoteName('#__odyssey_step'))
	      ->where($db->quoteName('id').' = '.(int) $this->value);
      $db->setQuery($query);

      try {
	$title = $db->loadResult();
      }
      catch(RuntimeException $e) {
	JError::raiseWarning(500, $e->getMessage());
      }
    }

    if(empty($title)) {
      $title = JText::_('COM_ODYSSEY_SELECT_A_DEPARTURE_STEP');
    }

    $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

    // The active travel id field.
    if(0 == (int) $this->value) {
      $value = '';
    }
    else {
      $value = (int) $this->value;
    }

    // The current step display field.
    $html[] = '<span class="input-append">';
    $html[] = '<input type="text" class="input-medium" id="'.$this->id.'_name" value="'.$title.'" disabled="disabled" />';
    $html[] = '<a class="modal btn hasTooltip" title="'.JHtml::tooltipText('COM_ODYSSEY_CHANGE_DEPARTURE_STEP').'"  href="'.$link.'&amp;'.JSession::getFormToken().'=1" rel="{handler: \'iframe\', size: {x: 800, y: 450}}"><i class="icon-file"></i> '.JText::_('JSELECT').'</a>';

    // Edit travel button
    //TODO: Set up the edit modal layout.
    /*if($allowEdit) {
      $html[] = '<a class="btn hasTooltip'.($value ? '' : ' hidden').'" href="index.php?option=com_odyssey&layout=modal&tmpl=component&task=travel.edit&id=' . $value. '" target="_blank" title="'.JHtml::tooltipText('COM_CONTENT_EDIT_ARTICLE').'" ><span class="icon-edit"></span> ' . JText::_('JACTION_EDIT') . '</a>';
    }*/

    // Clear travel button
    if($allowClear) {
      $html[] = '<button id="'.$this->id.'_clear" class="btn'.($value ? '' : ' hidden').'" onclick="return jClearStep(\''.$this->id.'\')"><span class="icon-remove"></span> ' . JText::_('JCLEAR') . '</button>';
    }

    $html[] = '</span>';

    // class='required' for client side validation
    $class = '';
    if($this->required) {
      $class = ' class="required modal-value"';
    }

    $html[] = '<input type="hidden" id="'.$this->id.'_id"'.$class.' name="'.$this->name.'" value="'.$value.'" />';

    return implode("\n", $html);
  }
}

