<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.formvalidator');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.modal');

//Prevent params layout (layouts/joomla/edit/params.php) to display twice some fieldsets.
$this->ignore_fieldsets = array('details', 'permissions', 'jmetadata');
//Get the the search filters to use set in global config.
$searchFilters = $this->config->get('search_filters');
?>

<script type="text/javascript">
//Global variable. It will be set as function in travel.js file.
var checkTravelData;
//Global variables. It will be set as function in common.js file.
var checkAlias;
var reverseOrder;

Joomla.submitbutton = function(task)
{
  if(task == 'travel.cancel' || document.formvalidator.isValid(document.getElementById('travel-form'))) {
    //Check that all the data item has been properly set.
    if(task != 'travel.cancel' && (!checkTravelData() || !checkAlias(task, 'travel'))) {
      return false;
    }

    Joomla.submitform(task, document.getElementById('travel-form'));
  }
}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_odyssey&view=travel&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="travel-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_ODYSSEY_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span9 form-vertical">
	  <?php
		echo $this->form->getControlGroup('image');
		echo $this->form->getControlGroup('travel_duration');
		echo $this->form->getControlGroup('show_steps');
		echo $this->form->getControlGroup('show_grouped_steps');
		echo $this->form->getControlGroup('departure_number');
		echo $this->form->getControlGroup('traveltext');
	  ?>
	</div>
	<div class="span3">
	  <?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'travel-sequence', JText::_('COM_ODYSSEY_TAB_TRAVEL_PRICES')); ?>
	<div class="row-fluid">
	  <div class="span12" id="step-sequence">
	    <div class="form-horizontal">
	      <div id="sequence">
		<?php echo $this->form->getControlGroup('dpt_step_id'); ?>
		<?php echo $this->form->getControlGroup('tax_id'); ?>
		<table id="travel-prices" class="table table-striped price-rows">
		</table>
	      </div>
	    </div>
	  </div>
	</div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'travel-addons', JText::_('COM_ODYSSEY_TAB_ADDON_PRICES')); ?>
	<div class="row-fluid">
	  <div class="span12">
	    <div class="form-horizontal">
	      <div id="addons">
	      </div>
	    </div>
	  </div>
	</div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'travel-transitcities', JText::_('COM_ODYSSEY_TAB_TRANSIT_CITY_PRICES')); ?>
	<div class="row-fluid">
	  <div class="span12">
	    <div class="form-horizontal">
	      <div id="transitcities">
	      </div>
	    </div>
	  </div>
	</div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'travel-extradescriptions', JText::_('COM_ODYSSEY_TAB_EXTRA_DESCRIPTIONS')); ?>
	<div class="row-fluid">
	  <div class="span8 form-vertical">
	    <?php
		  echo $this->form->getControlGroup('extra_desc_1');
		  echo $this->form->getControlGroup('extra_desc_2');
		  echo $this->form->getControlGroup('extra_desc_3');
		  echo $this->form->getControlGroup('extra_desc_4');
	    ?>
	  </div>
	</div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

	<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'travel-countryfilter', JText::_('COM_ODYSSEY_TAB_SEARCH_FILTERS')); ?>
	  <div class="row-fluid">
	    <div class="span12 form-horizontal">
	    <?php if($searchFilters != 'region' && $searchFilters != 'city' && $searchFilters != 'region_city') : ?>
	      <div id="countryfilter">
		<div class="search-filter-title"><?php echo JText::_('COM_ODYSSEY_SEARCH_FILTER_COUNTRIES'); ?></div>
	      </div>
	    <?php endif; ?>

	    <?php if($searchFilters != 'country' && $searchFilters != 'city' && $searchFilters != 'country_city') : ?>
	      <div id="regionfilter">
		<div class="search-filter-title"><?php echo JText::_('COM_ODYSSEY_SEARCH_FILTER_REGIONS'); ?></div>
	      </div>
	    <?php endif; ?>

	    <?php if($searchFilters != 'country' && $searchFilters != 'region' && $searchFilters != 'country_region') : ?>
	      <div id="cityfilter">
		<div class="search-filter-title"><?php echo JText::_('COM_ODYSSEY_SEARCH_FILTER_CITIES'); ?></div>
	      </div>
	    <?php endif; ?>
	    </div>
	  </div>
	<?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('JGLOBAL_FIELDSET_PUBLISHING', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6">
	  <?php echo JLayoutHelper::render('joomla.edit.publishingdata', $this); ?>
	</div>
	<div class="span6">
	  <?php echo JLayoutHelper::render('joomla.edit.metadata', $this); ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo JLayoutHelper::render('joomla.edit.params', $this); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'permissions', JText::_('COM_ODYSSEY_TAB_PERMISSIONS', true)); ?>
	      <?php echo $this->form->getInput('rules'); ?>
	      <?php echo $this->form->getInput('asset_id'); ?>
      <?php echo JHtml::_('bootstrap.endTab'); ?>
  </div>

  <input type="hidden" name="search_filters" id="search-filters" value="<?php echo $searchFilters; ?>" />
  <input type="hidden" name="task" value="" />
  <?php echo JHtml::_('form.token'); ?>
</form>

<?php
//Load the jQuery scripts.
$doc = JFactory::getDocument();
$doc->addScript(JURI::base().'components/com_odyssey/js/common.js');
$doc->addScript(JURI::base().'components/com_odyssey/js/travel.js');

