<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// no direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');

$addons = $this->addonData['addons'];
$addonOptions = $this->addonData['addon_options'];

//Ensure first that there is at least one hosting addon (matching with the number of
//passengers selected by the customer).
$isHosting = false;
foreach($addons as $addon) {
  if($addon['addon_type'] == 'hosting') {
    $isHosting = true;
    break;
  }
}

//Grab the user session.
$session = JFactory::getSession();
$travel = $session->get('travel', array(), 'odyssey'); 
$settings = $session->get('settings', array(), 'odyssey'); 
//echo '<pre>';
//var_dump($travel);
//echo '</pre>';

?>

<?php echo JLayoutHelper::render('booking_breadcrumb', array('position' => 'addons', 'travel' => $travel),
                                  JPATH_SITE.'/components/com_odyssey/layouts/'); ?>

<?php if($isHosting) : ?>

  <?php echo JLayoutHelper::render('booking_summary', array('travel' => $travel, 'settings' => $settings),
				    JPATH_SITE.'/components/com_odyssey/layouts/'); ?>

  <form action="index.php?option=com_odyssey&task=addons.setAddons" method="post" name="addons" id="addons">
  <?php
  $stepIds = array();

  foreach($addons as $key => $addon) {
    //Create an opening div for each step.
    if(!in_array($addon['step_id'], $stepIds)) {
      $stepIds[] = $addon['step_id'];

      //Close the previous div (unless we're dealing with the very first div).
      if(count($stepIds) > 1) {
	echo '</div>';
      }

      echo '<div class="addon-step">';
    }

    //The addon has no group.
    if($addon['group_nb'] == 'none') {
      //Display information and price.
      echo '<div class="addon">'.
	   '<h2 class="addon-title">'.$this->escape($addon['name']).'</h2>'.$addon['description'];

      if($addon['price'] > 0) {
	echo '<div class="addon-price">'.
	     '<span class="price">'.UtilityHelper::formatNumber($addon['price']).'</span>'.
	     '<span class="currency">'.$this->currency.'</span></div>';
      }
      //Use a hidden type tag as there is no selection for this addon.
      //Note: Value attribute is useless here as we get all the required ids from the name.
      echo '<input type="hidden" name="none_'.$addon['step_id'].'_'.$addon['addon_id'].'" value="'.$addon['addon_id'].'" >';

      //Check for addon options.
      if(!empty($addon['option_type'])) {
	echo JLayoutHelper::render('addon_options', array('addon_options' => $addonOptions, 
							  'addon' => $addon,
							  'currency' => $this->currency), JPATH_SITE.'/components/com_odyssey/layouts/');
      }

      echo '</div>';
    }
    else { //Addons belong to a group.
      //Parse the group_nb value to get the group number as well as the selection type.
      preg_match('#^([0-9]+)\:(no_sel|single_sel|multi_sel)$#', $addon['group_nb'], $matches);
      $grpNb = $matches[1];
      $selType = $matches[2];
      //The first radio button of a group (single select) is checked by default.
      $checked = ' checked="checked"';

      //The previous addon belongs to the same group than the current one.
      if(isset($addons[$key - 1]) && $addons[$key - 1]['group_nb'] == $addon['group_nb']) {
	$checked = '';
      }
      else { //It's the first addon of the group.
	echo '<div class="addon-group">';
      }

      //Display information and price.
      echo '<div class="addon">'.
	   '<h2 class="addon-title">'.$this->escape($addon['name']).'</h2>'.$addon['description'];

      if($addon['price'] > 0) {
	echo '<div class="addon-price">'.
	     '<span class="price">'.UtilityHelper::formatNumber($addon['price']).'</span>'.
	     '<span class="currency">'.$this->currency.'</span></div>';
      }

      //Set the addon tag according to the selection type.
      if($selType == 'single_sel') {
	//Note: Ids are set differently for single selection (radio buttons).
	echo '<input type="radio" class="single" name="single_'.$grpNb.'_'.$addon['step_id'].'" value="'.$addon['addon_id'].'" '.$checked.'>';
      }
      elseif($selType == 'multi_sel') {
	echo '<input type="checkbox" class="multi" name="multi_'.$grpNb.'_'.$addon['step_id'].'[]" value="'.$addon['addon_id'].'" >';
      }
      else { //no_sel
	//Use a hidden type tag as there is no selection for this addon.
	echo '<input type="hidden" name="no_'.$grpNb.'_'.$addon['step_id'].'" value="'.$addon['addon_id'].'" >';
      }

      //Check for addon options.
      if(!empty($addon['option_type'])) {
	echo JLayoutHelper::render('addon_options', array('addon_options' => $addonOptions, 
							  'addon' => $addon, 
							  'currency' => $this->currency), JPATH_SITE.'/components/com_odyssey/layouts/');
      }

      echo '</div>'; //Close the addon div.

      //The current addon is the last addon of the group.
      if(!isset($addons[$key + 1]) || $addons[$key + 1]['group_nb'] != $addon['group_nb']) {
	echo '</div>'; //Close the addon group div.
      }
    }
  }

  echo '</div>'; //Close the last addon step div.

  ?>
    <div id="btn-message">
      <input type="submit" class="btn btn-warning" onclick="hideButton('btn')" value="<?php echo JText::_('COM_ODYSSEY_BUTTON_NEXT'); ?>" />
    </div>
  </form>
<?php else : //No hosting addon. ?>
  <div class="no-hosting">
    <?php echo JText::sprintf('COM_ODYSSEY_NO_HOSTING_ADDON_AVAILABLE', $travel['nb_psgr']); ?>
  </div>
<?php endif; ?>


