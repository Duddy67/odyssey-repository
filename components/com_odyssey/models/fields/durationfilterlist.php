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
require_once JPATH_ROOT.'/components/com_odyssey/helpers/query.php';


//Script which build the select html tag containing the country names and codes.

class JFormFieldDurationfilterList extends JFormFieldList
{
  protected $type = 'durationfilterlist';

  protected function getOptions()
  {
    $options = array();
    $post = JFactory::getApplication()->input->post->getArray();
      
    //Get the country names.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('t.travel_duration')
	  ->from('#__odyssey_travel AS t');

    //Gets the join and where clauses needed for the other filters.
    $filterQuery = OdysseyHelperQuery::getSearchFilterQuery('duration');

    //Adds the join and where clauses to the query.
    foreach($filterQuery['join'] as $join) {
      $query->join('INNER', $join);
    }

    foreach($filterQuery['where'] as $where) {
      $query->where($where);
    }

    $query->where('t.published=1')
	  ->group('t.travel_duration')
	  ->order('t.travel_duration DESC');
    $db->setQuery($query);
    $durations = $db->loadColumn();

    //Build the first option.
    $options[] = JHtml::_('select.option', '', JText::_('COM_ODYSSEY_OPTION_SELECT_DURATION'));

    //Build the select options.
    foreach($durations as $duration) {
      $options[] = JHtml::_('select.option', $duration, JText::_('COM_ODYSSEY_OPTION_TRAVEL_DURATION_'.strtoupper($duration)));
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}



