<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');

$testimonies = $displayData;

echo '<div class="testimonies"><h1>'.JText::_('COM_ODYSSEY_TESTIMONIES_TITLE').'</h1>';

foreach($testimonies as $testimony) {
  echo '<div class="testimony">'.
       '<h2 class="testimony-title">'.$this->escape($testimony['title']).'</h2>'.
       '<h4 class="testimony-author">'.$this->escape($testimony['author_name']).'</h4>'.
       '<div class="testimony-text">'.$testimony['testimony_text'].'</div>'.
       '<div class="social-networks">';

  if(!empty($testimony['facebook'])) {
    echo '<a href="'.$testimony['facebook'].'" class="social-network" target="_blank">'.
         '<img src="'.JURI::base().'media/com_odyssey/images/facebook.png" width="20" heigh="20" alt="facebook-logo" /></a>';
  }

  if(!empty($testimony['twitter'])) {
    echo '<a href="'.$testimony['twitter'].'" class="social-network" target="_blank">'.
         '<img src="'.JURI::base().'media/com_odyssey/images/twitter.png" width="20" heigh="20" alt="twitter-logo" /></a>';
  }

  if(!empty($testimony['google_plus'])) {
    echo '<a href="'.$testimony['google_plus'].'" class="social-network" target="_blank">'.
         '<img src="'.JURI::base().'media/com_odyssey/images/google_plus.png" width="20" heigh="20" alt="google_plus-logo" /></a>';
  }

  if(!empty($testimony['pinterest'])) {
    echo '<a href="'.$testimony['pinterest'].'" class="social-network" target="_blank">'.
         '<img src="'.JURI::base().'media/com_odyssey/images/pinterest.png" width="20" heigh="20" alt="pinterest-logo" /></a>';
  }

  echo '</div></div>';
}

echo '</div>';
?>


