<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

$params = $displayData['params'];
$item = $displayData['item'];
?>

<p class="readmore">
	<a class="btn" href="<?php echo $displayData['link']; ?>" itemprop="url">
		<span class="icon-chevron-right"></span>
		<?php if (!$params->get('access-view')) :
			echo JText::_('COM_ODYSSEY_REGISTER_TO_READ_MORE');
		else :
			echo JText::_('COM_ODYSSEY_READ_MORE');
		endif; ?>
	</a>
</p>

