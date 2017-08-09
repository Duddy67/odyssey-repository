<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('JPATH_BASE') or die;

$tags = $displayData['item']->tags->itemTags;

//Check for the id of the current tag (meaning we're in tag view).
$tagId = 0;
if(isset($displayData['item']->tag_id)) {
  $tagId = $displayData['item']->tag_id;
}
?>

<ul class="tags inline">
<?php foreach($tags as $tag) : //Don't need link for the current tag. ?> 
  <li class="tag-<?php echo $tag->tag_id; ?> tag-list0" itemprop="keywords">
    <?php if($tagId == $tag->tag_id) : ?> 
      <span class="label label-warning"><?php echo $this->escape($tag->title); ?></span>
  <?php else : ?> 
      <a href="<?php echo JRoute::_(OdysseyHelperRoute::getTagRoute($tag->tag_id, $tag->path));?>" class="label label-success"><?php echo $this->escape($tag->title); ?></a>
  <?php endif; ?> 
  </li>
<?php endforeach; ?>
</ul>

