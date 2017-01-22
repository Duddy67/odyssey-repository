<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
$class = ' class="first"';
if (count($this->children[$this->category->id]) > 0 && $this->maxLevel != 0) :
?>
<ul class="subdirectories">
<?php foreach ($this->children[$this->category->id] as $id => $child) : ?>
	<?php
	if ($this->params->get('show_empty_categories') || $child->numitems || count($child->getChildren())) :
	if (!isset($this->children[$this->category->id][$id + 1]))
	{
		$class = ' class="last"';
	}
	?>
	<li<?php echo $class; ?>>
		<?php $class = ''; ?>
			<span class="item-title"><a href="<?php echo JRoute::_(OdysseyHelperRoute::getCategoryRoute($child->id));?>">
				<?php echo $this->escape($child->title); ?></a>
			</span>

			<?php if ($this->params->get('show_subcat_desc') == 1) :?>
			<?php if ($child->description) : ?>
				<div class="category-desc">
					<?php echo JHtml::_('content.prepare', $child->description, '', 'com_odyssey.category'); ?>
				</div>
			<?php endif; ?>
            <?php endif; ?>

            <?php if ($this->params->get('show_cat_num_travels') == 1) :?>
			<dl class="travel-count"><dt>
				<?php echo JText::_('COM_ODYSSEY_NUM_TRAVELS'); ?></dt>
				<dd><?php echo $child->numitems; ?></dd>
			</dl>
		<?php endif; ?>

			<?php if (count($child->getChildren()) > 0 ) :
				$this->children[$child->id] = $child->getChildren();
				$this->category = $child;
				$this->maxLevel--;
				echo $this->loadTemplate('children');
				$this->category = $child->getParent();
				$this->maxLevel++;
			endif; ?>
		</li>
	<?php endif; ?>
	<?php endforeach; ?>
	</ul>
<?php endif;
