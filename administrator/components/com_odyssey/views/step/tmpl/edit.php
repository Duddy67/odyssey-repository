<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');

//Lang tag is needed in the Ajax file.
$lang = JFactory::getLanguage();
$langTag = $lang->getTag();
?>

<script type="text/javascript">
//Global variables. It will be set as function in step.js file.
var checkStepData;
//Global variable. It will be set as function in common.js file.
var checkAlias;

Joomla.submitbutton = function(task)
{
  if(task == 'step.cancel' || document.formvalidator.isValid(document.id('step-form'))) {
    //Check that all the data item has been properly set.
    if(task != 'step.cancel' && (!checkStepData(document.getElementById('jform_step_type').value, document.getElementById('jform_id').value)
				  || !checkAlias(task, 'step'))) {
      return false;
    }

    Joomla.submitform(task, document.getElementById('step-form'));
  }
}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=step&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="step-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_ODYSSEY_TAB_DETAILS')); ?>
      <div class="row-fluid">
	<div class="span8">
	  <div class="form-vertical">
	    <?php
	          //Set the values of the "readonly" fields.
	          if($this->item->id) {
		    $this->form->setValue('locked_step_type', null, JText::_('COM_ODYSSEY_OPTION_'.strtoupper($this->item->step_type)));
		    if($this->item->step_type == 'link') {
		      $this->form->setValue('link_step_category', null, $this->escape($this->item->category_title));
		    }
		  }

		  echo $this->form->getControlGroup('step_type');
		  echo $this->form->getControlGroup('locked_step_type');
		  echo $this->form->getControlGroup('group_alias');
		  echo $this->form->getControlGroup('subtitle');
		  echo $this->form->getControlGroup('image');
		  echo $this->form->getControlGroup('description');
	      ?>
	  </div>
	</div>
	<div class="span4">
	  <div class="form-vertical">
	  <?php
		echo JLayoutHelper::render('joomla.edit.global', $this);
		echo $this->form->getControlGroup('link_step_category');
	  ?>
	  </div>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'step-departures', JText::_('COM_ODYSSEY_TAB_DEPARTURES')); ?>
      <div class="row-fluid">
	<div class="span8" id="departures">
	  <div class="form-vertical">
	    <?php echo $this->form->getControlGroup('date_type'); ?>
	    <div id="departure">
	    </div>
	  </div>
	</div>
      </div>
    <?php echo JHtml::_('bootstrap.endTab'); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'step-departure-step', JText::_('COM_ODYSSEY_TAB_DEPARTURE_STEP')); ?>
      <div class="row-fluid">
	<div class="span10" id="departure-step">
	  <div class="form-vertical">
	    <?php echo $this->form->getControlGroup('dpt_step_id'); ?>
	    <table id="time-gaps" class="table table-striped">
	    </table>
	  </div>
	</div>
      </div>
    <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'step-cities', JText::_('COM_ODYSSEY_TAB_CITIES')); ?>
	<div class="row-fluid">
	  <div class="span5" id="cities">
	    <div class="form-vertical">
	      <div id="city">
	      </div>
	    </div>
	  </div>
	</div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

    <?php if($this->item->id) : //As long as the item is not saved, no dynamical items can be added. ?>
      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'step-transitcities', JText::_('COM_ODYSSEY_TAB_TRANSIT_CITIES')); ?>
	<div class="row-fluid">
	  <div class="span6" id="transitcities">
	    <div class="form-vertical">
	      <div id="transitcity">
	      </div>
	    </div>
	  </div>
	</div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'step-addons', JText::_('COM_ODYSSEY_TAB_ADDONS')); ?>
	<div class="row-fluid">
	  <div class="span8" id="addons">
	    <div class="form-vertical">
	      <div id="addon">
	      </div>
	    </div>
	  </div>
	</div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>
    <?php endif; ?>

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

  <input type="hidden" name="lang_tag" id="lang-tag" value="<?php echo $langTag; ?>" />
  <input type="hidden" name="current_dpt_step_id" id="current-dpt-step-id" value="<?php echo $this->item->dpt_step_id; ?>" />
  <input type="hidden" name="task" value="" />
  <?php echo JHtml::_('form.token'); ?>
</form>

<?php
//Load the jQuery scripts.
$doc = JFactory::getDocument();
$doc->addScript(JURI::base().'components/com_odyssey/js/common.js');
$doc->addScript(JURI::base().'components/com_odyssey/js/step.js');

