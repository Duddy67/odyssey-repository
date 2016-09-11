<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
JHtml::_('behavior.framework');

//Create shortcut for params.
$params = $this->item->params;
//echo '<pre>';
//var_dump($this->item->tags);
//echo '</pre>';
?>

<div class="travel-item">
  <?php echo JLayoutHelper::render('travel_title', array('item' => $this->item, 'params' => $params, 'now_date' => $this->nowDate),
				    JPATH_SITE.'/components/com_odyssey/layouts/'); ?>

  <?php echo JLayoutHelper::render('icons', array('item' => $this->item, 'user' => $this->user, 'uri' => $this->uri),
				    JPATH_SITE.'/components/com_odyssey/layouts/'); ?>

  <?php $useDefList = ($params->get('show_modify_date') || $params->get('show_publish_date') || $params->get('show_create_date')
		       || $params->get('show_hits') || $params->get('show_category') || $params->get('show_parent_category')
		       || $params->get('show_author') ); ?>

  <?php if ($useDefList) : ?>
    <?php echo JLayoutHelper::render('info_block', array('item' => $this->item, 'params' => $params)); ?>
  <?php endif; ?>

  <?php if(($params->get('show_tags') == 'odyssey' || $params->get('show_tags') == 'both') && !empty($this->item->tags->itemTags)) : ?>
    <?php echo JLayoutHelper::render('tags', array('item' => $this->item), JPATH_SITE.'/components/com_odyssey/layouts/'); ?>
  <?php endif; ?>

  <?php echo $this->item->intro_text; ?>

  <?php if(($params->get('show_tags') == 'standard' || $params->get('show_tags') == 'both') && !empty($this->item->tags->itemTags)) : ?>
	  <?php $this->item->tagLayout = new JLayoutFile('joomla.content.tags'); ?>
	  <?php echo $this->item->tagLayout->render($this->item->tags->itemTags); ?>
  <?php endif; ?>

  <?php if($params->get('show_readmore') && !empty($this->item->full_text)) :
	  if($params->get('access-view')) :
	    $link = JRoute::_(OdysseyHelperRoute::getTravelRoute($this->item->slug, $this->item->catid, $this->item->language));
	  else : //Redirect the user to the login page.
	    $menu = JFactory::getApplication()->getMenu();
	    $active = $menu->getActive();
	    $itemId = $active->id;
	    $link = new JUri(JRoute::_('index.php?option=com_users&view=login&Itemid='.$itemId, false));
	    $link->setVar('return', base64_encode(JRoute::_(OdysseyHelperRoute::getTravelRoute($this->item->slug, $this->item->catid, $this->item->language), false)));
	  endif; ?>

	<?php echo JLayoutHelper::render('readmore', array('item' => $this->item, 'params' => $params, 'link' => $link)); ?>

  <?php endif; ?>
</div>

