<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.tabstate');


$user = JFactory::getUser();
$userId = $user->get('id');
$htmlDoc = OdmsHelper::renderDocuments($userId, 'customer', true);
?>

<script type="text/javascript">

function removeDocument(documentId)
{
  document.getElementById('document-id').value = documentId;
  Joomla.submitbutton('documents.removeDocument');
}
</script>


<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=documents');?>" method="post" enctype="multipart/form-data" name="adminForm" id="adminForm">

  <?php
	echo $htmlDoc['sent'];

	$fieldset = $this->myForm->getFieldset('details');
	foreach($fieldset as $field) {
	  if($field->getAttribute('name') == 'uploaded_file') {
	    echo $field->getControlGroup();
	  }
	}
  ?>

  <button onclick="Joomla.submitbutton('documents.uploadFile');" class="btn
  btn-small btn-success"> <span class="icon-apply icon-white"></span>Save</button>
  <?php echo $htmlDoc['received']; ?>

<input type="hidden" name="option" value="com_odyssey" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="document_id" id="document-id" value="" />
<?php echo JHtml::_('form.token'); ?>
</form>

