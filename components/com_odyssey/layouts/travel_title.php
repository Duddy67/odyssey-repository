<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

// Create a shortcut for params.
$item = $displayData['item'];
$params = $displayData['params'];
$nowDate = $displayData['now_date'];
?>

<?php if($params->get('show_name') || $item->published == 0 || ($params->get('show_author') && !empty($item->author))) : ?>
  <div class="page-header">
    <?php if($params->get('show_name')) : ?>
	    <h2>
	      <?php if($params->get('link_name') && $params->get('access-view')) : ?>
		<a href="<?php echo JRoute::_(OdysseyHelperRoute::getTravelRoute($item->slug, $item->catid)); ?>">
		      <?php echo $this->escape($item->name); ?></a>
	      <?php else : ?>
		<?php echo $this->escape($item->name); ?>
	      <?php endif; ?>
	    </h2>
    <?php endif; ?>

    <?php if($item->published == 0) : ?>
	    <span class="label label-warning"><?php echo JText::_('JUNPUBLISHED'); ?></span>
    <?php endif; ?>
    <?php if($item->published == 2) : ?>
	    <span class="label label-warning"><?php echo JText::_('JARCHIVED'); ?></span>
    <?php endif; ?>
    <?php if (strtotime($item->publish_up) > strtotime($nowDate)) : ?>
	    <span class="label label-warning"><?php echo JText::_('JNOTPUBLISHEDYET'); ?></span>
    <?php endif; ?>
    <?php if ((strtotime($item->publish_down) < strtotime($nowDate)) && $item->publish_down != '0000-00-00 00:00:00') : ?>
	    <span class="label label-warning"><?php echo JText::_('JEXPIRED'); ?></span>
    <?php endif; ?>


    <?php if(!empty($item->subtitle)) : ?>
      <h3><?php echo $this->escape($item->subtitle); ?></h3>
    <?php endif; ?>
  </div>
<?php endif; ?>
