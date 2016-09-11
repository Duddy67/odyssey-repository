<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

$addonOptions = $displayData['addon_options'];
$addon = $displayData['addon'];
$currency = $displayData['currency'];

echo '<div class="addon-options">';

foreach($addonOptions as $key => $addonOption) {
  //Check the addon option belongs to the current addon.
  if($addonOption['step_id'] == $addon['step_id'] && $addonOption['addon_id'] == $addon['addon_id']) {
    //The first radio button of a group (single select) is checked by default.
    $checked = '';
    if($addonOption['ordering'] == 1) {
      $checked = ' checked="checked"';
    }

    //Display information and price.
    echo '<div class="addon-option">'.
         '<h2 class="addon-option-title">'.$this->escape($addonOption['name']).'</h2>';

    if($addonOption['price'] > 0) {
      echo '<div class="addon-price">'.
	   '<span class="price">'.UtilityHelper::formatNumber($addonOption['price']).'</span>'.
	   '<span class="currency">'.$currency.'</span></div>';
    }

    //Set the addon option tag according to the selection type.
    if($addon['option_type'] == 'single_sel') {
      echo '<input type="radio" class="option-single" name="option_single_'.$addonOption['step_id'].'_'.$addonOption['addon_id'].'" '.
	   'value="'.$addonOption['addon_option_id'].'" '.$checked.'>';
    }
    else { //multi_sel
      echo '<input type="checkbox" class="option-multi" name="option_multi_'.$addonOption['step_id'].'_'.$addonOption['addon_id'].'[]" '.
	   'value="'.$addonOption['addon_option_id'].'" >';
    }

    echo '</div>';
  }
}

echo '</div>';
?>


