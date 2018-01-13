<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access

JHtml::_('bootstrap.tooltip');

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
	echo '<h3>'.JText::_('COM_ODYSSEY_SENT_DOCUMENTS').'</h3>';

	if(empty($htmlDoc['sent'])) {
	  echo '<div class="alert alert-no-items">'.JText::_('JGLOBAL_NO_MATCHING_RESULTS').'</div>';
	}

	echo $htmlDoc['sent'];
  ?>
  <div>
  <input type="file" name="uploaded_file" /><br />
  <button onclick="Joomla.submitbutton('documents.uploadFile');" style="margin-top:10px;" class="btn btn-small btn-info">
  <span class="icon-upload icon-white"></span><?php echo JText::_('COM_ODYSSEY_BUTTON_UPLOAD_LABEL'); ?></button>
  </div>
  <br />
  <div class="alert">
    <?php echo JText::sprintf('COM_ODYSSEY_FILE_INFORMATIONS', $this->allowedExtensions, $this->maxFileSize); ?>
  </div>
  <hr>
  <?php
	echo '<h3>'.JText::_('COM_ODYSSEY_RECEIVED_DOCUMENTS').'</h3>';

	if(empty($htmlDoc['received'])) {
	  echo '<div class="alert alert-no-items">'.JText::_('JGLOBAL_NO_MATCHING_RESULTS').'</div>';
	}

	echo $htmlDoc['received'];
  ?>

<input type="hidden" name="option" value="com_odyssey" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="document_id" id="document-id" value="" />
<input type="hidden" name="item_id" value="<?php echo $userId; ?>" />
<?php echo JHtml::_('form.token'); ?>
</form>

