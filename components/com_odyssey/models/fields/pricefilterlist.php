<?php
/**
 * @package Odyssey
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
// import the list field type
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');
require_once JPATH_ROOT.'/administrator/components/com_odyssey/helpers/utility.php';


//Script which build the select html tag containing the country names and codes.

class JFormFieldPricefilterList extends JFormFieldList
{
  protected $type = 'pricefilterlist';

  protected function getOptions()
  {
    $options = array();
    $post = JFactory::getApplication()->input->post->getArray();
    $country = $region = $city = '';

    if(isset($post['filter']['country'])) {
      $country = $post['filter']['country'];
    }

    if(isset($post['filter']['region'])) {
      $region = $post['filter']['region'];
    }

    if(isset($post['filter']['city'])) {
      $city = $post['filter']['city'];
    }
      
      
    //Get the country names.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('t.price_range')
	  ->from('#__odyssey_travel AS t');

    if(!empty($country)) {
      $query->join('INNER', '#__odyssey_search_filter AS sf_co ON sf_co.travel_id=t.id')
	    ->where('sf_co.country_code='.$db->Quote($country));
    }

    if(!empty($region)) {
      $query->join('INNER', '#__odyssey_search_filter AS sf_re ON sf_re.travel_id=t.id')
	    ->where('sf_re.region_code='.$db->Quote($region));
    }

    if(!empty($city)) {
      $query->join('INNER', '#__odyssey_search_filter AS sf_ci ON sf_ci.travel_id=t.id')
	    ->where('sf_ci.city_id='.(int)$city);
    }

    $query->where('t.published=1')
	  ->group('t.price_range')
	  ->order('LENGTH(t.price_range), t.price_range');
    $db->setQuery($query);
    $prices = $db->loadColumn();

    $currency = UtilityHelper::getCurrency();

    //Build the first option.
    $options[] = JHtml::_('select.option', '', JText::_('COM_ODYSSEY_OPTION_SELECT_PRICE'));

    //Build the select options.
    foreach($prices as $price) {
      $options[] = JHtml::_('select.option', $price, JText::_('COM_ODYSSEY_OPTION_PRICE_RANGE_'.strtoupper($price)).' '.$currency);
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}



