<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// no direct access
defined('_JEXEC') or die;
//JHtml::_('formbehavior.chosen', 'select');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));

$digitsPrecision = $this->config->get('digits_precision');
$nbItems = count($this->items);
?>

<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=search&layout=thumbnail');?>" method="post" name="adminForm" id="adminForm">
<?php
// Search tools bar 
echo JLayoutHelper::render('default', array('view' => $this, 'search_filters' => $this->state->get('search.filters')), JPATH_SITE.'/components/com_odyssey/layouts/search/');
?>

  <?php if (empty($this->items)) : ?>
    <div class="alert alert-no-items">
	<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
    </div>
  <?php else : ?>

    <?php foreach ($this->items as $i => $item) :
      $link = JRoute::_(OdysseyHelperRoute::getTravelRoute($item->slug, $item->catid));
    ?>
      <div class="search-thumbnails">
        <?php echo ($this->params->get('linked_thumbnail')) ? '<a href="'.$link.'">' : ''; ?>
	<div class="thumbnail-image"
	     style="background-image: url('<?php echo $item->image; ?>'); background-size:<?php echo $item->img_width; ?>px <?php echo $item->img_height; ?>px; background-repeat:no-repeat; width:<?php echo $item->img_width; ?>px; height:<?php echo $item->img_height; ?>px;">
	  <div class="bottom-panel">
	    <span class="title">
	    <?php echo (!$this->params->get('linked_thumbnail')) ? '<a href="'.$link.'">' : ''; ?>
	    <?php echo $this->escape($item->name); ?>
	    <?php echo (!$this->params->get('linked_thumbnail')) ? '</a>' : ''; ?>
	    </span>
	  </div>
	</div>
        <?php echo ($this->params->get('linked_thumbnail')) ? '</a>' : ''; ?>

	<div class="infos">
	  <span class="subtitle"><?php echo $this->escape($item->subtitle); ?></span>
	  <span class="price-starting"><?php echo JText::_('COM_ODYSSEY_PRICE_STARTING_AT'); ?></span>
	  <?php if(isset($item->price_prules) && $item->price_prules['price'] < $item->price) : ?>
	    <span class="normal-price"><?php echo UtilityHelper::formatNumber($item->price_prules['normal_price'],
									    $digitsPrecision).' '.$this->currency; ?></span>
	    <span class="pricerules"><?php echo UtilityHelper::formatNumber($item->price_prules['price'],
									    $digitsPrecision).' '.$this->currency; ?></span>
	  <?php else : ?>
	    <span class="price"><?php echo UtilityHelper::formatNumber($item->price, $digitsPrecision).' '.$this->currency; ?></span>
	  <?php endif; ?>
	</div>
      </div>
    <?php endforeach; ?>

    <div class="pagination"><?php echo $this->pagination->getListFooter(); ?></div>
  <?php endif; ?>

<input type="hidden" name="nb_items" id="nb-items" value="<?php echo $nbItems; ?>" />
<input type="hidden" name="option" value="com_odyssey" />
<input type="hidden" name="task" value="" />
<?php echo JHtml::_('form.token'); ?>
</form>

<?php
//Load the jQuery script.
$doc = JFactory::getDocument();
$doc->addScript(JURI::base().'components/com_odyssey/js/search.js');

