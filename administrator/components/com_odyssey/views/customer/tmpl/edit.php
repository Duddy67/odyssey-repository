<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.tabstate');
JHtml::_('formbehavior.chosen', 'select');

//Build a status array.
$status = array();
$status['completed'] = 'COM_ODYSSEY_OPTION_COMPLETED_STATUS';
$status['pending'] = 'COM_ODYSSEY_OPTION_PENDING_STATUS';
$status['other'] = 'COM_ODYSSEY_OPTION_OTHER_STATUS';
$status['cancelled'] = 'COM_ODYSSEY_OPTION_CANCELLED_STATUS';
$status['error'] = 'COM_ODYSSEY_OPTION_ERROR_STATUS';
$status['no_shipping'] = 'COM_ODYSSEY_OPTION_NO_SHIPPING_STATUS';
$status['unfinished'] = 'COM_ODYSSEY_OPTION_UNFINISHED_STATUS';
$status['undefined'] = 'COM_ODYSSEY_OPTION_UNDEFINED_STATUS';
$status['cartbackup'] = 'COM_ODYSSEY_OPTION_CART_BACKUP_STATUS';

$limitItem = JFactory::getApplication()->input->post->get('limit_item', null, 'int');

$fieldsets = $this->form->getFieldsets();
//var_dump($fieldsets);
$profile = $this->form->getFieldset('odysseyprofile');
//var_dump($profile);
$htmlDoc = OdmsHelper::renderDocuments($this->item->id, 'customer');
?>

<script type="text/javascript">
Joomla.submitbutton = function(task)
{
  if(task == 'customer.cancel' || document.formvalidator.isValid(document.id('customer-form'))) {
    Joomla.submitform(task, document.getElementById('customer-form'));
  }
  else {
    alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
  }
}


function setLimitItem(this_)
{
  var limitItem = document.getElementsByName('limit_item')[0];
  limitItem.value = this_.value; 

  this_.form.submit();
}


function removeDocument(documentId)
{
  document.getElementById('document-id').value = documentId;
  Joomla.submitbutton('customer.removeDocument');
}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=customer&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="customer-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_ODYSSEY_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span4">
	  <div class="form-vertical">
	    <?php
		  echo $this->form->getControlGroup('firstname');
		  echo $this->form->getControlGroup('customer_title');
		  echo $this->form->getControlGroup('username');
		  echo $this->form->getControlGroup('email');
		  echo $this->form->getControlGroup('registerDate');
		  echo $this->form->getControlGroup('lastvisitDate');
		  echo $this->form->getControlGroup('id');
	      ?>
	  </div>
	</div>
	<div class="span3">
	  <?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>


  <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'shipping', JText::_('COM_ODYSSEY_TAB_ADDRESS', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6">
	  <div class="form-vertical">
	  <?php foreach($this->form->getFieldset('odysseyprofile') as $field) : ?>
		  <?php if($field->name == 'jform[odysseyprofile][firstname]' || $field->name == 'jform[odysseyprofile][customer_title]') {
			  continue; 
			}
                  ?>
		  <?php if($field->hidden) : ?>
			  <div class="control-group">
				  <div class="controls">
				    <?php echo $field->input; ?>
				  </div>
			  </div>
		  <?php else: ?>
			  <div class="control-group">
				  <div class="control-label">
				    <?php echo $field->label; ?>
				  </div>
				  <div class="controls">
				    <?php echo $field->input; ?>
				  </div>
			  </div>
		    <?php if($field->name == 'jform[odysseyprofile][region_code]') : //Required for the dynamical Javascript setting. ?>
			    <input type="hidden" name="hidden_region_code" id="hidden-region-code" value="<?php echo $field->value; ?>" />
		    <?php endif; ?> 
		  <?php endif; ?>
	  <?php endforeach; ?>
	  </div>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'documents', JText::_('COM_ODYSSEY_TAB_DOCUMENTS', true)); ?>
	<div class="row-fluid form-horizontal-desktop">
	  <div class="span6">
	    <?php
		  echo '<h3>'.JText::_('COM_ODYSSEY_SENT_DOCUMENTS').'</h3>';

		  if(empty($htmlDoc['sent'])) {
		    echo '<div class="alert alert-no-items">'.JText::_('JGLOBAL_NO_MATCHING_RESULTS').'</div>';
		  }

	          echo $htmlDoc['sent'];
	    ?>
	    <div>
	    <input type="file" name="uploaded_file" /><br />
	    <button onclick="Joomla.submitbutton('customer.uploadFile');" style="margin-top:10px;" class="btn btn-small btn-info">
	    <span class="icon-upload icon-white"></span><?php echo JText::_('COM_ODYSSEY_BUTTON_UPLOAD_LABEL'); ?></button>
	    </div>
	    <hr>
	    <?php
		  echo '<h3>'.JText::_('COM_ODYSSEY_RECEIVED_DOCUMENTS').'</h3>';

		  if(empty($htmlDoc['received'])) {
		    echo '<div class="alert alert-no-items">'.JText::_('JGLOBAL_NO_MATCHING_RESULTS').'</div>';
		  }

		  echo $htmlDoc['received'];
	    ?>
	  </div>
	</div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>
  </div>

  <input type="hidden" name="task" value="" />
  <input type="hidden" name="limit_item" value="<?php echo $limitItem; ?>" />
  <input type="hidden" name="document_id" id="document-id" value="" />
  <?php echo JHtml::_('form.token'); ?>
</form>

<?php
$doc = JFactory::getDocument();
//Load the jQuery scripts.
//Note: Use the script of the frontend.
$doc->addScript(JURI::root().'components/com_odyssey/js/setregions.js');

