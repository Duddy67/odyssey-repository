<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');

$canDo = OdysseyHelper::getActions();
?>
<script type="text/javascript">
Joomla.submitbutton = function(task)
{
  if(task == 'testimony.cancel' || document.formvalidator.isValid(document.getElementById('testimony-form'))) {
    Joomla.submitform(task, document.getElementById('testimony-form'));
  }
}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=testimony&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="testimony-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_ODYSSEY_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span8">
	  <div class="form-vertical">
	    <?php
		  echo $this->form->getControlGroup('travel_id');
		  echo $this->form->getControlGroup('author_name');
		  echo $this->form->getControlGroup('facebook');
		  echo $this->form->getControlGroup('twitter');
		  echo $this->form->getControlGroup('google_plus');
		  echo $this->form->getControlGroup('pinterest');
		  echo $this->form->getControlGroup('testimony_text');
	      ?>
	  </div>
	</div>
	<div class="span4">
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



