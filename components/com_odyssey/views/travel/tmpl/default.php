<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// no direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');
JHtml::_('formbehavior.chosen', 'select', null, array('disable_search_threshold'=>1));

// Create some shortcuts.
$params = $this->item->params;
$item = $this->item;
?>

<div class="item-page<?php echo $this->pageclass_sfx; ?>" itemscope itemtype="http://schema.org/Travel">
  <?php if($item->params->get('show_page_heading')) : ?>
    <div class="page-header">
      <h1><?php echo $this->escape($params->get('page_heading')); ?></h1>
    </div>
  <?php endif; ?>

  <?php echo JLayoutHelper::render('travel_title', array('item' => $item, 'params' => $params, 'now_date' => $this->nowDate),
				      JPATH_SITE.'/components/com_odyssey/layouts/'); ?>

  <?php echo JLayoutHelper::render('icons', array('item' => $this->item, 'user' => $this->user, 'uri' => $this->uri),
				    JPATH_SITE.'/components/com_odyssey/layouts/'); ?>

  <?php $useDefList = ($params->get('show_modify_date') || $params->get('show_publish_date') || $params->get('show_create_date')
		       || $params->get('show_hits') || $params->get('show_category') || $params->get('show_parent_category')
		       || $params->get('show_author') ); ?>

  <?php if ($useDefList) : ?>
    <?php echo JLayoutHelper::render('info_block', array('item' => $item, 'params' => $params)); ?>
  <?php endif; ?>

  <?php if(($params->get('show_tags') == 'odyssey' || $params->get('show_tags') == 'both') && !empty($this->item->tags->itemTags)) : ?>
    <?php echo JLayoutHelper::render('tags', array('item' => $this->item), JPATH_SITE.'/components/com_odyssey/layouts/'); ?>
  <?php endif; ?>

  <?php if(!empty($item->image)) : ?>
    <img class="travel-image" src="<?php echo $item->image; ?>" alt="<?php echo $this->escape($item->name); ?>" />
  <?php endif; ?>

  <?php if($item->params->get('show_intro')) : ?>
    <?php echo $item->intro_text; ?>
  <?php endif; ?>

  <?php if(!empty($item->full_text)) : ?>
    <?php echo $item->full_text; ?>
  <?php endif; ?>

  <?php if(($params->get('show_tags') == 'standard' || $params->get('show_tags') == 'both') && !empty($this->item->tags->itemTags)) : ?>
	  <?php $this->item->tagLayout = new JLayoutFile('joomla.content.tags'); ?>
	  <?php echo $this->item->tagLayout->render($this->item->tags->itemTags); ?>
  <?php endif; ?>

  <?php if($this->item->show_steps) : ?>
    <?php echo JLayoutHelper::render('steps', array('steps' => $this->steps, 'item' => $this->item), JPATH_SITE.'/components/com_odyssey/layouts/'); ?>
  <?php endif; ?>

  <?php if(!empty($this->item->addons)) : ?>
    <?php echo JLayoutHelper::render('addons', $this->item->addons, JPATH_SITE.'/components/com_odyssey/layouts/'); ?>
  <?php endif; ?>

  <?php echo JLayoutHelper::render('extra_desc', $this->item, JPATH_SITE.'/components/com_odyssey/layouts/'); ?>

  <?php echo JLayoutHelper::render('travel_data', array('travel_data' => $this->travelData, 'item' => $item, 'params' => $params, 'now_date' => $this->nowDate), JPATH_SITE.'/components/com_odyssey/layouts/'); ?>
</div>

<?php
//Load the jQuery scripts.
$doc = JFactory::getDocument();
$doc->addScript(JURI::base().'administrator/components/com_odyssey/js/common.js');
$doc->addScript(JURI::base().'components/com_odyssey/js/traveldata.js');
$doc->addScript(JURI::base().'components/com_odyssey/js/moment.js');
$doc->addScript(JURI::base().'components/com_odyssey/js/jquery-ui-datepicker.js');
$doc->addStyleSheet(JURI::base().'components/com_odyssey/css/jquery-ui-datepicker.css');

