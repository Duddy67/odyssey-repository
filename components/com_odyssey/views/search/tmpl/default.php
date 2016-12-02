<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// no direct access
defined('_JEXEC') or die;

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));

$digitsPrecision = $this->config->get('digits_precision');
$nbItems = count($this->items);
?>

<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=search');?>" method="post" name="adminForm" id="adminForm">
<?php
// Search tools bar 
echo JLayoutHelper::render('default', array('view' => $this, 'search_filters' => $this->state->get('search.filters')), JPATH_SITE.'/components/com_odyssey/layouts/search/');
?>

  <table class="table table-striped">
    <thead>
      <th width="25%">
	<?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_NAME', 't.name', $listDirn, $listOrder); ?>
      </th>
      <th width="15%">
	<?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_PRICE_STARTING_AT', 'price', $listDirn, $listOrder); ?>
      </th>
      <th width="15%">
	<?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_TRAVEL_DURATION', 't.travel_duration', $listDirn, $listOrder); ?>
      </th>
    </thead>

    <tbody>
    <?php foreach ($this->items as $i => $item) : ?>

    <tr class="row-<?php echo $i % 2; ?>">
      <td>
        <a href="<?php echo JRoute::_(OdysseyHelperRoute::getTravelRoute($item->slug, $item->catid)); ?>">
	<?php echo $this->escape($item->name); ?></a>
      </td>
      <td>
	<?php echo UtilityHelper::formatNumber($item->price, $digitsPrecision).' '.$this->currency; ?>
      </td>
      <td class="small">
	<?php echo JText::_('COM_ODYSSEY_OPTION_TRAVEL_DURATION_'.strtoupper($item->travel_duration)); ?>
      </td>
      </tr>
    <?php endforeach; ?>
    <tr>
	<td colspan="3"><?php echo $this->pagination->getListFooter(); ?></td>
    </tr>
    </tbody>
  </table>

<input type="hidden" name="nb_items" id="nb-items" value="<?php echo $nbItems; ?>" />
<input type="hidden" name="option" value="com_odyssey" />
<input type="hidden" name="task" value="" />
<?php echo JHtml::_('form.token'); ?>
</form>

<?php
//Load the jQuery script.
$doc = JFactory::getDocument();
$doc->addScript(JURI::base().'components/com_odyssey/js/search.js');

