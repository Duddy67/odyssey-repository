<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');
?>

<script type="text/javascript">
checkCouponData = function()
{
  //Check first that a price rule has been selected.
  if(document.getElementById('jform_prule_id_id').value == '') {
    alert('<?php echo $this->escape(JText::_('COM_ODYSSEY_ERROR_NO_PRICERULE_SELECTED'));?>');
    alertRed('jform_prule_id_name', 'details', 'jform_prule_id');
    return false;
  }

  //Then check the coupon code is correct.
  var code = document.getElementById('jform_code').value; 
  var codePattern = /^[a-zA-Z0-9-_]{5,}$/;
  if(!codePattern.test(code)) {
    alert('<?php echo $this->escape(JText::_('COM_ODYSSEY_ERROR_COUPON_CODE_NOT_VALID'));?>');
    alertRed('jform_code', 'details');
    return false;
  }

  return true;
};


Joomla.submitbutton = function(task)
{
  if(task == 'coupon.cancel' || document.formvalidator.isValid(document.id('coupon-form'))) {
    if(task != 'coupon.cancel' && !checkCouponData()) {
      return false;
    }

    if(task == 'coupon.cancel' || (checkNumber('jform_max_nb_uses', true) && checkNumber('jform_max_nb_coupons', true, true))) {
      Joomla.submitform(task, document.getElementById('coupon-form'));
    }
    else {
      alert('<?php echo $this->escape(JText::_('COM_ODYSSEY_ERROR_NUMBER_NOT_VALID'));?>');
    }
  }
}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=coupon&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="coupon-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_ODYSSEY_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span6">
	  <div class="form-vertical">
	    <?php
		  echo $this->form->getControlGroup('prule_id');
		  echo $this->form->getControlGroup('code');
		  echo $this->form->getControlGroup('login_mandatory');
		  echo $this->form->getControlGroup('max_nb_uses');
		  echo $this->form->getControlGroup('max_nb_coupons');
		  echo $this->form->getControlGroup('description');
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

<?php
//Load the jQuery scripts.
$doc = JFactory::getDocument();
$doc->addScript(JURI::base().'components/com_odyssey/js/common.js');

