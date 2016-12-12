<?php
/**
 * @package Odyssey
 * @copyright Copyright (c)2016 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; // No direct access.

//Get the component settings.
$parameters = JComponentHelper::getParams('com_odyssey');
$digitsPrecision = $parameters->get('digits_precision');

//Include css file.
$css = JFactory::getDocument();
$css->addStyleSheet(JURI::base().'modules/mod_odysplay/odysplay.css');
?>

<div class="odysplay-module">
<?php if($module->showtitle) : ?>
  <h1 class="module-title"><?php echo $module->title; ?></h1>
<?php endif; ?>

<?php foreach($travels as $travel) : 
	$link = JRoute::_(OdysseyHelperRoute::getTravelRoute($travel->slug, $travel->catid, $travel->language));
?>
  <div class="odysplay-travel">
    <a href="<?php echo $link; ?>"><h2 class="travel-name"><?php echo $travel->name; ?></h2></a>

    <?php if($params->get('show_image')) : ?>
      <a href="<?php echo $link; ?>">
	<img src="<?php echo $travel->image; ?>" width="<?php echo $travel->img_width; ?>"
	     height="<?php echo $travel->img_height; ?>" class="travel-image" /></a>
    <?php endif; ?>

    <?php if($params->get('show_introtext')) : ?>
      <div class="travel-text"><?php echo $travel->intro_text; ?></div>
    <?php endif; ?>

    <div class="travel-price">
      <?php echo UtilityHelper::formatNumber($travel->starting_price, $digitsPrecision).' '.$currency; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>

