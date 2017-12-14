<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

$addons = $displayData;

echo '<div class="addons">';

foreach($addons as $key => $addon) {
  //Open a new div for each addon type.
  if($key == 0 || $addons[$key - 1]['addon_type'] != $addon['addon_type']) {
    echo '<div class="addon-type '.$addon['addon_type'].'">';
    echo '<h2 class="addon-type-title">'.JText::_('COM_ODYSSEY_ADDON_TYPE_'.strtoupper($addon['addon_type'])).'</h2>';
  }

  //Display addon name and description.
  echo '<div class="addon">'.
       '<h2 class="addon-title">'.$this->escape($addon['name']).'</h2>'.
       '<div class="addon-description">'.$addon['description'].'</div></div>';

  if(!empty($addon['image'])) {
    echo '<div class="addon-image"><img src="'.$addon['image'].'" /></div>';
  }

  //Close the addon type div.
  if(!isset($addons[$key + 1]) || $addons[$key + 1]['addon_type'] != $addon['addon_type']) {
    echo '</div>';
  }
}

echo '</div>';
?>


