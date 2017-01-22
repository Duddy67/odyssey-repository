<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access

$stepSeqDisplayed = $displayData['step_seq_displayed'];
$dptData = $displayData['dpt_data'];
?>

<hr class="hr-condensed" />
<div class="step-sequence-fields">
  <div class="control-label">
    <label id="jform_step_type-lbl" for="departure-data" class="hasTooltip" title=""
	   data-original-title="<?php echo JText::_('COM_ODYSSEY_FIELD_STEP_SEQUENCE_DESC'); ?>">
    <?php echo JText::_('COM_ODYSSEY_FIELD_STEP_SEQUENCE_LABEL'); ?>
    </label>
  </div>
  <div class="input-append">
    <input type="text" style="width:80px;" id="departure-data" name="departure_data" class="input-medium" value="<?php echo $dptData; ?>" />
    <a href="#" onclick="document.getElementById('adminForm').submit();" class="btn"><?php echo JText::_('COM_ODYSSEY_BUTTON_DISPLAY'); ?></a>
    <a href="#" onclick="document.getElementById('departure-data').value='';document.getElementById('adminForm').submit();" class="btn"><?php echo JText::_('JCLEAR'); ?></a>
  </div>
</div>

<?php if($stepSeqDisplayed) : //Filters are not taken in account when a step sequence is displayed. ?>
  <div class="step-sequence-fields">
    <div class="control-label">
      <label id="jform_step_type-lbl" for="time-offset" class="hasTooltip" title=""
	     data-original-title="<?php echo JText::_('COM_ODYSSEY_FIELD_TIME_OFFSET_DESC'); ?>">
      <?php echo JText::_('COM_ODYSSEY_FIELD_TIME_OFFSET_LABEL'); ?>
      </label>
    </div>
    <div class="input-append">
      <input type="text" style="width:80px;" id="time-offset" name="time_offset" class="input-medium" value="" />
      <a href="#" onclick="document.getElementById('adminForm').submit();" class="btn"><?php echo JText::_('COM_ODYSSEY_BUTTON_APPLY'); ?></a>
    </div>
  </div>

  <script type="text/javascript">
    //Reset all the filter select lists and search field.
    var filters = ['list_limit', 'filter_published', 'filter_step_type', 'filter_category_id', 'filter_user_id', 'filter_search', 'list_fullordering'];
    for(var i = 0; i < filters.length; i++) {
      if(filters[i] == 'list_limit') {
	document.getElementById(filters[i]).selectedIndex = 8;
	document.getElementById(filters[i]).value = '0';
      }
      else {
	document.getElementById(filters[i]).selectedIndex = 0;
	document.getElementById(filters[i]).value = '';
      }
    }
  </script>
<?php endif; ?>

