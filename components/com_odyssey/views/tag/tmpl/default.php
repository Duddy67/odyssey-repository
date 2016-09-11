<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('formbehavior.chosen', 'select');
JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');
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

<div class="list<?php echo $this->pageclass_sfx;?>">
  <?php if ($this->params->get('show_page_heading')) : ?>
	  <h1>
		  <?php echo $this->escape($this->params->get('page_heading')); ?>
	  </h1>
  <?php endif; ?>
  <?php if($this->params->get('show_tag_title', 1)) : ?>
	  <h2 class="category-title">
	      <?php //echo JHtml::_('content.prepare', $this->tag->title, '', $this->tag->extension.'.tag.title'); ?>
	      <?php echo JHtml::_('content.prepare', $this->tag->title, ''); ?>
	  </h2>
  <?php endif; ?>
  <?php //if($this->params->get('show_tags')) : ?>
	  <?php //echo JLayoutHelper::render('joomla.content.tags', $this->category->tags->itemTags); ?>
  <?php //endif; ?>
  <?php if($this->params->get('show_tag_description') || $this->params->def('show_tag_image')) : ?>
	  <div class="category-desc">
		  <?php if($this->params->get('show_tag_image') && $this->tag->images->get('image_intro')) : ?>
			  <img src="<?php echo $this->tag->images->get('image_intro'); ?>"/>
		  <?php endif; ?>
		  <?php if($this->params->get('show_tag_description') && $this->tag->description) : ?>
			  <?php //echo JHtml::_('content.prepare', $this->tag->description, '', $this->tag->extension.'.tag'); ?>
			  <?php echo JHtml::_('content.prepare', $this->tag->description, ''); ?>
		  <?php endif; ?>
		  <div class="clr"></div>
	  </div>
  <?php endif; ?>


  <form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="siteForm" id="siteForm">

    <?php if($this->params->get('filter_field') != 'hide' || $this->params->get('show_pagination_limit') || $this->params->get('filter_ordering')) : ?>
    <div class="odyssey-toolbar clearfix">
      <?php if ($this->params->get('filter_field') != 'hide') :?>
	<div class="btn-group input-append span6">
	  <label class="filter-search-lbl element-invisible" for="filter-search">
	    <?php echo JText::_('COM_ODYSSEY_'.$this->params->get('filter_field').'_FILTER_LABEL').'&#160;'; ?>
	  </label>
	  <input type="text" name="filter-search" id="filter-search" value="<?php echo $this->escape($this->state->get('list.filter')); ?>"
		  class="inputbox" onchange="odyssey.submitForm();" title="<?php echo JText::_('COM_ODYSSEY_FILTER_SEARCH_DESC'); ?>"
		  placeholder="<?php echo JText::_('COM_ODYSSEY_'.$this->params->get('filter_field').'_FILTER_LABEL'); ?>" />

	  <button type="submit" class="btn hasTooltip" title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_SUBMIT'); ?>">
		  <i class="icon-search"></i>
	  </button>
	  <button type="button" class="btn hasTooltip js-stools-btn-clear" onclick="odyssey.clearSearch();"
		  title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_CLEAR'); ?>">
		  <?php echo JText::_('JSEARCH_FILTER_CLEAR');?>
	  </button>
	</div>
      <?php endif; ?>
     
      <?php echo JLayoutHelper::render('filter_ordering', $this, JPATH_SITE.'/components/com_odyssey/layouts/'); ?>

      <?php if($this->params->get('show_pagination_limit')) : ?>
	<div class="span1">
	  <label for="limit" class="element-invisible"><?php echo JText::_('JGLOBAL_DISPLAY_NUM'); ?></label>
	    <?php echo $this->pagination->getLimitBox(); ?>
	</div>
      <?php endif; ?>

    </div>
    <?php endif; ?>

    <?php if(empty($this->items)) : ?>
	    <?php if($this->params->get('show_no_travels', 1)) : ?>
	    <p><?php echo JText::_('COM_ODYSSEY_NO_TRAVELS'); ?></p>
	    <?php endif; ?>
    <?php else : ?>
      <?php echo $this->loadTemplate('travels'); ?>
    <?php endif; ?>

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

    <?php if(!empty($this->children) && $this->tagMaxLevel != 0) : ?>
	    <div class="cat-children">
	      <h3><?php echo JTEXT::_('COM_ODYSSEY_SUBTAGS_TITLE'); ?></h3>
	      <?php echo $this->loadTemplate('children'); ?>
	    </div>
    <?php endif; ?>

    <input type="hidden" name="limitstart" value="" />
    <input type="hidden" name="task" value="" />
  </form>
</div><!-- list -->
