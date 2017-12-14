<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.formvalidation');
JHtml::_('behavior.modal');
JHtml::_('formbehavior.chosen', 'select');
?>

<script type="text/javascript">
Joomla.submitbutton = function(task)
{
  //If addon options are added check that an option type has been selected.
  var addonOptions = document.getElementsByClassName('option-item');
  var optionType = document.getElementById('jform_option_type');
  if(task != 'addon.cancel' && addonOptions.length > 0 && optionType.value == '') {
    alert('<?php echo JText::_('COM_ODYSSEY_ERROR_NO_OPTION_TYPE_SELECTED'); ?>');
    return false;
  }

  if(task == 'addon.cancel' || document.formvalidator.isValid(document.id('addon-form'))) {
    Joomla.submitform(task, document.getElementById('addon-form'));
  }
}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=addon&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="addon-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_ODYSSEY_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span8">
	  <div class="form-vertical">
	    <?php
	          if($this->item->id) {
		    $this->form->setValue('locked_addon_type', null, JText::_('COM_ODYSSEY_OPTION_'.strtoupper($this->item->addon_type)));
		  }

		  echo $this->form->getControlGroup('addon_type');
		  echo $this->form->getControlGroup('locked_addon_type');
		  echo $this->form->getControlGroup('nb_persons');
		  echo $this->form->getControlGroup('global');
		  echo $this->form->getControlGroup('code');
		  echo $this->form->getControlGroup('group_nb');
		  echo $this->form->getControlGroup('image');
		  echo $this->form->getControlGroup('description');
	      ?>
	  </div>
	</div>
	<div class="span4 form-vertical">
	  <?php
		echo JLayoutHelper::render('joomla.edit.global', $this);
		echo $this->form->getControlGroup('from_nb_psgr');
		echo $this->form->getControlGroup('to_nb_psgr');
	  ?>
	</div>
      </div>
    <?php echo JHtml::_('bootstrap.endTab'); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'addon-options', JText::_('COM_ODYSSEY_TAB_OPTIONS')); ?>
      <div class="row-fluid">
	<div class="span8" id="options">
	  <div class="form-vertical">
	    <?php echo $this->form->getControlGroup('option_type'); 
		  echo $this->form->getInput('imageurl'); //Must be loaded to call the overrided media file.
	    ?>
	    <div id="option">
	    </div>
	  </div>
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
//Load the jQuery scripts.
$doc = JFactory::getDocument();
$doc->addScript(JURI::base().'components/com_odyssey/js/common.js');
$doc->addScript(JURI::base().'components/com_odyssey/js/addon.js');

