<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('behavior.framework');


if(!empty($displayData->extra_desc_1)) {
  echo '<div class="extra-desc-1">'.$displayData->extra_desc_1.'</div>';
}

if(!empty($displayData->extra_desc_2)) {
  echo '<div class="extra-desc-2">'.$displayData->extra_desc_2.'</div>';
}

if(!empty($displayData->extra_desc_3)) {
  echo '<div class="extra-desc-3">'.$displayData->extra_desc_3.'</div>';
}

if(!empty($displayData->extra_desc_4)) {
  echo '<div class="extra-desc-4">'.$displayData->extra_desc_4.'</div>';
}

?>


