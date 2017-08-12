<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
?>


<ol class="nav nav-tabs nav-stacked">
<?php foreach ($this->link_items as &$item) : ?>
	<li>
	  <a href="<?php echo JRoute::_(OdysseyHelperRoute::getTravelRoute($item->slug, $item->tag_ids, 0, true)); ?>">
		      <?php echo $item->name; ?></a>
	</li>
<?php endforeach; ?>
</ol>

