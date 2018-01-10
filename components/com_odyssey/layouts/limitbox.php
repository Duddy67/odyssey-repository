<?php
/**
 * @package SongBook
 * @copyright Copyright (c) 2016 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

$limit = $displayData['limit_range'];
$current = $displayData['current_limit'];
?>

<select id="limit" name="limit" class="inputbox input-mini" onchange="this.form.submit()">
<?php for($i = 1; $i < 8; $i++) : 
        $value = $limit * $i;

	$selected = '';
	if($current == $value) {
	  $selected = 'selected="selected"';
	}
?>
  <option value="<?php echo $value; ?>" <?php echo $selected; ?>><?php echo $value; ?></option>
<?php endfor; ?>

  <option value="0"><?php echo JText::_('JALL'); ?></option>
</select>
