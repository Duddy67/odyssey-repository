<?php
/**
 * @package JooShop
 * @copyright Copyright (c)2012 - 2015 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.tabstate');
JHtml::_('formbehavior.chosen', 'select');
?>

<form action="<?php echo JRoute::_('index.php?option=com_jooshop&view=customer&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="customer-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_JOOSHOP_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span4">
	  <div class="form-vertical">
	    <?php
		  echo $this->form->getControlGroup('username');
		  echo $this->form->getControlGroup('email');
		  echo $this->form->getControlGroup('registerDate');
		  echo $this->form->getControlGroup('lastvisitDate');
		  echo $this->form->getControlGroup('user_id');
	      ?>
	  </div>
	</div>
	<div class="span3">
	  <?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>


  <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'shipping', JText::_('COM_USER_JOOSHOP_CUSTOMER_SLIDER_SHIPPING_LABEL', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6">
	  <div class="form-vertical">

	    <table class="table">
	      <tr><td class="address-label">
		<?php echo JText::_('COM_JOOSHOP_FIELD_STREET_LABEL'); ?>
	      </td><td>
	      <?php echo $this->item->street_sh; ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_JOOSHOP_FIELD_POSTCODE_LABEL'); ?>
	      </td><td>
	      <?php echo $this->item->postcode_sh; ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_JOOSHOP_FIELD_CITY_LABEL'); ?>
	      </td><td>
	      <?php echo $this->item->city_sh; ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_JOOSHOP_FIELD_REGION_LABEL'); ?>
	      </td><td>
	      <?php echo JText::_($this->item->region_lang_var_sh); ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_JOOSHOP_FIELD_COUNTRY_LABEL'); ?>
	      </td><td>
	      <?php echo JText::_($this->item->country_lang_var_sh); ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_JOOSHOP_FIELD_PHONE_LABEL'); ?>
	      </td><td>
	      <?php echo $this->item->phone_sh; ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_JOOSHOP_FIELD_NOTE_LABEL'); ?>
	      </td><td>
	      <?php echo $this->item->note_sh; ?>
	      </td></tr>
	      </table>
	  </div>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

  <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'billing', JText::_('COM_USER_JOOSHOP_CUSTOMER_SLIDER_BILLING_LABEL', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6">
	  <div class="form-vertical">

	    <table class="table">
	      <tr><td class="address-label">
		<?php echo JText::_('COM_JOOSHOP_FIELD_STREET_LABEL'); ?>
	      </td><td>
	      <?php echo $this->item->street_bi; ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_JOOSHOP_FIELD_POSTCODE_LABEL'); ?>
	      </td><td>
	      <?php echo $this->item->postcode_bi; ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_JOOSHOP_FIELD_CITY_LABEL'); ?>
	      </td><td>
	      <?php echo $this->item->city_bi; ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_JOOSHOP_FIELD_REGION_LABEL'); ?>
	      </td><td>
	      <?php echo JText::_($this->item->region_lang_var_bi); ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_JOOSHOP_FIELD_COUNTRY_LABEL'); ?>
	      </td><td>
	      <?php echo JText::_($this->item->country_lang_var_bi); ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_JOOSHOP_FIELD_PHONE_LABEL'); ?>
	      </td><td>
	      <?php echo $this->item->phone_bi; ?>
	      </td></tr>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_JOOSHOP_FIELD_NOTE_LABEL'); ?>
	      </td><td>
	      <?php echo $this->item->note_bi; ?>
	      </td></tr>
	      </table>
	  </div>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>
  </div>

  <?php echo JHtml::_('form.token'); ?>
</form>

