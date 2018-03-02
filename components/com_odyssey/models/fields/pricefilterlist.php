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
require_once JPATH_ROOT.'/components/com_odyssey/helpers/query.php';


//Script which build the select html tag containing the country names and codes.

class JFormFieldPricefilterList extends JFormFieldList
{
  protected $type = 'pricefilterlist';

  protected function getOptions()
  {
    $options = array();
    $post = JFactory::getApplication()->input->post->getArray();
      
    //Get the country names.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('t.price_range')
	  ->from('#__odyssey_travel AS t');

    //Gets the join and where clauses needed for the other filters.
    $filterQuery = OdysseyHelperQuery::getSearchFilterQuery('price');

    //Adds the join and where clauses to the query.
    foreach($filterQuery['join'] as $join) {
      $query->join('INNER', $join);
    }

    foreach($filterQuery['where'] as $where) {
      $query->where($where);
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



