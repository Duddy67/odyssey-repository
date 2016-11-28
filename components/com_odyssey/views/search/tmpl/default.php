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
?>

<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=search');?>" method="post" name="adminForm" id="adminForm">
<?php
// Search tools bar 
//echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
echo JLayoutHelper::render('search_filters', array('view' => $this, 'search_filters' => $this->state->get('search.filters')), JPATH_SITE.'/components/com_odyssey/layouts/');
?>

  <table class="table table-striped">
    <thead>
      <th width="15%">
	<?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_HEADING_NAME', 't.name', $listDirn, $listOrder); ?>
      </th>
      <th width="15%">
	<?php echo JHtml::_('searchtools.sort', 'COM_ODYSSEY_PRICE_STARTING_AT', 'price', $listDirn, $listOrder); ?>
      </th>
    </thead>

    <tbody>
    <?php foreach ($this->items as $i => $item) : ?>

    <tr class="row-<?php echo $i % 2; ?>">
      <td>
	<?php echo $this->escape($item->name); ?>
      </td>
      <td>
	<?php echo UtilityHelper::formatNumber($item->price, $digitsPrecision).' '.$this->currency; ?>
      </td>
      </tr>
    <?php endforeach; ?>
    <tr>
	<td colspan="2"><?php echo $this->pagination->getListFooter(); ?></td>
    </tr>
    </tbody>
  </table>

<input type="hidden" name="option" value="com_odyssey" />
<input type="hidden" name="task" value="" />
<?php echo JHtml::_('form.token'); ?>
</form>

<script type="text/javascript">

(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    $('#search-btn-clear').click( function() { $.fn.clearFilters(); });
  });

  $.fn.clearFilters = function() {
    //Empty drop down lists.
    $('#filter_country').val('');
    $('#filter_region').val('');
    $('#filter_city').val('');
    $('#filter_date').val('');
    //Reload the form.
    $('#adminForm').submit();
  };

})(jQuery);

</script>

