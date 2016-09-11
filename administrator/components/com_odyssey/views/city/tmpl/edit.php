<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');
?>
<script type="text/javascript">
Joomla.submitbutton = function(task)
{
  if(task == 'city.cancel' || document.formvalidator.isValid(document.getElementById('city-form'))) {
    Joomla.submitform(task, document.getElementById('city-form'));
  }
}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=city&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="city-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_ODYSSEY_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span8">
	  <div class="form-vertical">
	    <?php
		  echo $this->form->getControlGroup('country_code');
		  echo $this->form->getControlGroup('region_code');
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
  <?php //Required for the dynamical Javascript region setting. ?>
  <input type="hidden" name="hidden_region_code" id="hidden-region-code" value="<?php echo $this->form->getValue('region_code'); ?>" />

  <input type="hidden" name="task" value="" />
  <?php echo JHtml::_('form.token'); ?>
</form>

<?php
    $doc = JFactory::getDocument();
    $doc->addScript(JURI::base().'components/com_odyssey/js/setregions.js');

