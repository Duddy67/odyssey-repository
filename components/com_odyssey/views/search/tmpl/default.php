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
//echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
echo JLayoutHelper::render('search_filters', array('view' => $this, 'search_filters' => $this->state->get('search.filters')), JPATH_SITE.'/components/com_odyssey/layouts/');
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

<script type="text/javascript">

(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {
    $('#search-btn-clear').click( function() { $.fn.clearFilters(); });
    $.fn.setFilters($('#nb-items').val());
  });

  $.fn.clearFilters = function() {
    //Empty drop down lists.
    $('#filter_country').val('');
    $('#filter_region').val('');
    $('#filter_city').val('');
    $('#filter_duration').val('');
    $('#filter_date').val('');
    //Reload the form.
    $('#adminForm').submit();
  };


  $.fn.setFilters = function(nbItems) {
    //Get filter values.
    var country = $('#filter_country').val();
    var region = $('#filter_region').val();
    var city = $('#filter_city').val();
    var duration = $('#filter_duration').val();
    var departure = $('#filter_date').val();

    if(region !== undefined && region !== '') {
      $('#filter_country').css({'visibility':'hidden','display':'none'});
    }

    if(city !== undefined && city !== '') {
      $('#filter_country').css({'visibility':'hidden','display':'none'});
      $('#filter_region').css({'visibility':'hidden','display':'none'});
    }

    if(country === '' && region === '' && city === '') {
      //Don't display departure filter as long as no filter value above is set.
      $('#filter_date').css({'visibility':'hidden','display':'none'});
    }

    if($('#filter_duration').children('option').length == 2) {
      //If there is only 1 value left (plus the select value) there is no need to 
      //show the filter. Duration value can be read in the result array.
      $('#filter_duration').css({'visibility':'hidden','display':'none'});
    }

    //Single result.
    if(nbItems == 1) {
      $('#filter_date option[value=""]').text('- Next departures -');
      //Prevent from loading the form again.
      $('#filter_date').removeAttr('onchange');
    }
  };
})(jQuery);

</script>

