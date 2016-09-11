<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
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
  if(task == 'country.cancel' || document.formvalidator.isValid(document.getElementById('country-form'))) {
    if(task == 'country.cancel' || checkNumber('jform_numerical', true)) {
      Joomla.submitform(task, document.getElementById('country-form'));
    }
    else {
      alert('<?php echo $this->escape(JText::_('COM_ODYSSEY_ERROR_NUMBER_NOT_VALID'));?>');
    }
  }
}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=country&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="country-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_ODYSSEY_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span8">
	  <div class="form-vertical">
	    <?php
		  echo $this->form->getControlGroup('numerical');
		  echo $this->form->getControlGroup('alpha_2');
		  echo $this->form->getControlGroup('alpha_3');
		  echo $this->form->getControlGroup('continent_code');
		  echo $this->form->getControlGroup('lang_var');
		  echo $this->form->getControlGroup('description');
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

<?php
    $doc = JFactory::getDocument();
    $doc->addScript(JURI::base().'components/com_odyssey/js/common.js');
?>



