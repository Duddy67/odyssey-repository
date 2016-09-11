<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

$items = array('travels' => 'airplane',
	       'steps' => 'road',
	       'addons' => 'plus',
	       'pricerules' => 'calculator',
	       'coupons' => 'barcode',
	       'customers' => 'users',
	       'orders' => 'paste',
	       'cities' => 'office',
	       'countries' => 'flag',
	       'currencies' => 'coin-dollar',
	       'paymentmodes' => 'credit-card',
	       'taxes' => 'pie-chart');
?>

<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=odyssey');?>" method="post" name="adminForm" id="adminForm">

<div id="cpanel" class="row-fluid"> 

<?php foreach($items as $name => $icon) : ?>
  <div class="thumbnail">
  <a href="index.php?option=com_odyssey&view=<?php echo $name; ?>">
  <span class="icon-<?php echo $icon; ?>" style="font-size:32px;"></span>
  <span class="odyssey-icon-title"><?php echo JText::_('COM_ODYSSEY_ODYSSEY_'.strtoupper($name).'_TITLE'); ?></span></a>
  </div>
<?php endforeach; ?>

<div class="thumbnail">
  <a href="index.php?option=com_categories&extension=com_odyssey">
  <span class="icon-folder" style="font-size:32px;"></span>
  <span class="odyssey-icon-title"><?php echo JText::_('COM_ODYSSEY_ODYSSEY_CATEGORIES_TITLE'); ?></span></a>
</div>

</div>


<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="option" value="com_odyssey" />
<input type="hidden" name="task" value="" />
<?php echo JHtml::_('form.token'); ?>
</form>

