<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
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
<script type="text/javascript">
var odyssey = {
  clearSearch: function() {
    document.getElementById('filter-search').value = '';
    odyssey.submitForm();
  },

  submitForm: function() {
    var action = document.getElementById('siteForm').action;
    //Set an anchor on the form.
    document.getElementById('siteForm').action = action+'#siteForm';
    document.getElementById('siteForm').submit();
  }
};
</script>


<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=search&layout=thumbnail');?>" method="post" name="siteForm" id="siteForm">
<?php
// Search tools bar 
//echo JLayoutHelper::render('search.default', array('view' => $this, 'search_filters' => $this->state->get('search.filters')));
//TODO: The default layout file needs to be reshape. 
echo JLayoutHelper::render('search.filters', array('view' => $this, 'search_filters' => $this->state->get('search.filters')));
?>

<div class="btn-wrapper">
  <div id="search-btn-clear" class="btn hasTooltip js-stools-btn-clear"
	  title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_CLEAR'); ?>">
	  <?php echo JText::_('JSEARCH_FILTER_CLEAR');?>
  </div>
</div>

<?php
if($this->params->get('show_pagination_limit')) {
  echo JLayoutHelper::render('limitbox', array('limit_range' => $this->params->get('display_num'),
					       'current_limit' => $this->state->get('list.limit')));
}
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

    <?php if(($this->params->def('show_pagination', 2) == 1  || ($this->params->get('show_pagination') == 2)) && ($this->pagination->pagesTotal > 1)) : ?>
    <div class="pagination">

	    <?php if ($this->params->def('show_pagination_results', 1)) : ?>
		    <p class="counter pull-right">
			    <?php echo $this->pagination->getPagesCounter(); ?>
		    </p>
	    <?php endif; ?>

	    <?php //Load our own pagination layout. ?>
	    <?php echo JLayoutHelper::render('travel_pagination', $this->pagination, JPATH_SITE.'/components/com_odyssey/layouts/'); ?>
    </div>
    <?php endif; ?>
  <?php endif; ?>

<input type="hidden" name="nb_items" id="nb-items" value="<?php echo $nbItems; ?>" />
<input type="hidden" name="option" value="com_odyssey" />
<input type="hidden" name="limitstart" value="" />
<input type="hidden" name="task" value="" />
<?php echo JHtml::_('form.token'); ?>
</form>

<?php
JHtml::_('jquery.framework');
//Load the jQuery script.
$doc = JFactory::getDocument();
$doc->addScript(JURI::base().'components/com_odyssey/js/search.js');

