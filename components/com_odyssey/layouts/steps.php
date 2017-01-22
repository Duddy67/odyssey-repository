<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

$steps = $displayData['steps'];
$item = $displayData['item'];

echo '<div class="steps">';

foreach($steps as $step) {
  if($item->show_grouped_steps || (!$item->show_grouped_steps && !$step['group_prev'])) {
    //Display step name image and description.
    echo '<div class="step">'.
	 '<h2 class="step-title">'.$this->escape($step['name']).'</h2>';

    if(!empty($step['subtitle'])) {
      echo '<h3 class="step-subtitle">'.$this->escape($step['subtitle']).'</h3>';
    }

    if(!empty($step['image'])) {
      echo '<img src="'.$step['image'].'" class="step-image" alt="'.$this->escape($step['name']).'" />';
    }

    echo '<div class="step-description">'.$step['description'].'</div></div>';
  }
}

echo '</div>';
?>


