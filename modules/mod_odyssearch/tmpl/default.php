<?php
/**
 * @package Odyssey
 * @copyright Copyright (c)2016 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; // No direct access.
JHtml::_('formbehavior.chosen', 'select');

//Include css file.
$css = JFactory::getDocument();
$css->addStyleSheet(JURI::base().'modules/mod_odyssearch/odyssearch.css');
?>

<?php if ($form) : ?>
<div class="odyssearch-module">
  <form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=search&itemId='.$itemId);?>" method="post" name="adminForm" id="adminForm">
    <?php foreach ($form->getFieldset() as $fieldName => $field) : ?>
      <?php if ($fieldName != 'filter_search' && in_array($field->getAttribute('name'), $showedFilters)) : ?>
	<?php
	      $field->__set('onchange', ''); //Don't submit form whenever the drop down list value is changed. 
	      echo $field->input;
	?>
      <?php endif; ?>
    <?php endforeach; ?>

    <input type="submit" class="btn btn-warning" value="<?php echo JText::_('MOD_ODYSSEARCH_BUTTON_SEARCH'); ?>" />
  </form>
<?php endif; ?>
</div>
