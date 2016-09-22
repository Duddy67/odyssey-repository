<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// no direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');

//Grab the user session.
$session = JFactory::getSession();
$travel = $session->get('travel', array(), 'odyssey'); 
$addons = $session->get('addons', array(), 'odyssey'); 
$settings = $session->get('settings', array(), 'odyssey'); 
//echo '<pre>';
//var_dump($travel);
//echo '</pre>';
?>
<script type="text/javascript">
function checkForm() {
  var form = document.getElementById('passengers');
  //Parse the form elements.
  for(var i = 0; i < form.length; i++) {
    //firstname and lastname fields are mandatory. In case one of them is empty the Joomla
    //script prevent the form to be submit, so the submit button must still be visible.
    if(/^firstname_/.test(form.elements[i].id) || /^lastname_/.test(form.elements[i].id)) {
      if(form.elements[i].value == '') {
	return false;
      }
    }
  }

  //The form is properly set so we can hide the submit button.
  hideButton('btn');
}
</script>

<?php echo JLayoutHelper::render('booking_breadcrumb', array('position' => 'passengers', 'travel' => $travel),
                                  JPATH_SITE.'/components/com_odyssey/layouts/'); ?>

<?php echo JLayoutHelper::render('booking_summary', array('travel' => $travel, 'settings' => $settings, 'addons' => $addons),
				  JPATH_SITE.'/components/com_odyssey/layouts/'); ?>

<form action="index.php?option=com_odyssey&task=booking.checkPassengers" method="post" name="passengers" id="passengers" autocomplete="off">
<?php
$fieldset = $this->form->getFieldset('details');

for($i = 0; $i < (int)$travel['nb_psgr']; $i++) {
  $psgrNb = $i + 1;
  echo '<div class="passenger-nb span4">';
  echo '<h2 class="passenger-nb">'.JText::sprintf('COM_ODYSSEY_TITLE_PASSENGER_NB', $psgrNb).'</h2>';

  if($i != 0 && !empty($this->preloadPsgr)) {
    echo '<select name="preloadpsgr_'.$psgrNb.'">'.
	 '<option value="">Select a passenger</options>';

    foreach($this->preloadPsgr as $passenger) {
      $selected = '';
      if(isset($this->passengers[$i]) && $this->passengers[$i]['id'] == $passenger['id']) {
	$selected = 'selected="selected"';
      }

      echo '<option value="'.$passenger['id'].'" '.$selected.'>'.
	    $this->escape($passenger['firstname']).' '.$this->escape($passenger['lastname']).'</option>';
    }

    echo '</select>';
  }

  foreach($fieldset as $field) {
    $name = $field->getAttribute('name');

    //Number the name and the id of the field for each passenger.
    $field->__set('name', $name.'_'.$psgrNb);
    $field->__set('id', $name.'_'.$psgrNb);

    //Populate the first passenger form with the customer's data.
    $value = $readonly = '';
    if($psgrNb == 1 && isset($this->customerData[$name])) {
      $field->setValue($this->customerData[$name]);
      $field->__set('readonly', 'readonly');
    }
    else { //Set fields to empty value for all the other passengers. 
      $field->setValue('');
      $field->__set('readonly', '');
    }

    if($field->getAttribute('type') == 'hidden') {
      //Set the hidden fields manualy as the JFormField class is weirdly built.
      echo '<input type="hidden" name="'.$field->__get('name').'" id="'.$field->__get('id').'" value="'.$field->__get('value').'">';
    }
    else {
      //Display label and field.
      echo $field->getControlGroup();
    }
  }

  echo '</div>';
}
?>
  <div id="btn-message">
    <input type="submit" class="btn btn-warning" onclick="checkForm();" value="<?php echo JText::_('COM_ODYSSEY_BUTTON_NEXT'); ?>" />
  </div>
</form>

<?php
//Load the jQuery scripts.
$doc = JFactory::getDocument();
$doc->addScript(JURI::root().'/administrator/components/com_odyssey/js/preloadpassengers.js');

