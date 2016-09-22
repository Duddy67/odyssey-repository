<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access.
defined('_JEXEC') or die;
JHtml::_('behavior.formvalidation');
?>
<script type="text/javascript">
Joomla.submitbutton = function(task)
{
  if(task == 'order.cancel' || document.formvalidator.isValid(document.id('order-form'))) {
    Joomla.submitform(task, document.getElementById('order-form'));
  }
  else {
    alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
  }
}
</script>


<form action="<?php echo JRoute::_('index.php?option=com_odyssey&o_id='.$this->item->id); ?>" 
      method="post" name="adminForm" id="order-form" enctype="multipart/form-data" class="form-validate" autocomplete="off">

    <?php if(!$this->outOfDate && $this->item->order_status != 'cancelled') : ?>
      <div class="btn-toolbar">
	<div class="btn-group">
	  <button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('order.save')">
		  <span class="icon-ok"></span>&#160;<?php echo JText::_('JSAVE') ?>
	  </button>
	</div>
    <?php endif; ?>
	<div class="btn-group">
	  <button type="button" class="btn" onclick="Joomla.submitbutton('order.cancel')">
		  <span class="icon-cancel"></span>&#160;<?php echo JText::_('JCANCEL') ?>
	  </button>
	</div>
      </div>

  <div class="form-inline form-inline-header">
    <?php echo $this->form->getControlGroup('order_nb'); ?>
  </div>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>
    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_ODYSSEY_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span5 form-vertical">
	<?php
	      echo $this->form->getControlGroup('customer');
	      echo $this->form->getControlGroup('order_status');
	      echo $this->form->getControlGroup('final_amount');
	      echo $this->form->getControlGroup('outstanding_balance');
	      echo $this->form->getControlGroup('nb_psgr');

	      //Set the date format according to the date type of the departure.
	      $departureDate = JHtml::_('date', $this->item->departure_date, JText::_('DATE_FORMAT_LC2'));
	      if($this->item->date_type == 'period') {
		$departureDate = JHtml::_('date', $this->item->departure_date, JText::_('DATE_FORMAT_LC3'));
	      }

	      $this->form->setValue('departure_date', null, $departureDate);
	      echo $this->form->getControlGroup('departure_date');
	      echo $this->form->getControlGroup('deposit_rate');
	      echo $this->form->getInput('id');
          ?>
	</div>
	<div class="span5 form-vertical">
	  <?php
		echo $this->form->getControlGroup('travel_name');
		echo $this->item->order_details;
	    ?>
	</div>

	<?php if($this->remainingPayment) : //There is something left to pay. ?>
	  <div id="btn-message">
	    <a class="btn btn-warning" onclick="hideButton('btn')" href="<?php echo JRoute::_('index.php?option=com_odyssey&view=outstdbal'); ?>">
	    <?php echo JText::_('COM_ODYSSEY_PAY'); ?></a>
	  </div>
	<?php endif; ?>

      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'transactions', JText::_('COM_ODYSSEY_TAB_TRANSACTIONS')); ?>
	<div class="row-fluid">
	  <div class="span12 form-vertical">
	    <?php echo $this->form->getControlGroup('payment_status'); ?>

	    <?php if(!empty($this->transactions)) : ?>
	      <table class="table">
	      <tr>
		<th><?php echo JText::_('COM_ODYSSEY_HEADER_PAYMENT_MODE'); ?></th>
		<th><?php echo JText::_('COM_ODYSSEY_HEADER_AMOUNT_TYPE'); ?></th>
		<th><?php echo JText::_('COM_ODYSSEY_HEADER_AMOUNT'); ?></th>
		<th><?php echo JText::_('COM_ODYSSEY_HEADER_RESULT'); ?></th>
		<th><?php echo JText::_('COM_ODYSSEY_HEADER_CREATED'); ?></th>
		<th><?php echo JText::_('COM_ODYSSEY_HEADER_DETAIL'); ?></th>
	      </tr>
	      <?php
		    foreach($this->transactions as $transaction) {
		      echo '<tr><td>'.$transaction->payment_mode.'</td><td>'.$transaction->amount_type.'</td><td>'.
			   UtilityHelper::formatNumber($transaction->amount,
			       $this->item->digits_precision).' '.$this->item->currency_code.
			   '</td><td>'.$transaction->result.'</td><td>'.
			   JHtml::_('date', $transaction->created, JText::_('DATE_FORMAT_LC2')).'</td><td>'.
			   '<textarea readonly>'.$transaction->detail.'</textarea></td></tr>';
		    }
		?>
	      </table>
	  <?php endif; ?>
	  </div>
	</div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'passengers', JText::_('COM_ODYSSEY_TAB_PASSENGERS')); ?>
	<div class="row-fluid">
	  <div class="span12 form-vertical">
	  <?php
	  $fieldset = $this->psgrForm->getFieldset('details');

	  for($i = 0; $i < (int)$this->item->nb_psgr; $i++) {
	    $psgrNb = $i + 1;
	    echo '<div class="span4 passenger-nb">';
	    echo '<h2 class="passenger-nb">'.JText::sprintf('COM_ODYSSEY_TITLE_PASSENGER_NB', $psgrNb).'</h2>';

	    //Build a preload passenger drop down list. 
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

	      if(isset($this->passengers[$i])) {
		$field->setValue($this->passengers[$i][$name]);
	      }
	      else { //Set fields to empty value for all the other passengers. 
		$field->setValue('');
	      }
	      //Populate the first passenger form with the customer's data.
	      $value = $readonly = '';
	      if($psgrNb == 1 && isset($this->customerData[$name])) {
		$field->setValue($this->customerData[$name]);
		$field->__set('readonly', 'readonly');
	      }
	      else { //Set fields to empty value for all the other passengers. 
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
	  </div>
	</div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

  </div>
  <input type="hidden" name="task" value="" />
  <?php echo JHtml::_('form.token'); ?>

</form>

<?php
//Load the jQuery scripts.
$doc = JFactory::getDocument();
$doc->addScript(JURI::base().'administrator/components/com_odyssey/js/preloadpassengers.js');

