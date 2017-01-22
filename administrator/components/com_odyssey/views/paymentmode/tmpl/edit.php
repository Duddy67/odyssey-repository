<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.formvalidation');
JHtml::_('behavior.tabstate');
JHtml::_('formbehavior.chosen', 'select');

$canDo = OdysseyHelper::getActions();
?>

<script type="text/javascript">
Joomla.submitbutton = function(task)
{
  if(task == 'paymentmode.cancel' || document.formvalidator.isValid(document.id('paymentmode-form'))) {
    Joomla.submitform(task, document.getElementById('paymentmode-form'));
  }
}
</script>


<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=paymentmode&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="paymentmode-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_ODYSSEY_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span6">
	  <div class="form-vertical">
	    <?php
		  echo $this->form->getControlGroup('plugin_element');
		  echo $this->form->getControlGroup('description');
		  echo $this->form->getControlGroup('information');
	      ?>
	  </div>
	</div>
	<div class="span3">
	  <?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>


      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('JGLOBAL_FIELDSET_PUBLISHING', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6">
	  <?php echo JLayoutHelper::render('joomla.edit.publishingdata', $this); ?>
	</div>
	<div class="span6">
	  <?php echo JLayoutHelper::render('joomla.edit.metadata', $this); ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

  </div>

  <input type="hidden" name="task" value="" />
  <?php echo JHtml::_('form.token'); ?>
</form>

