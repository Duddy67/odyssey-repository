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
require_once JPATH_ROOT.'/components/com_odyssey/models/search.php';
require_once JPATH_ROOT.'/components/com_odyssey/helpers/query.php';


//Script which build the select html tag containing the available departure dates.

class JFormFieldMonthfilterList extends JFormFieldList
{
  protected $type = 'monthfilterlist';

  protected function getOptions()
  {
    $options = $dates = array();
    $nowDate = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);
    $post = JFactory::getApplication()->input->post->getArray();

    //Get departure dates
    //Note: Get only the date part, (ie: yyyy-mm-dd).
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('DISTINCT SUBSTRING(ds.date_time, 1, 7) AS month')
	  ->from('#__odyssey_departure_step_map AS ds')
	  ->join('INNER', '#__odyssey_travel AS t ON t.dpt_step_id=ds.step_id');

    //Gets the join and where clauses needed for the other filters.
    $filterQuery = OdysseyHelperQuery::getSearchFilterQuery('month');

    //Adds the join and where clauses to the query.
    foreach($filterQuery['join'] as $join) {
      $query->join('INNER', $join);
    }

    foreach($filterQuery['where'] as $where) {
      $query->where($where);
    }
    
    $query->where('t.published=1')
	  ->where('(ds.date_time > '.$db->Quote($nowDate).' OR date_time_2 > '.$db->Quote($nowDate).')')
	  ->order('ds.date_time');
    $db->setQuery($query);
    $months = $db->loadColumn();

    //Build the first option.
    $options[] = JHtml::_('select.option', '', JText::_('COM_ODYSSEY_OPTION_SELECT_MONTH'));

    //Build the select options.
    foreach($months as $month) {
      $options[] = JHtml::_('select.option', $month, JHtml::_('date', $month, JText::_('DATE_FORMAT_YM')));
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}



