<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.modal');

//Lang tag is needed in the Ajax file.
$lang = JFactory::getLanguage();
$langTag = $lang->getTag();
?>

<script type="text/javascript">
//Global variables. It will be set as function in pricerule.js file.
var checkPriceruleData;

Joomla.submitbutton = function(task)
{
  if(task == 'pricerule.cancel' || document.formvalidator.isValid(document.id('pricerule-form'))) {
    //Check that all the data item has been properly set.
    if(task != 'pricerule.cancel' && !checkPriceruleData()) {
      return false;
    }

    Joomla.submitform(task, document.getElementById('pricerule-form'));
  }
}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=pricerule&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="pricerule-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_ODYSSEY_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span6">
	  <div class="form-vertical">
	    <?php
		  echo $this->form->getControlGroup('prule_type');
		  echo $this->form->getControlGroup('show_rule');
		  echo $this->form->getControlGroup('behavior');
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

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'pricerule-conditions', JText::_('COM_ODYSSEY_TAB_CONDITIONS')); ?>
	<div class="row-fluid">
	  <div class="span8" id="condition">
	    <div class="form-horizontal">
	      <?php
		    echo $this->form->getControlGroup('condition'); 
		    echo $this->form->getControlGroup('logical_opr'); 
		    echo $this->form->getControlGroup('since_date'); 
	      ?>
	      <div id="condition">
	      </div>
	    </div>
	  </div>
	</div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'pricerule-targets', JText::_('COM_ODYSSEY_TAB_TARGETS')); ?>
	<div class="row-fluid">
	  <div class="span12">
	    <div class="form-horizontal" id="targets">
	      <?php
		    echo $this->form->getControlGroup('target');
		    echo $this->form->getControlGroup('operation');
		    echo $this->form->getControlGroup('value');
	      ?>
	      <div id="target">
	      </div>
	    </div>
	  </div>
	</div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'pricerule-recipients', JText::_('COM_ODYSSEY_TAB_RECIPIENTS')); ?>
	<div class="row-fluid">
	  <div class="span8" id="recipients">
	    <div class="form-horizontal">
	      <?php echo $this->form->getControlGroup('recipient'); ?>
	      <div id="recipient">
	      </div>
	    </div>
	  </div>
	</div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

  </div>

  <input type="hidden" name="task" value="" />
  <input type="hidden" name="lang_tag" id="lang-tag" value="<?php echo $langTag; ?>" />
  <?php echo JHtml::_('form.token'); ?>
</form>

<?php
//Load the jQuery scripts.
$doc = JFactory::getDocument();
$doc->addScript(JURI::base().'components/com_odyssey/js/common.js');
$doc->addScript(JURI::base().'components/com_odyssey/js/pricerule.js');

